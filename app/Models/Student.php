<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'student_number', 'program', 'year_level'])]
class Student extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected static function booted(): void
    {
        // Keep enrolments and grades out of lists once the profile is
        // removed, but stay recoverable alongside it on force-delete the
        // database cascades take over.
        static::deleting(function (self $student) {
            if ($student->isForceDeleting()) {
                return;
            }

            $student->enrollments()->delete();
            $student->academicRecords()->delete();
        });
    }

    protected function casts(): array
    {
        return [
            'year_level' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function academicRecords(): HasMany
    {
        return $this->hasMany(AcademicRecord::class);
    }

    /**
     * Every enrollment this student has ever held, dropped ones included.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * The classes that appear on their certificate of registration.
     */
    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()->active();
    }

    public function enrollmentDocuments(): HasMany
    {
        return $this->hasMany(EnrollmentDocument::class);
    }

    /**
     * Convenience accessor so views can show the student's name directly.
     */
    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    /**
     * "3rd Year" style label for tables and PDFs.
     */
    public function yearLevelLabel(): string
    {
        return match ($this->year_level) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            default => $this->year_level.'th Year',
        };
    }
}
