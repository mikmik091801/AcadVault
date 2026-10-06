<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'title', 'faculty_id', 'program', 'year_level', 'semester', 'units'])]
class Course extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (self $course) {
            if ($course->isForceDeleting()) {
                return;
            }

            $course->enrollments()->delete();
            $course->academicRecords()->delete();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year_level' => 'integer',
            'semester' => 'integer',
            'units' => 'integer',
        ];
    }

    /**
     * Subjects a student may enroll in: the ones in their own program and year,
     * plus any subject not yet mapped to a curriculum, which stays open to all.
     */
    public function scopeInCurriculumFor(Builder $query, Student $student): Builder
    {
        return $query
            ->where(function (Builder $program) use ($student) {
                $program->whereNull('program')
                    ->orWhere('program', $student->program);
            })
            ->where(function (Builder $year) use ($student) {
                $year->whereNull('year_level')
                    ->orWhere('year_level', $student->year_level);
            });
    }

    /**
     * "1st Semester" style label for tables and the enrollment portal.
     */
    public function semesterLabel(): ?string
    {
        return match ($this->semester) {
            1 => '1st Semester',
            2 => '2nd Semester',
            3 => 'Summer',
            default => null,
        };
    }

    /**
     * The faculty member teaching this course.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function academicRecords(): HasMany
    {
        return $this->hasMany(AcademicRecord::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * The class list — everyone holding a place, dropped students excluded.
     * A pending drop still counts: they have not left yet.
     */
    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()->active();
    }
}
