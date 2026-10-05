<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function users(): array
    {
        return [
            'admin' => User::factory()->admin()->create(),
            'registrar' => User::factory()->registrar()->create(),
            'faculty' => User::factory()->faculty()->create(),
            'student' => User::factory()->student()->create(),
        ];
    }

    // ------------------------------------------------------------------
    // Guests
    // ------------------------------------------------------------------

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/dashboard', '/students', '/courses', '/records'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    // ------------------------------------------------------------------
    // Route access matrix
    // ------------------------------------------------------------------

    public static function accessMatrix(): array
    {
        // [route, admin, registrar, faculty, student]
        return [
            'students index' => ['students.index', 200, 200, 403, 403],
            'students create' => ['students.create', 200, 200, 403, 403],
            'courses index' => ['courses.index', 200, 200, 200, 403],
            'courses create' => ['courses.create', 200, 200, 403, 403],
            'records index' => ['records.index', 200, 200, 200, 200],
            'records create' => ['records.create', 200, 200, 200, 403],
        ];
    }

    #[DataProvider('accessMatrix')]
    public function test_routes_enforce_role_access(
        string $route,
        int $admin,
        int $registrar,
        int $faculty,
        int $student,
    ): void {
        $users = $this->users();
        $expected = compact('admin', 'registrar', 'faculty', 'student');

        foreach ($expected as $role => $status) {
            $this->actingAs($users[$role])
                ->get(route($route))
                ->assertStatus($status, "{$role} on {$route}");
        }
    }

    // ------------------------------------------------------------------
    // 403s are audited
    // ------------------------------------------------------------------

    public function test_blocked_access_is_written_to_the_audit_log(): void
    {
        $student = User::factory()->student()->create();

        $this->assertDatabaseCount('audit_logs', 0);

        $this->actingAs($student)->get('/students')->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $student->id,
            'action' => AuditLogger::ACCESS_DENIED,
            'target_type' => 'students',
        ]);
    }

    public function test_a_policy_denial_is_also_audited(): void
    {
        $faculty = User::factory()->faculty()->create();
        $othersCourse = Course::factory()->create(); // taught by somebody else

        $this->actingAs($faculty)
            ->get(route('courses.show', $othersCourse))
            ->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $faculty->id,
            'action' => AuditLogger::ACCESS_DENIED,
            'target_type' => "courses/{$othersCourse->id}",
        ]);
    }

    public function test_the_denied_entry_records_the_ip_address(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get('/students')->assertForbidden();

        $log = AuditLog::where('action', AuditLogger::ACCESS_DENIED)->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->ip_address);
    }

    // ------------------------------------------------------------------
    // Faculty are scoped to their own courses
    // ------------------------------------------------------------------

    public function test_faculty_only_see_their_own_courses(): void
    {
        $faculty = User::factory()->faculty()->create();
        $mine = Course::factory()->create(['faculty_id' => $faculty->id, 'code' => 'MINE101']);
        $theirs = Course::factory()->create(['code' => 'THEIRS101']);

        $this->actingAs($faculty)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertSee('MINE101')
            ->assertDontSee('THEIRS101');

        $this->actingAs($faculty)->get(route('courses.show', $mine))->assertOk();
        $this->actingAs($faculty)->get(route('courses.show', $theirs))->assertForbidden();
    }

    public function test_faculty_only_see_records_from_their_own_courses(): void
    {
        $faculty = User::factory()->faculty()->create();
        $myCourse = Course::factory()->create(['faculty_id' => $faculty->id]);

        $mineRecord = AcademicRecord::factory()->create(['course_id' => $myCourse->id]);
        $otherRecord = AcademicRecord::factory()->create();

        $this->actingAs($faculty)->get(route('records.index'))->assertOk();
        $this->actingAs($faculty)->get(route('records.show', $mineRecord))->assertOk();
        $this->actingAs($faculty)->get(route('records.show', $otherRecord))->assertForbidden();
    }

    public function test_faculty_cannot_file_a_grade_for_a_course_they_do_not_teach(): void
    {
        $faculty = User::factory()->faculty()->create();
        $foreignCourse = Course::factory()->create();
        $student = Student::factory()->create();

        $this->actingAs($faculty)
            ->post(route('records.store'), [
                'student_id' => $student->id,
                'course_id' => $foreignCourse->id,
                'grade' => '1.00',
                'remarks' => 'Forged',
            ])
            ->assertSessionHasErrors('course_id');

        $this->assertDatabaseCount('academic_records', 0);
    }

    public function test_faculty_can_file_a_grade_for_their_own_course(): void
    {
        $faculty = User::factory()->faculty()->create();
        $course = Course::factory()->create(['faculty_id' => $faculty->id]);
        $student = Student::factory()->create();
        Enrollment::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        $this->actingAs($faculty)
            ->post(route('records.store'), [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'grade' => '1.25',
                'remarks' => 'Passed',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('academic_records', [
            'course_id' => $course->id,
            'created_by' => $faculty->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Students are scoped to their own records
    // ------------------------------------------------------------------

    public function test_students_only_see_their_own_records(): void
    {
        $profile = Student::factory()->create();
        $me = $profile->user;

        $mine = AcademicRecord::factory()->create(['student_id' => $profile->id]);
        $theirs = AcademicRecord::factory()->create();

        $this->actingAs($me)->get(route('records.show', $mine))->assertOk();
        $this->actingAs($me)->get(route('records.show', $theirs))->assertForbidden();
    }

    public function test_students_cannot_create_or_delete_records(): void
    {
        $profile = Student::factory()->create();
        $record = AcademicRecord::factory()->create(['student_id' => $profile->id]);

        $this->actingAs($profile->user)->get(route('records.create'))->assertForbidden();
        $this->actingAs($profile->user)->delete(route('records.destroy', $record))->assertForbidden();

        $this->assertDatabaseHas('academic_records', ['id' => $record->id]);
    }

    public function test_only_admins_can_delete_a_course(): void
    {
        $course = Course::factory()->create();
        $users = $this->users();

        $this->actingAs($users['registrar'])
            ->delete(route('courses.destroy', $course))
            ->assertForbidden();

        $this->actingAs($users['admin'])
            ->delete(route('courses.destroy', $course))
            ->assertRedirect(route('courses.index'));

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    // ------------------------------------------------------------------
    // Dashboards
    // ------------------------------------------------------------------

    public function test_every_role_gets_a_working_dashboard(): void
    {
        foreach ($this->users() as $role => $user) {
            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk("dashboard for {$role}");
        }
    }

    public function test_viewing_a_record_is_written_to_the_audit_log(): void
    {
        $registrar = User::factory()->registrar()->create();
        $record = AcademicRecord::factory()->create();

        $this->actingAs($registrar)->get(route('records.show', $record))->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $registrar->id,
            'action' => AuditLogger::RECORD_VIEWED,
            'target_type' => AcademicRecord::class,
            'target_id' => $record->id,
        ]);
    }
}
