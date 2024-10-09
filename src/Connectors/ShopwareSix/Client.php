<?php

namespace Go2Flow\Ezport\Connectors\ShopwareSix;

use Go2Flow\Ezport\Logger\LogOutput;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;

class Client
{
    const REFRESH_TOKEN_AFTER_MINUTES = 5;

    private Guzzle $client;
    private array $payload;
    private ?array $header;
    private ?Guzzle $testGuzzle;
    private ?object $response;
    private LogOutput $logger;

    public function __construct(private array $connector, ?Guzzle $testGuzzle = null)
    {
        $this->testGuzzle = $testGuzzle;
        $this->logger = (new LogOutput($connector['project_id']))->api();

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

    public function sendRequest($path, $method = 'GET', $content = null) : ?object
    {
        if (!Cache::has('shopware_six_token_' . $this->connector['project_id']) || !!$this->client) {
            $this->setupToken();
        }

        $report = [
            'method' => $method,
            'path' => $path,
            'content' => $content ?? $this->setPayload()
        ];

        try {
            $response = $this->client->request(
                $method,
                'api/' . $path,
                $content ?: $this->setPayload()
            );
        } catch (ClientException $e) {
            $this->convertAndLogError($e, $report);
        } catch (ServerException $e) {
            $this->convertAndLogError($e, $report);
        } catch (RequestException $e) {
            $this->convertAndLogError($e, $report);
        }
        catch (\Exception $e) {
            $this->convertAndLogError($e, $report);
        }
        $this->clearPayload();

        return $response ?? null;
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

    private function authenticate()
    {
        try {
            $this->response = ($this->testGuzzle ?: new Guzzle(['base_uri' => $this->connector['host']]))
                ->post(
                    '/api/oauth/token',
                    [
                        'headers' => ['Content-Type' => 'application/json'],
                        'body' => json_encode([
                            'grant_type' => 'client_credentials',
                            'client_id' => $this->connector['username'],
                            'client_secret' => $this->connector['password'],
                            'scopes' => 'write',
                        ])
                    ]
                );
        } catch (ClientException $e) {
            $this->logProblem(
                $e . "\n"
            );
        } catch (ServerException $e) {
            $this->logProblem(
                $e . "\n"
            );
        } catch (RequestException $e) {
            $this->logProblem(
                $e . "\n"
            );
        } catch (\Exception $e) {
            $this->logProblem(
                $e . "\n"
            );
        }

        return json_decode($this->response->getBody())->access_token;
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

    private function logProblem($problem) : void
    {
        $this->logger->log(
            $problem,
            'error'
        );
    }

    private function convertAndLogError($e, $report) : void
    {
        $report = 'report ' . json_encode($report);

        if ($e->hasResponse()) {

            $response = $e->getResponse();
            $this->logProblem(
                "HTTP error " . $response->getStatusCode() . ": " . $response->getReasonPhrase() . "\n"
                    . "Error body: " . $response->getBody() . "\n"
                    . $report
            );
        } else {
            $response = $e->getResponse();
            $response = json_decode($response->getBody()->getContents());

            $this->recursiveErrorLogger($response, $report);
        }
    }

    private function recursiveErrorLogger($response, $report) : void
    {

        !isset($response->errors)
            ? collect($response)->each(fn ($item) => is_array($item) ?  $this->logProblem($response . "\n" . $report) : '')
            : $this->logProblem(
                array_merge(get_object_vars($response), ['report' => $report])
            );
    }
}
