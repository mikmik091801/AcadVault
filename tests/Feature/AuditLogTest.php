<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AcademicRecord;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Authentication events
    // ------------------------------------------------------------------

    public function test_a_successful_login_is_logged(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => AuditLogger::LOGIN_SUCCESS,
        ]);
    }

    public function test_a_failed_login_is_logged_with_the_attempted_email(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors();

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogger::LOGIN_FAILED,
            'target_type' => 'email: '.$user->email,
        ]);
    }

    public function test_each_auth_event_is_logged_exactly_once(): void
    {
        // Regression: Laravel auto-discovers public handle* listener methods,
        // so a listener named handleLogin() plus an explicit Event::listen
        // registration writes every entry twice.
        $user = User::factory()->create(['password' => 'correct-password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'nope']);
        $this->assertSame(1, AuditLog::where('action', AuditLogger::LOGIN_FAILED)->count());

        $this->post('/login', ['email' => $user->email, 'password' => 'correct-password']);
        $this->assertSame(1, AuditLog::where('action', AuditLogger::LOGIN_SUCCESS)->count());

        $this->post('/logout');
        $this->assertSame(1, AuditLog::where('action', AuditLogger::LOGOUT)->count());
    }

    public function test_non_model_targets_render_without_a_stray_id(): void
    {
        $student = User::factory()->student()->create();
        $this->actingAs($student)->get('/students')->assertForbidden();

        $log = AuditLog::where('action', AuditLogger::ACCESS_DENIED)->firstOrFail();

        // "students", not "students #"
        $this->assertSame('students', $log->targetLabel());
        $this->assertStringNotContainsString('#', $log->targetLabel());
    }

    public function test_model_targets_render_with_their_id(): void
    {
        $registrar = User::factory()->registrar()->create();
        $record = AcademicRecord::factory()->create();

        $this->actingAs($registrar)->get(route('records.show', $record));

        $log = AuditLog::where('action', AuditLogger::RECORD_VIEWED)->firstOrFail();

        $this->assertSame("AcademicRecord #{$record->id}", $log->targetLabel());
    }

    public function test_a_failed_login_never_stores_the_password(): void
    {
        User::factory()->create(['email' => 'victim@acadvault.test']);

        $this->post('/login', [
            'email' => 'victim@acadvault.test',
            'password' => 'hunter2-super-secret',
        ]);

        $logs = AuditLog::all();

        $this->assertNotEmpty($logs);

        foreach ($logs as $log) {
            $this->assertStringNotContainsString('hunter2', (string) $log->target_type);
            $this->assertStringNotContainsString('hunter2', json_encode($log->toArray()));
        }
    }

    public function test_a_failed_login_for_an_unknown_email_is_still_logged(): void
    {
        $this->post('/login', [
            'email' => 'nobody@acadvault.test',
            'password' => 'whatever',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogger::LOGIN_FAILED,
            'user_id' => null,
            'target_type' => 'email: nobody@acadvault.test',
        ]);
    }

    public function test_logout_is_logged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => AuditLogger::LOGOUT,
        ]);
    }

    public function test_repeated_failures_trip_the_lockout_and_log_it(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        // Laravel's login throttle allows five attempts per minute.
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogger::LOGIN_LOCKOUT,
            'target_type' => 'email: '.$user->email,
        ]);
    }

    // ------------------------------------------------------------------
    // Record events
    // ------------------------------------------------------------------

    public function test_viewing_a_record_is_logged(): void
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

    public function test_record_mutations_are_logged_by_the_observer(): void
    {
        // The starting grade is pinned so the update below always changes it —
        // an unchanged value is not dirty, so Eloquent fires no updated event.
        $record = AcademicRecord::factory()->create(['grade' => '2.00']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogger::RECORD_CREATED,
            'target_id' => $record->id,
        ]);

        $record->update(['grade' => '1.00']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogger::RECORD_UPDATED,
            'target_id' => $record->id,
        ]);

        $id = $record->id;
        $record->delete();
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogger::RECORD_DELETED,
            'target_id' => $id,
        ]);
    }

    public function test_a_record_change_made_outside_a_controller_is_still_logged(): void
    {
        // The observer means console commands and tinker are audited too.
        $record = AcademicRecord::factory()->create();
        AuditLog::query()->delete();

        $record->update(['remarks' => 'Adjusted offline']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogger::RECORD_UPDATED,
            'target_id' => $record->id,
        ]);
    }

    public function test_creating_a_record_is_logged_only_once(): void
    {
        $registrar = User::factory()->registrar()->create();
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        Enrollment::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        $this->actingAs($registrar)->post(route('records.store'), [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'grade' => '1.50',
            'remarks' => 'Passed',
        ])->assertRedirect();

        // The controller must not double-log alongside the observer.
        $this->assertSame(
            1,
            AuditLog::where('action', AuditLogger::RECORD_CREATED)->count(),
        );
    }

    // ------------------------------------------------------------------
    // The admin viewer
    // ------------------------------------------------------------------

    public function test_only_admins_can_open_the_audit_log(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('audit-logs.index'))->assertOk();

        foreach (['registrar', 'faculty', 'student'] as $role) {
            $this->actingAs(User::factory()->role(Role::from($role))->create())
                ->get(route('audit-logs.index'))->assertForbidden();
        }
    }

    public function test_a_non_admin_opening_the_audit_log_is_itself_audited(): void
    {
        $registrar = User::factory()->registrar()->create();

        $this->actingAs($registrar)->get(route('audit-logs.index'))->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $registrar->id,
            'action' => AuditLogger::ACCESS_DENIED,
            'target_type' => 'audit-logs',
        ]);
    }

    public function test_the_audit_log_can_be_filtered_by_action(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::factory()->create(['action' => AuditLogger::LOGIN_FAILED, 'ip_address' => '10.0.0.9']);
        AuditLog::factory()->create(['action' => AuditLogger::RECORD_VIEWED, 'ip_address' => '10.0.0.8']);

        $this->actingAs($admin)
            ->get(route('audit-logs.index', ['action' => AuditLogger::LOGIN_FAILED]))
            ->assertOk()
            ->assertSee('10.0.0.9')
            ->assertDontSee('10.0.0.8');
    }

    public function test_the_audit_log_can_be_filtered_by_user(): void
    {
        $admin = User::factory()->admin()->create();
        $alice = User::factory()->create(['name' => 'Alice Aquino']);
        $bob = User::factory()->create(['name' => 'Bob Bautista']);

        AuditLog::factory()->create(['user_id' => $alice->id, 'ip_address' => '10.1.1.1']);
        AuditLog::factory()->create(['user_id' => $bob->id, 'ip_address' => '10.2.2.2']);

        $this->actingAs($admin)
            ->get(route('audit-logs.index', ['user_id' => $alice->id]))
            ->assertOk()
            ->assertSee('10.1.1.1')
            ->assertDontSee('10.2.2.2');
    }

    public function test_the_audit_log_can_be_filtered_by_date_range(): void
    {
        $admin = User::factory()->admin()->create();

        // Created already backdated: entries are append-only, so one cannot be
        // moved into the past after the fact.
        AuditLog::factory()->create(['ip_address' => '10.9.9.9', 'created_at' => now()->subDays(30)]);

        AuditLog::factory()->create(['ip_address' => '10.5.5.5']);

        $this->actingAs($admin)
            ->get(route('audit-logs.index', ['from' => now()->subDay()->toDateString()]))
            ->assertOk()
            ->assertSee('10.5.5.5')
            ->assertDontSee('10.9.9.9');
    }

    public function test_the_audit_log_search_matches_ip_and_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['name' => 'Carmen Delgado']);

        AuditLog::factory()->create(['user_id' => $target->id, 'ip_address' => '192.168.44.7']);
        AuditLog::factory()->create(['ip_address' => '172.16.0.1']);

        $this->actingAs($admin)
            ->get(route('audit-logs.index', ['search' => '192.168.44.7']))
            ->assertOk()
            ->assertSee('192.168.44.7')
            ->assertDontSee('172.16.0.1');

        $this->actingAs($admin)
            ->get(route('audit-logs.index', ['search' => 'Carmen']))
            ->assertOk()
            ->assertSee('192.168.44.7');
    }

    public function test_an_audit_entry_cannot_be_changed(): void
    {
        $log = AuditLogger::log(AuditLogger::LOGIN_SUCCESS);

        $this->expectException(\LogicException::class);

        $log->update(['action' => AuditLogger::LOGOUT]);
    }

    public function test_an_audit_entry_cannot_be_deleted(): void
    {
        $log = AuditLogger::log(AuditLogger::LOGIN_SUCCESS);

        try {
            $log->delete();
            $this->fail('Deleting an audit entry should have thrown.');
        } catch (\LogicException) {
            $this->assertModelExists($log);
        }
    }

    public function test_audit_logging_failure_does_not_break_the_request(): void
    {
        // Simulate the audit table being unavailable.
        Schema::drop('audit_logs');

        $this->assertNull(AuditLogger::log(AuditLogger::RECORD_VIEWED));
    }
}
