<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /** Attributes that must never be written to the audit log. */
    private const REDACTED = ['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'token'];

    public function log(
        string $action,
        ?Model $subject = null,
        array $old = [],
        array $new = [],
        ?string $reason = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'old_values' => $this->sanitise($old) ?: null,
            'new_values' => $this->sanitise($new) ?: null,
            'reason' => $reason,
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }

    /** Log the dirty attributes of a model that is about to be / was just saved. */
    public function logChanges(string $action, Model $model, ?string $reason = null): ?AuditLog
    {
        $changes = $model->getChanges() ?: $model->getDirty();
        if ($changes === []) {
            return null;
        }

        $old = array_intersect_key($model->getOriginal(), $changes);

        return $this->log($action, $model, $old, $changes, $reason);
    }

    private function sanitise(array $values): array
    {
        foreach (self::REDACTED as $key) {
            unset($values[$key]);
        }

        return array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $values);
    }
}
