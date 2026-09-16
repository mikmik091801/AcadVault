<?php

namespace App\Http\Requests;

use App\Enums\Program;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
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
        $course = $this->route('course');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('courses', 'code')->ignore($course),
            ],
            'title' => ['required', 'string', 'max:255'],
            'faculty_id' => [
                'nullable',
                'integer',
                // Only actual faculty members can be assigned to teach.
                Rule::exists('users', 'id')->where('role', Role::Faculty->value),
            ],

            // Curriculum placement. All optional: an unplaced subject stays
            // open to every student.
            'program' => ['nullable', 'string', Rule::in(Program::values())],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:3'],
            'units' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'That course code already exists.',
            'faculty_id.exists' => 'The selected instructor is not a faculty member.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'faculty_id' => 'instructor',
        ];
    }
}
