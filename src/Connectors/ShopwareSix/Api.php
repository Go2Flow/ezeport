<?php

namespace Go2Flow\Ezport\Connectors\ShopwareSix;

use Go2Flow\Ezport\Connectors\ApiInterface;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\Response;

class Api implements ApiInterface
{
    protected Client $client;
    protected ?object $response;
    protected ?string $path;

    public function __construct(
        array $connector,
        private Collection $structure,
        ?Guzzle $testGuzzle = null
    ) {
        $this->client = (new Client($connector, $testGuzzle))->setupToken();
    }

    /** special path setter for manufacturers that shortens the path */

    public function manufacturer(): self
    {
        return $this->setPath('product-manufacturer');
    }

    /** speical path setter for property options that shortens the path */

    public function propertyOption(): self
    {
        return $this->setPath('property-group-option');
    }

    /** a bulk create or update for the set path (e.g. update or create multiple products) */
    public function bulk(array $payload): self
    {
        return $this->bulkPostRequest($payload);
    }

    /** a bulk delete for the set path (e.g. delete multiple products) */
    public function bulkDelete(array $payload): self
    {

        return $this->bulkDeleteRequest($payload);
    }

    /** create a single instance of the set path (e.g. a single product) */
    public function create(array $payload): self
    {
        return $this->postRequest(
            $payload,
            $this->path,
        );
    }

    /** delete a single instance of the set path (e.g. delete a single product) */
    public function delete(string|int $id): self
    {
        return $this->deleteRequest(
            $id,
        );
    }

    /** update a single instance of the set path (e.g. patch a single producxt) */
    public function patch(array $payload, string|int $id): self
    {
        return $this->patchRequest(
            $payload,
            $id
        );
    }

    /** transition an order from one state to the next. can only be used for state transitions */
    public function transition(string|int $id, string $transition): self
    {
        return $this->postRequest(
            [],
            '_action/' . Str::replace('-', '_', $this->path) . '/' . $id . '/state/' . $transition
        );
    }

    /** a simple get. The parameter sets how many you'd like to get back */

    public function get(int $limit = 10)
    {
        $this->path .= '?limit=' . $limit . '&total-count-mode=1';
        return $this->getRequest();
    }

    /** sets the url that shopware should download an image from. Only works for images.  */

    public function url(string $url, string|int $id, $extension = 'jpg') : self
    {
        return $this->postRequest(
            ['url' => $url],
            '_action/' . $this->path . '/' . $id . '/upload?extension=' . $extension . '&_response=true',
        );
    }

    /** uploads an image file to the server */

    public function upload(array $payload, string|int $id, $extension = 'jpg') : self
    {
        return $this->uploadRequest(
            $payload['file'],
            '_action/' . $this->path . '/' . $id . '/upload?extension=' . $extension . '&_response=true',

        );
    }

    /** index the server. Should be run in case in other places you've not set */

    public function index() : self
    {
        $this->response = $this->client->addToPayload([
            'parameter' => 'skip[]'
        ])->sendRequest(
            '_action/index',
            'POST'
        );

        return $this;
    }

    /** search for an entry of the specific set path. You'll need to use the 'filter' function to specify what it should search for
     * you can also add associations using the 'associations' method. You can use the ShopSix::association helper
     */

    public function search(array $array = []) : self
    {
        return $this->postRequest(
            $array,
            'search/' . $this->path
        );
    }

    /** filter the search. You can use this to search for specific things. You can use the ShopSix::filter helper */

    public function filter(array $array): self
    {

        return count($array) > 0
            ? $this->simplePayloadAdd(['filter' => isset($array['type']) ? [$array] : $array])
            : $this;
    }

    public function sort(array $array): self
    {

        return $this->simplePayloadAdd(['sort' => [$array]]);

    }

    /** add associations to your search */

    public function association(array $array): self
    {
        return count($array) > 0
            ? $this->simplePayloadAdd(['associations' => $array])
            : $this;
    }

    /** returns a specific page in the result if there are multiple pages */

    public function page(int $int): self
    {
        return $this->simplePayloadAdd(
            ['page' => $int]
        );
    }

    /** limit the number of returned items */
    public function limit(int $int): self
    {
        return $this->simplePayloadAdd(
            ['limit' => $int]
        );
    }

    /** this method will give you the total count of the existing objects on the server */
    public function totalCount(): self
    {
        return $this->simplePayloadAdd(
            ['total-count-mode' => 1]
        );
    }
    /** this method lets you reduce the amount of data the server sends by seleecting specific fields */

    public function include(array $array): self
    {
        return count($array) > 0
            ? $this->simplePayloadAdd(['includes' => $array])
            : $this;
    }

    public function fields(array $array): self
    {
        return count($array) > 0
            ? $this->simplePayloadAdd(['fields' => $array])
            : $this;
    }

    /** response & client */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /** json decodes the body and returns it */

    /**
     * @return null|object{
     *     totalcount: int,
     *     data: array
     * }
     */

    public function body(array $remove = []): ?object
    {
        if (! $this->response ) return $this->response;

        $response = $this->response->getBody()->getContents();

        if ($this->path == 'media') $remove = array_merge($remove, ['\u0000*\u0000']);

        foreach ($remove as $key) {
            $response = str_replace($key, '', $response);
        }

        return json_decode($response);
    }

    /** returns the status code of the response */

    public function status(): ?int
    {
        return ($this->response)
            ? $this->response->getStatusCode()
            : null;
    }

    /** get the guzzle client */

    public function getClient(): Client
    {
        return $this->client;
    }

    protected function setPath(string $path): self
    {

        $this->path = $path;

        return $this;
    }

    /** Requests */
    protected function bulkPostRequest(array $payload): self
    {
        $this->response = $this->client
            ->setAlternativeHeader([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
//                    'indexing-behavior' => 'use-queue-indexing'
                ]
            ])->addToPayload([
                'write-' . $this->path => [
                    'entity' => Str::replace('-', '_', $this->path),
                    'action' => 'upsert',
                    'payload' => $payload
                ]
            ])->sendRequest(
                '_action/sync?_response=true',
                'POST'
            );

        return $this;
    }

    protected function bulkDeleteRequest(array $payload): self
    {
        $this->response = $this->client
            ->setAlternativeHeader([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/vnd.api+json',
                ]
            ])->addToPayload([
                'delete-' . $this->path => [
                    'entity' => Str::replace('-', '_', $this->path),
                    'action' => 'delete',
                    'payload' => $payload
                ]
            ])->sendRequest(
                '_action/sync',
                'POST'
            );

        return $this;
    }

    protected function getRequest(): self
    {
        $this->response = $this->client
            ->sendRequest(
                $this->path . '?_response=true'
            );
        return $this;
    }

    protected function patchRequest(array $payload, string|int $id): self
    {
        $this->response = $this->client
            ->addToPayload($payload)
            ->sendRequest(
                $this->path . '/' . $id . '?_response=true',
                'PATCH'
            );

        return $this;
    }

    protected function postRequest(array $payload, string $path): self
    {
        $this->response = $this->client
            ->addToPayload($payload)
            ->sendRequest(
                $path . '?_response=true',
                'POST'
            );

        return $this;
    }

    protected function uploadRequest($payload, string $path): self
    {

        $this->response = $this->client->sendRequest(
            $path,
            'POST',
            [
                'headers' => [
                    'Content-Type' => 'image/jpg', 'Accept' => 'application/json'
                ],

                'body' =>  $payload,
            ]
        );

        return $this;
    }

    protected function deleteRequest(string|int $id): self
    {

        $this->response = $this->client
            ->sendRequest(
                $this->path . '/' . $id,
                'DELETE'
            );

        return $this;
    }

    protected function simplePayloadAdd(array $add): self
    {
        $this->client->addToPayload(
            $add,
            'body'
        );

        return $this;
    }

    public function __call(string $method, ?array $args): self
    {
        if (method_exists($this, $method)) {
            return $this->{$method}(...$args);
        }

        $this->setPath($this->getPath($method));

        return $this;
    }

    private function getPath($method): string
    {
        return Str::kebab($this->structure[$method] ?? $method);
    }
}
