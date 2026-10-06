<?php

namespace App\Models;

use App\Observers\AcademicRecordObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(AcademicRecordObserver::class)]
#[Fillable(['student_id', 'course_id', 'grade', 'remarks', 'created_by'])]
class AcademicRecord extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    /**
     * `grade` and `remarks` are encrypted at rest with AES-256-CBC via
     * Laravel's Encryption service (keyed by APP_KEY). Eloquent decrypts them
     * transparently on read, so the rest of the app works with plain values.
     *
     * Consequence: these two columns cannot be searched or sorted in SQL —
     * see AcademicRecordController::index(), which deliberately searches only
     * the student and course relations.
     */
    protected function casts(): array
    {
        return [
            'grade' => 'encrypted',
            'remarks' => 'encrypted',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * The user who entered this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exports(): HasMany
    {
        return $this->hasMany(Export::class);
    }
}
