<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AcademicRecord;
use App\Models\Student;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // ------------------------------------------------------------------
    // Access
    // ------------------------------------------------------------------

    public static function nonAdminRoles(): array
    {
        return [
            'registrar' => ['registrar'],
            'faculty' => ['faculty'],
            'student' => ['student'],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_only_admins_reach_the_user_screens(string $role): void
    {
        $user = User::factory()->role(Role::from($role))->create();
        $target = User::factory()->create();

        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('users.create'))->assertForbidden();
        $this->actingAs($user)->get(route('users.show', $target))->assertForbidden();
        $this->actingAs($user)->get(route('users.edit', $target))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_an_admin_sees_the_user_directory(): void
    {
        $admin = $this->admin();
        $other = User::factory()->registrar()->create(['name' => 'Reggie Registrar']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Reggie Registrar')
            ->assertSee($other->email);
    }

    public function test_every_user_screen_renders_for_an_admin(): void
    {
        $admin = $this->admin();
        $profile = Student::factory()->create(); // exercises the student-profile branches

        foreach ([$profile->user, $admin] as $target) {
            $this->actingAs($admin)->get(route('users.show', $target))->assertOk();
            $this->actingAs($admin)->get(route('users.edit', $target))->assertOk();
        }

        $this->actingAs($admin)->get(route('users.create'))->assertOk();
    }

    public function test_the_role_field_is_locked_when_an_admin_edits_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('users.edit', $admin))
            ->assertOk()
            ->assertSee('You cannot change your own role', escape: false);

        $this->actingAs($admin)
            ->get(route('users.edit', User::factory()->student()->create()))
            ->assertOk()
            ->assertDontSee('You cannot change your own role', escape: false);
    }

    public function test_the_directory_filters_by_role_and_search(): void
    {
        $admin = $this->admin();
        User::factory()->faculty()->create(['name' => 'Fiona Faculty']);
        User::factory()->student()->create(['name' => 'Sam Student']);

        $this->actingAs($admin)
            ->get(route('users.index', ['role' => 'faculty']))
            ->assertOk()
            ->assertSee('Fiona Faculty')
            ->assertDontSee('Sam Student');

        $this->actingAs($admin)
            ->get(route('users.index', ['search' => 'Sam']))
            ->assertOk()
            ->assertSee('Sam Student')
            ->assertDontSee('Fiona Faculty');
    }

    // ------------------------------------------------------------------
    // Creating
    // ------------------------------------------------------------------

    public function test_an_admin_can_create_a_user_with_a_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'last_name' => 'Registrar',
                'first_name' => 'New',
                'email' => 'new.registrar@example.com',
                'role' => 'registrar',
                'password' => 'correct-horse-battery',
                'password_confirmation' => 'correct-horse-battery',
            ])
            ->assertRedirect();

        $created = User::where('email', 'new.registrar@example.com')->firstOrFail();

        $this->assertSame(Role::Registrar, $created->role);
        $this->assertTrue(Hash::check('correct-horse-battery', $created->password));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditLogger::USER_CREATED,
            'target_type' => User::class,
            'target_id' => $created->id,
        ]);
    }

    public function test_creating_a_user_requires_a_valid_role_and_unique_email(): void
    {
        $admin = $this->admin();
        $existing = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'last_name' => 'Input',
                'first_name' => 'Bad',
                'email' => $existing->email,
                'role' => 'superuser',
                'password' => 'correct-horse-battery',
                'password_confirmation' => 'correct-horse-battery',
            ])
            ->assertSessionHasErrors(['email', 'role']);

        $this->assertDatabaseCount('users', 2); // admin + existing
    }

    // ------------------------------------------------------------------
    // Editing and role changes
    // ------------------------------------------------------------------

    public function test_an_admin_can_promote_a_student_to_registrar(): void
    {
        $admin = $this->admin();
        $user = User::factory()->student()->create(['name' => 'Rising Star']);

        $this->actingAs($admin)
            ->put(route('users.update', $user), [
                'last_name' => 'Star',
                'first_name' => 'Rising',
                'email' => $user->email,
                'role' => 'registrar',
            ])
            ->assertRedirect(route('users.show', $user));

        $this->assertSame(Role::Registrar, $user->refresh()->role);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditLogger::USER_ROLE_CHANGED,
            'target_type' => "User #{$user->id}: student → registrar",
        ]);
    }

    public function test_editing_without_a_password_keeps_the_existing_one(): void
    {
        $admin = $this->admin();
        $user = User::factory()->faculty()->create(['password' => 'original-password']);

        $this->actingAs($admin)
            ->put(route('users.update', $user), [
                'last_name' => 'Faculty',
                'first_name' => 'Renamed',
                'middle_initial' => '', // the form always posts this, blank or not
                'email' => $user->email,
                'role' => 'faculty',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertSame('Renamed Faculty', $user->name);
        $this->assertTrue(Hash::check('original-password', $user->password));
    }

    public function test_an_admin_can_reset_a_password(): void
    {
        $admin = $this->admin();
        $user = User::factory()->faculty()->create(['password' => 'original-password']);

        $this->actingAs($admin)
            ->put(route('users.update', $user), [
                'last_name' => $user->last_name,
                'first_name' => $user->first_name,
                'email' => $user->email,
                'role' => 'faculty',
                'password' => 'a-brand-new-password',
                'password_confirmation' => 'a-brand-new-password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('a-brand-new-password', $user->refresh()->password));
    }

    public function test_an_admin_cannot_change_their_own_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('users.update', $admin), [
                'last_name' => $admin->last_name,
                'first_name' => $admin->first_name,
                'email' => $admin->email,
                'role' => 'student',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame(Role::Admin, $admin->refresh()->role);
    }

    public function test_an_admin_can_still_edit_their_own_name(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('users.update', $admin), [
                'last_name' => 'Admin',
                'first_name' => 'Renamed',
                'middle_initial' => '',
                'email' => $admin->email,
                'role' => 'admin',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed Admin', $admin->refresh()->name);
    }

    public function test_a_user_with_a_student_profile_cannot_be_given_another_role(): void
    {
        $admin = $this->admin();
        $profile = Student::factory()->create();

        $this->actingAs($admin)
            ->put(route('users.update', $profile->user), [
                'last_name' => $profile->user->last_name,
                'first_name' => $profile->user->first_name,
                'email' => $profile->user->email,
                'role' => 'faculty',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame(Role::Student, $profile->user->refresh()->role);
    }

    public function test_an_ordinary_edit_is_audited(): void
    {
        $admin = $this->admin();
        $user = User::factory()->faculty()->create();

        $this->actingAs($admin)
            ->put(route('users.update', $user), [
                'last_name' => 'Name',
                'first_name' => 'Edited',
                'email' => $user->email,
                'role' => 'faculty',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditLogger::USER_UPDATED,
            'target_type' => User::class,
            'target_id' => $user->id,
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => AuditLogger::USER_ROLE_CHANGED,
            'target_type' => "User #{$user->id}: faculty → faculty",
        ]);
    }

    // ------------------------------------------------------------------
    // Deleting
    // ------------------------------------------------------------------

    public function test_an_admin_can_delete_another_account(): void
    {
        $admin = $this->admin();
        $user = User::factory()->faculty()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditLogger::USER_DELETED,
            'target_type' => User::class,
            'target_id' => $user->id,
        ]);
    }

    public function test_an_admin_cannot_delete_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_deleting_a_student_account_removes_their_records_but_keeps_the_audit_trail(): void
    {
        $admin = $this->admin();
        $profile = Student::factory()->create();
        $record = AcademicRecord::factory()->create(['student_id' => $profile->id]);

        // Something this user did, before the account goes away.
        AuditLogger::log(AuditLogger::LOGIN_SUCCESS, null, $profile->user);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $profile->user))
            ->assertRedirect(route('users.index'));

        $this->assertSoftDeleted('students', ['id' => $profile->id]);
        $this->assertSoftDeleted('academic_records', ['id' => $record->id]);

        // The entry survives with a null user_id — audit rows are never purged.
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogger::LOGIN_SUCCESS,
            'user_id' => null,
        ]);
    }

    // ------------------------------------------------------------------
    // Navigation
    // ------------------------------------------------------------------

    public function test_the_sidebar_links_to_users_for_admins_only(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('users.index'));

        $this->actingAs(User::factory()->registrar()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('users.index'));
    }
}
