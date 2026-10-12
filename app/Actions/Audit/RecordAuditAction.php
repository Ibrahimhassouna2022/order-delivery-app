<?php

namespace App\Actions\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes an append-only audit record for a sensitive operation (FR-030,
 * research R-13). Audit rows survive the deletion of their actor/subject.
 */
class RecordAuditAction
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        string $action,
        Model $subject,
        ?User $actor = null,
        array $metadata = [],
    ): AuditLog {
        return AuditLog::create([
            'actor_user_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => (string) $subject->getKey(),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
