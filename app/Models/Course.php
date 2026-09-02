<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'title', 'faculty_id'])]
class Course extends Model
{
    use HasFactory;

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
