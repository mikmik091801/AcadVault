<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /**
     * The student profile, when this user is a student.
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Courses this user teaches, when they are faculty.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'faculty_id');
    }

    /**
     * Academic records this user entered.
     */
    public function createdRecords(): HasMany
    {
        return $this->hasMany(AcademicRecord::class, 'created_by');
    }

    /**
     * Exports this user generated.
     */
    public function exports(): HasMany
    {
        return $this->hasMany(Export::class, 'exported_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // -----------------------------------------------------------------
    // Role helpers
    // -----------------------------------------------------------------

    /**
     * Does the user hold any of the given roles?
     */
    public function hasRole(Role|string ...$roles): bool
    {
        foreach ($roles as $role) {
            $value = $role instanceof Role ? $role : Role::tryFrom($role);

            if ($value !== null && $this->role === $value) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isRegistrar(): bool
    {
        return $this->role === Role::Registrar;
    }

    public function isFaculty(): bool
    {
        return $this->role === Role::Faculty;
    }

    public function isStudent(): bool
    {
        return $this->role === Role::Student;
    }
}
