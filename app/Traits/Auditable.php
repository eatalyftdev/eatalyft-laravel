<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    protected static function getAuditExcludeAttributes(): array
    {
        return [
            'password',
            'remember_token',
            'fcm_token',
            'kyc_status',
            'kyc_tier',
            'kyc_verified_at',
            'firebase_uid',
            'public_id',
            'device_tokens',
        ];
    }

    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            if ($model->shouldAudit('created')) {
                $model->auditLog('created');
            }
        });

        static::updated(function ($model) {
            if ($model->shouldAudit('updated')) {
                $model->auditLog('updated');
            }
        });

        static::deleted(function ($model) {
            if ($model->shouldAudit('deleted')) {
                $model->auditLog('deleted');
            }
        });
    }

    protected function shouldAudit(string $action): bool
    {
        return true;
    }

    protected function getAuditAttributes(string $action): array
    {
        $attributes = $this->getAttributes();

        if ($action === 'updated') {
            $attributes = $this->getDirty();
        }

        $excluded = array_merge(
            static::getAuditExcludeAttributes(),
            $this->getAdditionalAuditExcludeAttributes()
        );

        foreach ($excluded as $attr) {
            unset($attributes[$attr]);
        }

        return $attributes;
    }

    protected function getAdditionalAuditExcludeAttributes(): array
    {
        return [];
    }

    protected function auditLog(string $action): void
    {
        if ($this instanceof AuditLog) {
            return;
        }

        $actor = Auth::user();
        $details = $this->getAuditAttributes($action);

        if (empty($details) && $action === 'updated') {
            return;
        }

        AuditLog::create([
            'action' => $action,
            'actor_id' => $actor?->id,
            'target_id' => (string) $this->getKey(),
            'target_type' => get_class($this),
            'details' => $details,
        ]);
    }
}