<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['academic_record_id', 'exported_by', 'file_hash', 'qr_code_path'])]
class Export extends Model
{
    use HasFactory, HasUuids;

    /**
     * Auto-fill `uuid` while keeping the auto-increment `id` as primary key.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * /verify/{export} resolves by UUID, not by the sequential id.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function academicRecord(): BelongsTo
    {
        return $this->belongsTo(AcademicRecord::class);
    }

    /**
     * The user who generated this export.
     */
    public function exporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by');
    }
}
