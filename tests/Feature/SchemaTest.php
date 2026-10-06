<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AcademicRecord;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Export;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_is_cast_to_the_role_enum(): void
    {
        $user = User::factory()->registrar()->create();

        $this->assertInstanceOf(Role::class, $user->fresh()->role);
        $this->assertSame(Role::Registrar, $user->fresh()->role);
        $this->assertSame('Registrar', $user->role->label());
    }

    public function test_new_users_default_to_the_student_role(): void
    {
        $user = User::create([
            'name' => 'Default Person',
            'email' => 'default@acadvault.test',
            'password' => 'secret-password',
        ]);

        $this->assertSame(Role::Student, $user->fresh()->role);
    }

    public function test_role_helpers_answer_correctly(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isStudent());
        $this->assertTrue($admin->hasRole(Role::Admin, Role::Registrar));
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertFalse($admin->hasRole('faculty'));
    }

    public function test_a_student_profile_belongs_to_a_user(): void
    {
        $student = Student::factory()->create([
            'program' => 'BS Computer Science',
            'year_level' => 3,
        ]);

        $this->assertSame(Role::Student, $student->user->role);
        $this->assertSame($student->id, $student->user->student->id);
        $this->assertSame('3rd Year', $student->yearLevelLabel());
        $this->assertSame($student->user->name, $student->name);
    }

    public function test_a_course_belongs_to_a_faculty_member(): void
    {
        $course = Course::factory()->create(['code' => 'CS101', 'title' => 'Intro to Computing']);

        $this->assertSame(Role::Faculty, $course->faculty->role);
        $this->assertTrue($course->faculty->courses->contains($course));
    }

    public function test_an_academic_record_links_student_course_and_creator(): void
    {
        $record = AcademicRecord::factory()->create(['grade' => '1.75', 'remarks' => 'Passed']);

        $this->assertInstanceOf(Student::class, $record->student);
        $this->assertInstanceOf(Course::class, $record->course);
        $this->assertSame(Role::Registrar, $record->creator->role);
        $this->assertTrue($record->student->academicRecords->contains($record));
        $this->assertTrue($record->course->academicRecords->contains($record));
    }

    public function test_an_export_gets_a_uuid_and_is_routed_by_it(): void
    {
        $export = Export::factory()->create();

        $this->assertNotNull($export->uuid);
        $this->assertSame(36, strlen($export->uuid));
        $this->assertSame('uuid', $export->getRouteKeyName());
        $this->assertSame($export->uuid, $export->getRouteKey());
        $this->assertInstanceOf(AcademicRecord::class, $export->academicRecord);
    }

    public function test_deleting_a_student_user_cascades_to_records_but_keeps_audit_logs(): void
    {
        $record = AcademicRecord::factory()->create();
        $student = $record->student;
        $user = $student->user;

        AuditLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'record.viewed',
        ]);

        $user->delete();

        // Profile and records go with the account (soft-deleted, recoverable)...
        $this->assertSoftDeleted('students', ['id' => $student->id]);
        $this->assertSoftDeleted('academic_records', ['id' => $record->id]);

        // ...but the audit trail is retained with the user detached.
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'record.viewed',
            'user_id' => null,
        ]);
    }

    public function test_deleting_a_faculty_member_keeps_the_course(): void
    {
        $course = Course::factory()->create();

        $course->faculty->delete();

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'faculty_id' => null]);
    }

    public function test_student_numbers_and_course_codes_are_unique(): void
    {
        Student::factory()->create(['student_number' => '2026-00001']);
        Course::factory()->create(['code' => 'CS101']);

        $this->assertDatabaseCount('students', 1);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Student::factory()->create(['student_number' => '2026-00001']);
    }
}
