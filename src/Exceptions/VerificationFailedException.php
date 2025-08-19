<?php

declare(strict_types=1);

namespace PrivateCaptcha\Exceptions;

class VerificationFailedException extends PrivateCaptchaException
{
    public function __construct(
        string $message,
        public readonly int $attempts,
    ) {
        parent::__construct($message);
    }
}
