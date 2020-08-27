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
    private $term;
    private $likeColumns;
    private $page;

    public function __construct($mIndex='', $extra=[])
    {
        $this->client      = new EsClient();
        $this->term        = $extra['term'] ?? [];
        $this->mIndex      = $mIndex ?: env('ELASTICSEARCH_INDEX');
        $this->limit       = $extra['limit'] ?? (app('request')->get('limit')  ?: 10);
        $this->page        = app('request')->get('page') ?: 0;
        $this->offset      = intval($this->page * $this->limit);
        $this->likeColumns = [
          config('scout.elasticsearch.post')  => ['post_content'],
          config('scout.elasticsearch.topic') => ['topic_content'],
          config('scout.elasticsearch.user')  => ['user_nick_name', 'user_name'],
        ];

        if (!empty($extra['likeColumns'])) {
            $this->likeColumns[$this->mIndex] = $extra['likeColumns'];
        }

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
            '_id'     => $request[$this->mIndex."_id"],
            'type'    => '_doc',
            'refresh' => true,
            'body'    => $body
        ]);
    }


    public function batchCreate($params)
    {
        try {
            $data = ['body' => []];
            $index = [
                '_index' => $this->mIndex,
                '_type'  => '_doc',
            ];

            foreach ($params as $param) {
                if ($this->mIndex=='user') {
                    $index['_id'] = $param[$this->mIndex.'_id'];
                }
                $data['body'][] = [
                    'index' => $index,
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

    public function suggest($request)
    {

        $keywords = trim($request['keyword']);
        $query = [
            'index' => $this->mIndex,
            'body'  => array_merge($this->completion($keywords), $this->makePaginationCypher())
        ];

//        dump(json_encode($query));
        $response = $this->client->search($query);
        return $this->makeAsSuggest($response);
    }

    public function makeAsSuggest($response)
    {
        $suggest = $response['suggest'];

        $result = [];
        foreach ($suggest as $hit) {
            foreach ($hit as $item) {
                foreach ($item['options'] as $it) {
                    if(!empty($it['_source'])){
                        if ($this->mIndex == 'post') {
                            $result[] = ['topic_content'=>$it['text']];
                        } else {
                            $result[] = array_merge($it['_source'], ['id' => $it['_id'], 'text'=>$it['text']]);
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function completion($keywords)
    {
        $suggest = [];
        foreach ($this->likeColumns[$this->mIndex] as $v) {
            $suggest = [
                $v => [
                    "prefix" => $keywords,
                    "completion" => [
                        "field" => $v.".suggest",
                        "skip_duplicates"=> true
                    ]
                ]
            ];
        }
        return ['suggest'=> $suggest];

    }



    public function phrase($keywords)
    {
        $suggest = [];
        foreach ($this->likeColumns[$this->mIndex] as $v) {
            $suggest = [
                $v    => [
                    "text" => $keywords,
                    "phrase" => [
                        "field" => $v,
                        "skip_duplicates"=> true
                    ]
                ]

            ];
        }
        return ['suggest'=> $suggest];

    }
    public function likeQuery($request)
    {
        $keywords = trim($request['keyword']);
        $query = [
            'index' => $this->mIndex,
            'body' => array_merge([
                'query' => [
                    'bool' => array_merge_recursive($this->makeTermQuery($this->term), $this->makeLikeQuery($this->likeColumns[$this->mIndex], $keywords))
                ]
            ], $this->makeOrderCypher(), $this->makePaginationCypher())
        ];

        $response = $this->client->search($query);
        return $this->makeAsGrid($response);
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

        /*return new LengthAwarePaginator($result, $total, $this->limit, $this->page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);*/
        return $this->paginator(collect($result), $total, $this->limit, $this->page, [
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
