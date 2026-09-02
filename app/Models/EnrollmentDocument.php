<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'issued_by', 'file_hash', 'qr_code_path', 'class_count'])]
class EnrollmentDocument extends Model
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
     * Verification resolves by UUID, not by the sequential id.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * The user who generated the certificate — normally the student.
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
