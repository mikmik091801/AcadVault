<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicRecordRequest extends FormRequest
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
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'course_id' => [
                'required',
                'integer',
                // A faculty member may only file grades for their own courses.
                // Enforced here as well as in the UI so a forged course_id in
                // the POST body is rejected.
                Rule::exists('courses', 'id')->where(function ($query) {
                    if ($this->user()?->isFaculty()) {
                        $query->where('faculty_id', $this->user()->id);
                    }
                }),
            ],
            'grade' => ['required', 'string', 'max:20'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'course_id.exists' => 'You can only file grades for courses you teach.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'student',
            'course_id' => 'course',
        ];
    }
}
