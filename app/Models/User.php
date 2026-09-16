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

#[Fillable(['name', 'last_name', 'first_name', 'middle_initial', 'phone', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Keep `name` in step with the parts it is built from.
     *
     * `name` is what the whole app displays and what the document fingerprints
     * hash, so it stays the single source for reading. Accounts created before
     * the parts existed — and the seeders, which still pass a whole name — are
     * left alone.
     */
    protected static function booted(): void
    {
        static::saving(function (self $user) {
            if (blank($user->first_name) && blank($user->last_name)) {
                return;
            }

            // An explicitly supplied name wins, so callers that still set one
            // directly — seeders, factories, older code — are never overruled.
            if ($user->isDirty('name')) {
                return;
            }

            if ($user->isDirty(['first_name', 'middle_initial', 'last_name'])) {
                $user->name = $user->composedName();
            }
        });
    }

    /**
     * "Juan P. Dela Cruz" — the parts joined the way they are displayed.
     */
    public function composedName(): string
    {
        $initial = filled($this->middle_initial)
            ? strtoupper($this->middle_initial).'.'
            : null;

        return trim(implode(' ', array_filter([
            $this->first_name,
            $initial,
            $this->last_name,
        ])));
    }

    /**
     * "Dela Cruz, Juan P." — the way a registrar files it.
     */
    public function filedName(): string
    {
        $given = trim(implode(' ', array_filter([
            $this->first_name,
            filled($this->middle_initial) ? strtoupper($this->middle_initial).'.' : null,
        ])));

        return blank($this->last_name)
            ? ($given ?: $this->name)
            : trim($this->last_name.($given === '' ? '' : ", {$given}"));
    }

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
