<?php

namespace App\Shared\Services;

use App\Enums\ApprovalAction;
use App\Enums\AuditAction;
use App\Modules\Audit\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkflowService
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * @param  list<string>  $fromStatuses
     */
    public function transition(
        Model $model,
        string $toStatus,
        ApprovalAction $action,
        array $fromStatuses,
        ?string $comment = null,
        string $auditModule = 'workflow',
        bool $requireComment = false,
        bool $setApprovedMeta = false,
    ): Model {
        if ($requireComment && blank($comment)) {
            throw ValidationException::withMessages([
                'comment' => ['A comment or reason is required for this action.'],
            ]);
        }

        $current = $this->statusValue($model);

        if (! in_array($current, $fromStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot {$action->value} from status [{$current}]."],
            ]);
        }

        return DB::transaction(function () use ($model, $toStatus, $action, $comment, $auditModule, $setApprovedMeta, $current) {
            if (method_exists($model, 'recordApproval')) {
                $model->recordApproval(
                    $action,
                    $current,
                    $toStatus,
                    Auth::id(),
                    $comment,
                );
            }

            $model->status = $toStatus;

            if ($setApprovedMeta) {
                if (array_key_exists('approved_at', $model->getAttributes()) || $model->isFillable('approved_at')) {
                    $model->approved_at = now();
                }
                if (array_key_exists('approved_by', $model->getAttributes()) || $model->isFillable('approved_by')) {
                    $model->approved_by = Auth::id();
                }
            }

            $model->save();

            $this->auditService->log(
                AuditAction::Updated,
                $auditModule,
                $model::class,
                $model->getKey(),
                null,
                ['status' => $toStatus, 'action' => $action->value],
            );

            return $model->fresh();
        });
    }

    public function assertEditable(Model $model, array $editableStatuses = ['draft', 'returned', 'under_review']): void
    {
        $status = $this->statusValue($model);

        if (! in_array($status, $editableStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => ["Record in status [{$status}] cannot be modified. Create a new version instead."],
            ]);
        }
    }

    protected function statusValue(Model $model): string
    {
        $status = $model->getAttribute('status');

        return is_object($status) && property_exists($status, 'value')
            ? (string) $status->value
            : (string) $status;
    }
}
