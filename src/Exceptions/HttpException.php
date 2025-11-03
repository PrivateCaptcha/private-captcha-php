<?php

declare(strict_types=1);

namespace PrivateCaptcha\Exceptions;

class HttpException extends PrivateCaptchaException
{
    public int $statusCode;
    public ?string $traceId;

    public function __construct(
        int $statusCode,
        ?string $traceId = null,
        string $message = '',
    ) {
        $this->statusCode = $statusCode;
        $this->traceId = $traceId;

        if ($message === '') {
            $message = "API returned HTTP status {$statusCode}";
        }

        parent::__construct($message);
    }
}
