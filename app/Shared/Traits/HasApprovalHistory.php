<?php

namespace App\Shared\Traits;

use App\Enums\ApprovalAction;
use App\Shared\Models\ApprovalHistory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasApprovalHistory
{
    public function approvalHistories(): MorphMany
    {
        return $this->morphMany(ApprovalHistory::class, 'approvable');
    }

    public function recordApproval(
        ApprovalAction|string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?int $userId = null,
        ?string $comment = null,
    ): ApprovalHistory {
        $actionValue = $action instanceof ApprovalAction ? $action->value : $action;

        return $this->approvalHistories()->create([
            'organization_id' => $this->organization_id,
            'action' => $actionValue,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'user_id' => $userId,
            'comment' => $comment,
        ]);
    }
}
