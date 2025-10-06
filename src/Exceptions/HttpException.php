<?php

declare(strict_types=1);

namespace PrivateCaptcha\Exceptions;

class HttpException extends PrivateCaptchaException
{
    public int $statusCode;

    public function __construct(
        int $statusCode,
        string $message = '',
    ) {
        $this->statusCode = $statusCode;
        
        if ($message === '') {
            $message = "API returned HTTP status {$statusCode}";
        }

        parent::__construct($message);
    }
}
