<?php

declare(strict_types=1);

namespace PrivateCaptcha\Models;

use PrivateCaptcha\Enums\VerifyCode;

readonly class VerifyOutput
{
    public function __construct(
        public bool $success,
        public VerifyCode $code,
        public ?string $origin = null,
        public ?string $timestamp = null,
        private ?string $requestId = null,
        private ?int $attempt = null,
    ) {
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getAttempt(): ?int
    {
        return $this->attempt;
    }

    public function __toString(): string
    {
        return $this->code->toString();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, ?string $requestId = null, ?int $attempt = null): self
    {
        return new self(
            success: (bool) ($data['success'] ?? false),
            code: VerifyCode::tryFrom($data['code'] ?? VerifyCode::ERROR_OTHER->value) ?? VerifyCode::ERROR_OTHER,
            origin: $data['origin'] ?? null,
            timestamp: $data['timestamp'] ?? null,
            requestId: $requestId,
            attempt: $attempt,
        );
    }
}
