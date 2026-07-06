<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomDomainVerificationStatus: string
{
    case Unverified = 'unverified';
    case Pending = 'pending';
    case Verified = 'verified';
}
