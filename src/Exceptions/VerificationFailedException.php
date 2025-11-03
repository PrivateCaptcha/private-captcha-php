<?php

declare(strict_types=1);

namespace PrivateCaptcha\Exceptions;

class VerificationFailedException extends PrivateCaptchaException
{
    public int $attempts;
    public ?string $traceId;

    public function __construct(
        string $message,
        int $attempts,
        ?string $traceId = null,
    ) {
        $this->attempts = $attempts;
        $this->traceId = $traceId;
        parent::__construct($message);
    }
}
