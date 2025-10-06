<?php

declare(strict_types=1);

namespace PrivateCaptcha\Exceptions;

class VerificationFailedException extends PrivateCaptchaException
{
    public int $attempts;

    public function __construct(
        string $message,
        int $attempts,
    ) {
        $this->attempts = $attempts;
        parent::__construct($message);
    }
}
