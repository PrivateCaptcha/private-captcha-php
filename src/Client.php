<?php

declare(strict_types=1);

namespace PrivateCaptcha;

use JsonException;
use PrivateCaptcha\Exceptions\ApiKeyException;
use PrivateCaptcha\Exceptions\PrivateCaptchaException;
use PrivateCaptcha\Exceptions\RetriableException;
use PrivateCaptcha\Exceptions\RetriableHttpException;
use PrivateCaptcha\Exceptions\SolutionException;
use PrivateCaptcha\Exceptions\VerificationFailedException;
use PrivateCaptcha\Exceptions\HttpException;
use PrivateCaptcha\Models\VerifyOutput;

class Client
{
    public const GLOBAL_DOMAIN = 'api.privatecaptcha.com';
    public const EU_DOMAIN = 'api.eu.privatecaptcha.com';
    public const DEFAULT_FORM_FIELD = 'private-captcha-solution';
    public const VERSION = '0.0.9';
    public const MIN_BACKOFF_MILLIS = 250;

    private const STRICT_ARRAY_SEARCH = true;
    private const JSON_DECODE_MAX_DEPTH = 512;

    private const RETRIABLE_STATUS_CODES = [
        429, // Too Many Requests
        500, // Internal Server Error
        502, // Bad Gateway
        503, // Service Unavailable
        504, // Gateway Timeout
        408, // Request Timeout
        425, // Too Early
    ];

    private string $endpoint;
    private string $domain;
    private string $apiKey;
    public string $formField;
    private ?int $timeout;

    public function __construct(
        string $apiKey,
        ?string $domain = null,
        string $formField = self::DEFAULT_FORM_FIELD,
        ?int $timeout = null,
    ) {
        $this->apiKey = $apiKey;
        $this->formField = $formField;
        $this->timeout = $timeout;
        if (empty($this->apiKey)) {
            throw new ApiKeyException('API key is empty');
        }

        $domain = $domain ?? self::GLOBAL_DOMAIN;

        // Remove https:// or http:// prefix
        if (str_starts_with($domain, 'https://')) {
            $domain = substr($domain, 8);
        } elseif (str_starts_with($domain, 'http://')) {
            $domain = substr($domain, 7);
        }

        $domain = rtrim($domain, '/');

        $this->domain = $domain;
        $this->endpoint = "https://{$domain}/verify";
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @return array{0: array<string, mixed>, 1: ?string}
     * @throws RetriableException|RetriableHttpException
     */
    private function doVerify(string $solution, ?string $sitekey = null): array
    {
        $curl = curl_init();

        // this is just for PHP static analyzer to shut up
        assert($this->endpoint !== '');

        $headers = [
            'X-Api-Key: ' . $this->apiKey,
            'Content-Type: text/plain',
            'User-Agent: private-captcha-php/' . self::VERSION,
        ];

        if ($sitekey !== null) {
            $headers[] = 'X-PC-Sitekey: ' . $sitekey;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $solution,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => $this->timeout ?? 30,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($response === false || $error !== '') {
            throw new RetriableException("cURL error: {$error}");
        }

        // At this point we know $response is string, not false
        assert(is_string($response));

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // Extract trace ID from headers
        $traceId = $this->extractHeaderValue($headers, 'X-Trace-ID');

        if (in_array($httpCode, self::RETRIABLE_STATUS_CODES, self::STRICT_ARRAY_SEARCH)) {
            $retryAfter = 0;
            if ($httpCode === 429) {
                $retryAfterValue = $this->extractHeaderValue($headers, 'Retry-After');
                if ($retryAfterValue !== null && ctype_digit($retryAfterValue)) {
                    $retryAfter = (int) $retryAfterValue;
                }
            }
            throw new RetriableHttpException($httpCode, $retryAfter, $traceId);
        }

        if ($httpCode >= 400) {
            throw new HttpException($httpCode, $traceId);
        }

        try {
            $data = json_decode($body, true, self::JSON_DECODE_MAX_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RetriableException("Invalid JSON response: {$e->getMessage()}");
        }

        return [$data, $traceId];
    }

    public function verify(
        string $solution,
        int $maxBackoffSeconds = 20,
        int $attempts = 5,
        ?string $sitekey = null,
    ): VerifyOutput {
        if (empty($solution)) {
            throw new SolutionException('Solution is empty');
        }

        if ($attempts <= 0) {
            $attempts = 5;
        }

        if ($maxBackoffSeconds <= 0) {
            $maxBackoffSeconds = 20;
        }

        $minBackoff = self::MIN_BACKOFF_MILLIS / 1000.0;
        $maxBackoff = (float) $maxBackoffSeconds;
        $backoffFactor = 2.0;

        $currentBackoff = $minBackoff;
        $lastException = null;

        for ($i = 0; $i < $attempts; $i++) {
            if ($i > 0) {
                $sleepDuration = $currentBackoff;

                if ($lastException instanceof RetriableHttpException && $lastException->retryAfter > 0) {
                    $sleepDuration = max($sleepDuration, (float) $lastException->retryAfter);
                }

                usleep((int) (min($sleepDuration, $maxBackoff) * 1_000_000));
                $currentBackoff = min($maxBackoff, $currentBackoff * $backoffFactor);
            }

            try {
                [$responseData, $traceId] = $this->doVerify($solution, $sitekey);

                return VerifyOutput::fromArray(
                    $responseData,
                    requestId: $traceId,
                    attempt: $i + 1,
                );
            } catch (RetriableException $e) {
                $lastException = $e;
                continue;
            }
        }

        $traceId = null;
        if ($lastException instanceof RetriableHttpException) {
            $traceId = $lastException->traceId;
        }

        throw new VerificationFailedException(
            "Failed to verify solution after {$attempts} attempts",
            $attempts,
            $traceId,
        );
    }

    /**
     * @param array<string, mixed> $formData
     * @throws SolutionException
     */
    public function verifyRequest(array $formData): void
    {
        $solution = $formData[$this->formField] ?? null;

        if (!is_string($solution)) {
            throw new SolutionException('Solution not found in form data');
        }

        $output = $this->verify($solution);

        if (!$output->isOK()) {
            throw new SolutionException("Captcha verification failed: {$output}");
        }
    }

    private function extractHeaderValue(string $headers, string $headerName): ?string
    {
        $headerLines = explode("\r\n", $headers);
        $searchHeader = strtolower($headerName) . ':';
        $searchHeaderLength = strlen($searchHeader);

        foreach ($headerLines as $line) {
            $line = trim($line);
            if (stripos($line, $searchHeader) === 0) {
                $value = substr($line, $searchHeaderLength);
                return trim($value) ?: null;
            }
        }

        return null;
    }
}
