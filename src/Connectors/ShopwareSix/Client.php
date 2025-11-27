<?php

namespace Go2Flow\Ezport\Connectors\ShopwareSix;

use Go2Flow\Ezport\Logger\LogError;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;

class Client
{
    const REFRESH_TOKEN_AFTER_MINUTES = 5;

    private ?Guzzle $client;
    private array $payload;
    private ?array $header;
    private ?Guzzle $testGuzzle;
    private ?object $response;
    private LogError $logger;

    public function __construct(private array $connector, ?Guzzle $testGuzzle = null)
    {
        $this->client = null;
        $this->testGuzzle = $testGuzzle;
        $this->logger = (new LogError($connector['project_id']))->type('api');

        $this->clearPayload();
    }

    public function setupToken()
    {
        $this->setToken(
            decrypt(
                Cache::remember(
                    'shopware_six_token_' . $this->connector['project_id'],
                    self::REFRESH_TOKEN_AFTER_MINUTES * 60,
                    fn () => encrypt($this->authenticate())
                )
            )
        );

        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function addToPayload(string|array $array, string $field = 'body') : self
    {
        if (isset($this->payload[$field])) {
            $array += $this->payload[$field];
        }

        $this->payload[$field] = $array;

        return $this;
    }

    public function clearPayload() : self
    {
        $this->payload = [];
        $this->header = null;

        return $this;
    }

    public function sendRequest($path, $method = 'GET', $content = null): ?object
    {
        if (! Cache::has('shopware_six_token_' . $this->connector['project_id']) || ! $this->client) {
            $this->setupToken();
        }
        $payload = $content ?: $this->setPayload();

        $report = [
            'method'  => $method,
            'path'    => $path,
            'content' => $payload,
        ];

        $maxAttempts = 5;
        $attempt     = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $response = $this->client->request(
                    $method,
                    'api/' . $path,
                    $payload
                );

                $this->clearPayload();

                return $response;

            } catch (ClientException|ServerException|RequestException|\Exception $e) {

                if ($this->isDeadlockException($e) && $attempt < $maxAttempts) {

                    usleep(200_000 * $attempt);
                    continue;
                }

                $this->convertAndLogError($e, $report);
                break;
            }
        }

        $this->clearPayload();

        return null;
    }

    public function setAlternativeHeader(array $array) : self
    {
        $this->header = $array;

        return $this;
    }

    private function setPayload() : array
    {

        $payload = $this->header ?: [
            'headers' => [
                'Content-Type' => 'application/json', 'Accept' => 'application/json'
            ]
        ];

        foreach ($this->payload as $key => $content) {
            $payload[$key] = json_encode($content);
        }

        return $payload;
    }

    private function isDeadlockException(\Throwable $e): bool
    {
        $message = $e->getMessage();

        if (str_contains($message, 'SQLSTATE[40001]')
            || str_contains($message, '1213 Deadlock')
            || str_contains($message, 'deadlock')
        ) {
            return true;
        }

        if (method_exists($e, 'getResponse') && $response = $e->getResponse()) {
            $body = (string) $response->getBody();

            return str_contains($body, 'SQLSTATE[40001]')
                || str_contains($body, '1213 Deadlock')
                || str_contains($body, 'Deadlock found when trying to get lock');
        }

        return false;
    }

    private function authenticate()
    {
        try {
            $this->response = ($this->testGuzzle ?: new Guzzle(['base_uri' => $this->connector['host']]))
                ->post('/api/oauth/token', [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body' => json_encode([
                        'grant_type'    => 'client_credentials',
                        'client_id'     => $this->connector['username'],
                        'client_secret' => $this->connector['password'],
                        'scopes'        => 'write',
                    ]),
                ]);
        } catch (\Throwable $e) {
            $this->logProblem((string) $e . "\n");
            throw new \RuntimeException('Unable to authenticate to Shopware', 0, $e);
        }

        $data = json_decode($this->response->getBody()->getContents(), false);

        if (! $data || empty($data->access_token)) {
            throw new \RuntimeException('Shopware auth response did not contain an access_token');
        }

        return $data->access_token;
    }

    private function setToken($token)
    {
        $this->client = $this->testGuzzle ?: new Guzzle([
            'base_uri' => $this->connector['host'],
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ]);

        return $this;
    }

    private function logProblem(string $problem) : void
    {
        $this->logger->level('high')->log(
            $problem,
        );
    }

    private function convertAndLogError(\Throwable $e, array $report): void
    {
        $reportJson = 'report ' . json_encode($report);

        if (method_exists($e, 'getResponse') && $response = $e->getResponse()) {
            $body = (string) $response->getBody();

            $this->logProblem(
                "HTTP error {$response->getStatusCode()}: {$response->getReasonPhrase()}\n"
                . "Error body: {$body}\n"
                . $reportJson
            );

            $decoded = json_decode($body);
            if ($decoded) {
                $this->recursiveErrorLogger($decoded, $reportJson);
            }

            return;
        }

        $this->logProblem(
            "Exception: " . $e::class . " - " . $e->getMessage() . "\n" . $reportJson
        );
    }

    private function recursiveErrorLogger($response, string $report): void
    {
        if (isset($response->errors) && is_array($response->errors)) {
            foreach ($response->errors as $error) {
                $this->logProblem(
                    json_encode(array_merge((array) $error, ['report' => $report]))
                );
            }

            return;
        }

        $this->logProblem(
            json_encode(array_merge((array) $response, ['report' => $report]))
        );
    }
}
