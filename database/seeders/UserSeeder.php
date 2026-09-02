<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * One demo account per role, plus a few extra students so the record and
     * student tables have enough rows to exercise search and pagination.
     *
     * Passwords are passed as PLAIN strings: the User model's `hashed` cast
     * hashes them with Argon2id. Calling bcrypt() here would throw, because
     * the cast rejects a hash that is not made by the configured driver.
     */
    public function run(): void
    {
        $password = 'password';

        // ---- One account per role -------------------------------------
        $this->user('Sofia Villanueva', 'admin@acadvault.test', Role::Admin, $password);
        $this->user('Elena Reyes', 'registrar@acadvault.test', Role::Registrar, $password);
        $this->user('Dr. Ramon Cruz', 'faculty@acadvault.test', Role::Faculty, $password);

        $primaryStudent = $this->user('Juan Dela Cruz', 'student@acadvault.test', Role::Student, $password);
        $this->studentProfile($primaryStudent, '2026-01001', 'BS Computer Science', 3);

        // ---- Extra students, for list and pagination testing -----------
        $extras = [
            ['Ana Bautista', 'ana.bautista@acadvault.test', '2026-01002', 'BS Information Technology', 2],
            ['Miguel Santos', 'miguel.santos@acadvault.test', '2026-01003', 'BS Computer Science', 4],
            ['Liza Mendoza', 'liza.mendoza@acadvault.test', '2026-01004', 'BS Information Systems', 1],
            ['Paolo Garcia', 'paolo.garcia@acadvault.test', '2026-01005', 'BS Information Technology', 3],
        ];

        foreach ($extras as [$name, $email, $number, $program, $year]) {
            $user = $this->user($name, $email, Role::Student, $password);
            $this->studentProfile($user, $number, $program, $year);
        }
    }

    private function user(string $name, string $email, Role $role, string $password): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'role' => $role,
                'password' => $password,
                'email_verified_at' => now(),
            ],
        );
    }

    private function studentProfile(User $user, string $number, string $program, int $year): Student
    {
        return Student::updateOrCreate(
            ['user_id' => $user->id],
            [
                'student_number' => $number,
                'program' => $program,
                'year_level' => $year,
            ],
        );
    }
}
