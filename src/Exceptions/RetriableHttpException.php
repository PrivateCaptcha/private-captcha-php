<?php

declare(strict_types=1);

namespace PrivateCaptcha\Exceptions;

class RetriableHttpException extends RetriableException
{
    public int $statusCode;
    public int $retryAfter;
    public ?string $traceId;

    public function __construct(
        int $statusCode,
        int $retryAfter = 0,
        ?string $traceId = null,
        string $message = '',
    ) {
        $this->statusCode = $statusCode;
        $this->retryAfter = $retryAfter;
        $this->traceId = $traceId;

        if ($message === '') {
            $message = "API returned HTTP status {$statusCode}";
        }

        parent::__construct($message);
    }
}
