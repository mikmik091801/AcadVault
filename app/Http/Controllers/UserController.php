<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\SearchTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Admin-only account management.
 *
 * Sign-ups always land as students; this is the only place a role can be
 * changed, and every change is written to the audit trail.
 */
class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin'),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', '');

        $users = User::query()
            ->with('student')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => SearchTerm::whereAllWords($q, ['name', 'first_name', 'last_name', 'email'], $search)))
            ->when(Role::tryFrom($role), fn ($query, Role $value) => $query->where('role', $value))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        // One count per role for the summary cards, in a single query.
        $counts = User::query()
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('users.index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'stats' => collect(Role::cases())->map(fn (Role $case) => [
                // "Faculty" has no sensible plural, so the labels are spelled
                // out rather than run through Str::plural().
                'label' => match ($case) {
                    Role::Admin => 'Administrators',
                    Role::Registrar => 'Registrars',
                    Role::Faculty => 'Faculty',
                    Role::Student => 'Students',
                },
                'value' => (int) ($counts[$case->value] ?? 0),
                'icon' => $case->icon(),
                'variant' => match ($case) {
                    Role::Admin => 'danger',
                    Role::Registrar => 'navy',
                    Role::Faculty => 'accent',
                    Role::Student => 'success',
                },
                'href' => route('users.index', ['role' => $case->value]),
            ])->all(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create');
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        // Plain password: the model's `hashed` cast applies argon2id. Never
        // hand it an already-hashed value.
        $user = User::create($request->validated());

        AuditLogger::log(AuditLogger::USER_CREATED, $user);
        AuditLogger::log(
            AuditLogger::USER_ROLE_CHANGED,
            "User #{$user->id}: new account → {$user->role->value}",
        );

        return redirect()
            ->route('users.show', $user)
            ->with('success', "{$user->name} was added as {$user->role->label()}.");
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        // The record count comes along because deleting the account cascades
        // through the student profile to their records.
        $user->load(['student' => fn ($query) => $query->withCount('academicRecords')]);
        $user->loadCount(['courses', 'createdRecords', 'exports']);

        return view('users.show', [
            'user' => $user,
            'recentLogs' => $user->auditLogs()->latest()->limit(10)->get(),
        ]);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        $previousRole = $user->role;

        // Leaving the password field blank keeps the current password.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        AuditLogger::log(AuditLogger::USER_UPDATED, $user);

        if ($user->role !== $previousRole) {
            AuditLogger::log(
                AuditLogger::USER_ROLE_CHANGED,
                "User #{$user->id}: {$previousRole->value} → {$user->role->value}",
            );
        }

        return redirect()
            ->route('users.show', $user)
            ->with('success', "{$user->name}'s account was updated.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $name = $user->name;

        // Logged before the row disappears so the entry keeps the real id;
        // audit rows themselves survive the delete with a null user_id.
        AuditLogger::log(AuditLogger::USER_DELETED, $user);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', "{$name}'s account was deleted.");
    }
}
