<?php

declare(strict_types=1);

namespace PrivateCaptcha\Exceptions;

class HttpException extends PrivateCaptchaException
{
    public function __construct(
        public readonly int $statusCode,
        string $message = '',
    ) {
        if ($message === '') {
            $message = "API returned HTTP status {$statusCode}";
        }

        parent::__construct($message);
    }
}
