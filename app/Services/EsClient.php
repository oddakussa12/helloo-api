<?php

namespace App\Services;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Namespaces\IndicesNamespace;

/**
 * Class EsClient
 * @package app\api\common\components
 * @method mixed index($params = []) create or update document
 * @method IndicesNamespace indices()
 * @method mixed update($params = [])
 * @method mixed updateByQuery(array $params = [])
 * @method mixed get(array $params = [])
 * @method mixed bulk(array $params = [])
 */
class EsClient
{

    public $hosts;

    /**
     * 分片数
     * @var
     */
    public $shards;

    /**
     * 副本数
     * @var
     */
    public $replicas;

    /**
     * @var Client
     */
    private $client;

    public function escape($str)
    {
        $arr = [];
        for ($i = 0; $i < mb_strlen($str); $i++) {
            $c = mb_substr($str, $i, 1);
            if ($c == '\\' || $c == '+' || $c == '-' || $c == '!' || $c == '(' || $c == ')' || $c == ':' || $c == '^' || $c == '[' || $c == ']' || $c == '"' || $c == '{' || $c == '}' || $c == '~' || $c == '*' || $c == '?' || $c == '|' || $c == '&' || $c == '/') {
                $arr[] = '\\';
            }
            $arr[] = $c;
        }
        return implode('', $arr);
    }

    /**
     * triggered when invoking inaccessible methods in an object context
     * ... is availabel in PHP 5.6+
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $this->_open();
        return $this->client->$name(...$arguments);
    }

    private function _open()
    {
        if ($this->client != null) {
            return;
        }
        $this->client = ClientBuilder::create()
            ->setHosts(config('scout.elasticsearch.hosts'))->setRetries(3)
            ->build();
    }
}
