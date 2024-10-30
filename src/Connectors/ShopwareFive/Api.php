<?php

namespace Go2Flow\Ezport\Connectors\ShopwareFive;

use Go2Flow\Ezport\Connectors\ApiInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Str;

class Api implements ApiInterface
{
    private $url;
    private $response;
    private $queryString = null;

    public function __construct(private array $connector)
    {

    }

    public function product() : self
    {
        $this->url = 'articles';

        return $this;
    }

    public function category()
    {
        $this->url = 'categories';
        return $this;
    }


    public function find($id)
    {
        $this->url = $this->url . '/' . $id;

        return $this->getRequest();
    }

    public function get()
    {
        return $this->getRequest();
    }

    public function body()
    {
        return $this->response !== false ? json_decode($this->response->getBody()) : null;
    }

    public function limit(string|int $string) : self
    {
        $this->parameter('limit=' . $string);

        return $this;
    }

    public function start(string|int $string) : self
    {
        $this->parameter('start=' . $string);

        return $this;
    }

    private function getRequest() : self
    {
        $client = new GuzzleClient();

        $auth = base64_encode($this->connector['username'] . ':' . $this->connector['password']);

            try {
                $this->response = $client->get(
                    $this->connector['host'] . '/api/' . $this->setUrl(), [
                    'headers' => [
                        'Authorization' => 'Basic ' . $auth,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ],
                ],

                );
            } catch (ClientException $e) {
                $this->response = $e->getResponse();
            }

        return $this;
    }

    private function parameter($string)
    {
        $this->queryString = $this->queryString
            ? $this->queryString . '&' . $string
            : '?' . $string;

        return $this;
    }


    private function setUrl()
    {
        return $this->url . ($this->queryString ?? '');
    }

    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return $this->{$method}(...$args);
        }

        $this->url = Str::plural($method);

        return $this;
    }
}
