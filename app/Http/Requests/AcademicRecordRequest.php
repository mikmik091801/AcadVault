<?php

namespace App\Http\Requests;

use App\Models\AcademicRecord;
use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
     * Rules that need both the student and the course at once.
     *
     * A grade belongs to a class the student is actually taking, and there is
     * only ever one grade per student per course. An existing record whose
     * student and course are left unchanged is exempt, so grades filed before
     * enrolment existed can still be corrected.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['student_id', 'course_id'])) {
                    return;
                }

                $studentId = (int) $this->input('student_id');
                $courseId = (int) $this->input('course_id');

                /** @var AcademicRecord|null $record */
                $record = $this->route('record');

                if ($record && (int) $record->student_id === $studentId && (int) $record->course_id === $courseId) {
                    return;
                }

                $alreadyGraded = AcademicRecord::query()
                    ->where('student_id', $studentId)
                    ->where('course_id', $courseId)
                    ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                    ->exists();

                if ($alreadyGraded) {
                    $validator->errors()->add(
                        'student_id',
                        'This student already has a grade for this course. Edit that record instead.',
                    );

                    return;
                }

                $isEnrolled = Enrollment::query()
                    ->where('student_id', $studentId)
                    ->where('course_id', $courseId)
                    ->active()
                    ->exists();

                if (! $isEnrolled) {
                    $validator->errors()->add(
                        'student_id',
                        'This student is not enrolled in the selected course.',
                    );
                }
            },
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
