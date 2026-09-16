<?php

namespace Database\Seeders;

use App\Enums\Program;
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
        // [last, first, middle initial, mobile]
        $this->user(['Villanueva', 'Sofia', 'M', '0917 555 0101'], 'admin@acadvault.test', Role::Admin, $password);
        $this->user(['Reyes', 'Elena', 'B', '0917 555 0102'], 'registrar@acadvault.test', Role::Registrar, $password);
        $this->user(['Cruz', 'Ramon', 'T', '0917 555 0103'], 'faculty@acadvault.test', Role::Faculty, $password);

        $primaryStudent = $this->user(['Dela Cruz', 'Juan', 'P', '0917 555 0201'], 'student@acadvault.test', Role::Student, $password);
        $this->studentProfile($primaryStudent, '2026-01001', Program::ComputerScience, 3);

        // ---- Extra students, for list and pagination testing -----------
        $extras = [
            [['Bautista', 'Ana', 'L', '0917 555 0202'], 'ana.bautista@acadvault.test', '2026-01002', Program::InformationTechnology, 2],
            [['Santos', 'Miguel', 'R', '0917 555 0203'], 'miguel.santos@acadvault.test', '2026-01003', Program::ComputerScience, 4],
            [['Mendoza', 'Liza', 'A', '0917 555 0204'], 'liza.mendoza@acadvault.test', '2026-01004', Program::InformationTechnology, 1],
            [['Garcia', 'Paolo', 'D', '0917 555 0205'], 'paolo.garcia@acadvault.test', '2026-01005', Program::InformationTechnology, 3],
        ];

        foreach ($extras as [$parts, $email, $number, $program, $year]) {
            $user = $this->user($parts, $email, Role::Student, $password);
            $this->studentProfile($user, $number, $program, $year);
        }
    }

    /**
     * @param  array{0: string, 1: string, 2: ?string, 3: ?string}  $parts
     */
    private function user(array $parts, string $email, Role $role, string $password): User
    {
        [$last, $first, $initial, $phone] = $parts;

        // `name` is composed from the parts by the User model on save.
        return User::updateOrCreate(
            ['email' => $email],
            [
                'last_name' => $last,
                'first_name' => $first,
                'middle_initial' => $initial,
                'phone' => $phone,
                'role' => $role,
                'password' => $password,
                'email_verified_at' => now(),
            ],
        );
    }

    private function studentProfile(User $user, string $number, Program $program, int $year): Student
    {
        return Student::updateOrCreate(
            ['user_id' => $user->id],
            [
                'student_number' => $number,
                'program' => $program->value,
                'year_level' => $year,
            ],
        );
    }
}
