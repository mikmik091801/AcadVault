<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller + UserPolicy already gate this
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->targetUser();

        return [
            // `name` is composed from these by the User model on save.
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'alpha', 'size:1'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-.\s]{7,32}$/'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user),
            ],
            'role' => ['required', Rule::enum(Role::class)],

            // Required when creating; on edit an empty field simply leaves the
            // existing password alone.
            'password' => [
                $user ? 'nullable' : 'required',
                'confirmed',
                Password::defaults(),
            ],
        ];
    }

    /**
     * Rules that need the current state of the account being edited.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->guardAgainstSelfRoleChange($validator),
            fn (Validator $validator) => $this->guardStudentProfile($validator),
        ];
    }

    /**
     * An admin cannot promote or demote themselves — see UserPolicy.
     */
    private function guardAgainstSelfRoleChange(Validator $validator): void
    {
        $user = $this->targetUser();

        if (! $user || $this->user()->isNot($user)) {
            return;
        }

        if ($this->input('role') !== $user->role->value) {
            $validator->errors()->add(
                'role',
                'You cannot change your own role. Ask another administrator to do it.',
            );
        }
    }

    /**
     * A user with a student profile must keep the student role, otherwise the
     * profile in the students directory would contradict the account.
     */
    private function guardStudentProfile(Validator $validator): void
    {
        $user = $this->targetUser();

        if (! $user || $this->input('role') === Role::Student->value) {
            return;
        }

        if ($user->student()->exists()) {
            $validator->errors()->add(
                'role',
                'This account has a student profile. Delete the student profile first to give it another role.',
            );
        }
    }

    /**
     * The user being edited, or null when creating one.
     */
    private function targetUser(): ?User
    {
        $user = $this->route('user');

        return $user instanceof User ? $user : null;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Another account already uses that email address.',
            'email.lowercase' => 'The email address must be in lowercase.',
            'middle_initial.size' => 'Enter a single letter, or leave it blank.',
            'phone.regex' => 'Enter a valid contact number — digits, and optionally + ( ) - or spaces.',
        ];
    }
}
