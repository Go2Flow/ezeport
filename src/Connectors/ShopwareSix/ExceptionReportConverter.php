<?php

namespace Go2Flow\Ezport\Connectors\ShopwareSix;

class ExceptionReportConverter
{
    private array $messages;


    public function __construct() {
        $this->messages = [];

    }

    public function toLogMessages(\Throwable $e, array $report): array
    {
        $reportJson = $this->reportToString($report);

        $response = $this->extractResponse($e);

        if ($response) {
            $status = $response['status'];
            $reason = $response['reason'];
            $body   = $response['body'];

             $this->messages = [
                "HTTP error {$status}: {$reason}\n"
                . "Error body: {$body}\n"
                . $reportJson
             ];

            foreach ($this->extractNestedErrorMessages($body, $reportJson) as $msg) {
                $this->messages[] = $msg;
            }

        } else {

            $this->messages[] = "Exception: " . $e::class . " - " . $e->getMessage() . "\n" . $reportJson;
        }

        return $this->messages;
    }

    public function getMessages(): array {
        return $this->messages;
    }

    private function reportToString(array $report): string
    {
        // keep your existing prefix format
        return 'report ' . json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array{status:int, reason:string, body:string}|null
     */
    private function extractResponse(\Throwable $e): ?array
    {
        if (! method_exists($e, 'getResponse')) {
            return null;
        }

        $response = $e->getResponse();
        if (! $response) {
            return null;
        }

        // Works for Guzzle PSR-7 responses
        $body = '';
        try {
            $body = (string) $response->getBody();
        } catch (\Throwable) {
            $body = '[unable to read response body]';
        }

        $status = (int) ($response->getStatusCode() ?? 0);
        $reason = (string) ($response->getReasonPhrase() ?? '');

        return [
            'status' => $status,
            'reason' => $reason,
            'body'   => $body,
        ];
    }

    /**
     * @return list<string>
     */
    private function extractNestedErrorMessages(string $body, string $reportJson): array
    {
        $decoded = json_decode($body);

        if (! $decoded) {
            return [];
        }

        return $this->flattenDecodedErrors($decoded, $reportJson);
    }

    /**
     * Keeps your old behavior:
     * - if response->errors is array, log each error as separate line with report
     * - otherwise log the decoded object merged with report
     *
     * @return list<string>
     */
    private function flattenDecodedErrors(object $decoded, string $reportJson): array
    {
        if (isset($decoded->errors) && is_array($decoded->errors)) {
            $messages = [];

            foreach ($decoded->errors as $error) {
                $messages[] = json_encode(
                    array_merge((array) $error, ['report' => $reportJson]),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            }

            return $messages;
        }

        return [
            json_encode(
                array_merge((array) $decoded, ['report' => $reportJson]),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
        ];
    }
}
