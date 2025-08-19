<?php

declare(strict_types=1);

namespace PrivateCaptcha\Exceptions;

class RetriableHttpException extends RetriableException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly int $retryAfter = 0,
        string $message = '',
    ) {
        if ($message === '') {
            $message = "API returned HTTP status {$statusCode}";
        }
        
        parent::__construct($message);
    }
}
