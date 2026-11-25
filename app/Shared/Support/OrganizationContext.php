<?php

namespace App\Shared\Support;

use App\Modules\Organization\Models\Organization;

class OrganizationContext
{
    protected ?Organization $organization = null;

    public function set(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function organization(): ?Organization
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
}
