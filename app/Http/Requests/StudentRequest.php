<?php

namespace App\Http\Requests;

use App\Enums\Program;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller + policy already gate this
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('students', 'user_id')->ignore($student),
            ],
            'student_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('students', 'student_number')->ignore($student),
            ],
            'program' => ['required', 'string', 'max:255', Rule::in($this->allowedPrograms())],
            'year_level' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }

    /**
     * The programs this form will accept.
     *
     * The university catalog, plus whatever the profile already holds — a
     * profile created before the catalog was fixed would otherwise be
     * impossible to save without also changing its program.
     *
     * @return array<int, string>
     */
    private function allowedPrograms(): array
    {
        $student = $this->route('student');
        $current = $student instanceof Student ? $student->program : null;

        return $current === null
            ? Program::values()
            : [...Program::values(), $current];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.unique' => 'That user already has a student profile.',
            'student_number.unique' => 'That student number is already in use.',
            'program.in' => 'Choose a program from the list.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'user account',
            'student_number' => 'student number',
            'year_level' => 'year level',
        ];
    }
}
