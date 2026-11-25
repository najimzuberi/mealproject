<?php

namespace App\Modules\Audit\Services;

use App\Enums\AuditAction;
use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Audit\Models\LoginLog;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AuditService
{
    /** @var list<string> */
    protected array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'remember_token',
        'api_token',
    ];

    public function __construct(
        protected OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public function log(
        AuditAction|string $action,
        ?string $module = null,
        ?string $entityType = null,
        int|string|null $entityId = null,
        ?array $old = null,
        ?array $new = null,
        ?User $user = null,
        ?Request $request = null,
    ): AuditLog {
        $actionValue = $action instanceof AuditAction ? $action->value : $action;

        return AuditLog::query()->create([
            'organization_id' => $this->organizationContext->id(),
            'user_id' => $user?->id ?? auth()->id(),
            'action' => $actionValue,
            'module' => $module,
            'entity_type' => $entityType,
            'entity_id' => $entityId !== null ? (string) $entityId : null,
            'old_values' => $this->sanitize($old),
            'new_values' => $this->sanitize($new),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function logLogin(
        User $user,
        string $event,
        ?int $organizationId = null,
        ?Request $request = null,
    ): LoginLog {
        return LoginLog::query()->create([
            'user_id' => $user->id,
            'organization_id' => $organizationId,
            'email' => $user->email,
            'event' => $event,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @return array<string, mixed>|null
     */
    protected function sanitize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        return Arr::except($data, $this->sensitiveKeys);
    }
}
