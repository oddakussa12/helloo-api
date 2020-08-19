<?php

namespace App\Models;

use App\Services\EsClient;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class Es
{
    use BuildsQueries;
    public $mIndex;
    private $client;
    private $limit;
    private $offset;

    public function __construct($mIndex='')
    {
        $this->client = new EsClient();
        $this->mIndex = $mIndex ?: env('ELASTICSEARCH_INDEX');
        $this->limit  = app('request')->get('limit')  ?: 10;
        $this->offset = app('request')->get('page') ?: 0;
    }

    public function create(array $request, $flag=false)
    {
        $body = $request;
        if ($flag) {
            $body = array_merge($request, [
                'create_at' => time(),
                'update_at' => time(),
                'is_delete' => 0,
                '@timestamp' => date('c', time())
            ]);
        }
        return $this->client->index([
            'index'   => $this->mIndex,
            'type'     => '_doc',
            'refresh' => true,
            'body'    => $body
        ]);
    }


    public function batchCreate($params)
    {
        try {
            $data = ['body' => []];
            foreach ($params as $param) {
                $data['body'][] = [
                    'index' => [
                        '_index' => $this->mIndex,
                        '_type'  => '_doc',
                    ],
                ];
                $data['body'][] = array_merge([], $param);
            }
            return $this->client->bulk($data);

        } catch (\Throwable $e) {
            \Log::info(__FUNCTION__.' message: ', [$e->getCode(),$e->getMessage()]);
        }
    }

    public function update($ids)
    {
        $ids = $this->toArray($ids);

        $columns['update_at'] = time();
        $this->client->updateByQuery([
            'index' => $this->mIndex,
            'refresh' => true,
            'body' => array_merge([
                'query' => [
                    'terms' => [
                        '_id' => $ids
                    ]
                ],
            ], $this->makeUpdateScripts($columns))
        ]);


    }

    public function makeUpdateScripts($columns)
    {
        $fields = '';
        foreach ($columns as $k => $v) {
            $fields .= "ctx._source.{$k} = params.${k};";
        }
        return [
            'script' => [
                'inline' => trim($fields, ';'),
                'params' => $columns,
                'lang' => 'painless'
            ]
        ];
    }

    public function delete($ids)
    {
        $columns = ['update_at' => time(), 'is_delete' => 1];
        $ids = $this->toArray($ids);
        $this->client->updateByQuery([
            'index' => $this->mIndex,
            'refresh' => true,
            'body' => array_merge([
                'query' => [
                    'terms' => [
                        '_id' => $ids
                    ]
                ],
            ], $this->makeUpdateScripts($columns))
        ]);
        return $ids;
    }

    public function view($id)
    {
        $data = self::findOne($id);
        if (empty($data)) {

        } else {

        }
    }

    public function findOne($id, $column = '_id')
    {
        $response = $this->client->search([
            'index' => $this->mIndex,
            'body' => [
                'query' => [
                    'term' => [
                        $column => $id
                    ]
                ]
            ]
        ]);
        return $response['hits']['hits'][0];
    }

    public function makeResult($data)
    {
        return array_merge($data['_source'], ['id' => $data['_id']], isset($data['highlight']) ? ['.highlight' => $data['highlight']] : []);
    }

    public function afterView($id)
    {
    }

    public function likeQuery($request, $likeColumns)
    {
        $columns = [];
        $keywords = trim($request['keyword']);
        $query = [
            'index' => $this->mIndex,
            'body' => array_merge([
                'query' => [
                    'bool' => array_merge_recursive($this->makeTermQuery($columns), $this->makeLikeQuery($likeColumns, $keywords))
                ]
            ], $this->makeOrderCypher(), $this->makePaginationCypher())
        ];
        $response = $this->client->search($query);
        if ($response) {
            return $this->makeAsGrid($response);
        }
    }

    public function makeTermQuery($columns)
    {
        $must = [];
        foreach ($columns as $k => $v) {
            $must[] = [
                'term' => [
                    $this->makeColumn($k) => $v
                ]
            ];
        }
        return [
            'must' => $must
        ];
    }

    public function makeColumn($column)
    {
        if (isset($columns[$column])) {
            $column .= '.keyword';
        }
        return $column;
    }

    public function makeLikeQuery($columns, $keywords, $flag=false)
    {
        $type  = $flag ? 'wildcard' : 'match';
        $query = [];
        if ($keywords && count($columns) > 0) {
            if ($flag) {
                $keywords = $this->client->escape(trim($keywords));
                if (mb_substr($keywords, 0, 1) !== '*') {
                    $keywords = '*' . $keywords;
                }
                if (mb_substr($keywords, mb_strlen($keywords) - 1, 1) !== '*') {
                    $keywords .= '*';
                }
            }
            $should = [];
            foreach ($columns as $k) {
                $should[] = [
                    $type => [
                        $this->makeColumn($k) => $keywords
                    ]
                ];
            }
            $query['should'] = $should;
            $query['minimum_should_match'] = 1;
        }
        return $query;
    }

    public function makeOrderCypher()
    {
        $dsl = [];
        $sort = app('request')->get('sort');
        $order = app('request')->get('order', 'asc');
        if (!empty($sort) && !empty($order)) {
            $dsl['sort'] = [
                [
                    $this->makeColumn($sort) => ['order' => $order]
                ]
            ];
        }
        return $dsl;
    }

    public function makePaginationCypher()
    {
        return [
            'size' => $this->limit,
            'from' => $this->offset
        ];
    }

    public function makeAsGrid($response)
    {
        $result = $this->makeAsList($response);
        $total = $response['hits']['total']['value'];

        return new LengthAwarePaginator($result, $total, $this->limit, $this->offset, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
        return $this->paginator($result, $total, $this->limit, $this->offset, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }


    public function makeAsList($response)
    {
        $hits = $response['hits']['hits'];
        $result = [];
        foreach ($hits as $hit) {
            $result[] = $this->makeResult($hit);
        }
        return $result;
    }

    public function makeRangeQuery(&$query)
    {
        $timeRange = $this->mParamDTO->getValue('time_range');

        if ($timeRange) {
            $timeRange = json_decode($timeRange);
            $query[] = [
                'range' => [
                    'creat_at' => [
                        'gte' => $timeRange[0],
                        'lte' => $timeRange[1]
                    ]
                ]
            ];
        }
    }

    public function toArray($string, $sep = ',')
    {
        if (!empty($string)) {
            if (is_string($string)) {
                return explode($sep, $string);
            }
        }
        return $string;
    }
}
