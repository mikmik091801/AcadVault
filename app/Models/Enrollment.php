<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'course_id', 'status'])]
class Enrollment extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'drop_requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * The registrar who approved or declined the drop request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Classes that count towards the certificate — everything but a drop that
     * a registrar has already approved.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', EnrollmentStatus::Dropped);
    }

    public function scopeAwaitingReview(Builder $query): Builder
    {
        return $query->where('status', EnrollmentStatus::DropPending);
    }

    // -----------------------------------------------------------------
    // State
    // -----------------------------------------------------------------

    public function isEnrolled(): bool
    {
        return $this->status === EnrollmentStatus::Enrolled;
    }

    public function isDropPending(): bool
    {
        return $this->status === EnrollmentStatus::DropPending;
    }

    public function isDropped(): bool
    {
        return $this->status === EnrollmentStatus::Dropped;
    }
}
