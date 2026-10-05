<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['user_id', 'action', 'target_type', 'target_id', 'ip_address'])]
class AuditLog extends Model
{
    use HasFactory;

    /**
     * Entries are append-only: once written, the application can neither
     * change nor remove one.
     */
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log entries are append-only and cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit log entries are append-only and cannot be deleted.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human label for whatever was acted on.
     *
     * Model targets read as "AcademicRecord #12"; non-model targets (a blocked
     * URL, an attempted email address) are stored as a plain string with no id
     * and are shown verbatim.
     */
    public function targetLabel(): ?string
    {
        if (! $this->target_type) {
            return null;
        }

        if ($this->target_id === null) {
            return $this->target_type;
        }

        return class_basename($this->target_type).' #'.$this->target_id;
    }
}
