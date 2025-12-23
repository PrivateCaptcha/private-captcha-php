<?php

declare(strict_types=1);

namespace PrivateCaptcha\Enums;

enum VerifyCode: int
{
    case NO_ERROR = 0;
    case ERROR_OTHER = 1;
    case DUPLICATE_SOLUTIONS_ERROR = 2;
    case INVALID_SOLUTION_ERROR = 3;
    case PARSE_RESPONSE_ERROR = 4;
    case PUZZLE_EXPIRED_ERROR = 5;
    case INVALID_PROPERTY_ERROR = 6;
    case WRONG_OWNER_ERROR = 7;
    case VERIFIED_BEFORE_ERROR = 8;
    case MAINTENANCE_MODE_ERROR = 9;
    case TEST_PROPERTY_ERROR = 10;
    case INTEGRITY_ERROR = 11;
    case ORG_SCOPE_ERROR = 12;

    public function toString(): string
    {
        return match ($this) {
            self::NO_ERROR => '',
            self::ERROR_OTHER => 'error-other',
            self::DUPLICATE_SOLUTIONS_ERROR => 'solution-duplicates',
            self::INVALID_SOLUTION_ERROR => 'solution-invalid',
            self::PARSE_RESPONSE_ERROR => 'solution-bad-format',
            self::PUZZLE_EXPIRED_ERROR => 'puzzle-expired',
            self::INVALID_PROPERTY_ERROR => 'property-invalid',
            self::WRONG_OWNER_ERROR => 'property-owner-mismatch',
            self::VERIFIED_BEFORE_ERROR => 'solution-verified-before',
            self::MAINTENANCE_MODE_ERROR => 'maintenance-mode',
            self::TEST_PROPERTY_ERROR => 'property-test',
            self::INTEGRITY_ERROR => 'integrity-error',
            self::ORG_SCOPE_ERROR => 'org-scope-error',
        };
    }

}
