<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TenantContextUnresolvedException;
use App\Models\Organization;

final class TenantContext
{
    private ?Organization $organization = null;

    public function set(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function get(): ?Organization
    {
        return $this->organization;
    }

    public function id(): ?int
    {
        return $this->organization?->id;
    }

    public function clear(): void
    {
        $this->organization = null;
    }

    public function isResolved(): bool
    {
        return $this->organization !== null;
    }

    public function requireId(): int
    {
        $id = $this->id();

        if ($id === null) {
            throw new TenantContextUnresolvedException(
                'Organization context is required but was not resolved for this request.',
            );
        }

        return $id;
    }
}
