<?php

declare(strict_types=1);

namespace PrivateCaptcha\Exceptions;

class RetriableHttpException extends RetriableException
{
    public int $statusCode;
    public int $retryAfter;

    public function __construct(
        int $statusCode,
        int $retryAfter = 0,
        string $message = '',
    ) {
        $this->statusCode = $statusCode;
        $this->retryAfter = $retryAfter;
        
        if ($message === '') {
            $message = "API returned HTTP status {$statusCode}";
        }

        parent::__construct($message);
    }
}
