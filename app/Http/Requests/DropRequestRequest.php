<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DropRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller + EnrollmentPolicy already gate this
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // The registrar has to judge the request, so a bare "idk" is no
            // use to them — hence a real minimum rather than just `required`.
            'drop_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'drop_reason.required' => 'Please explain why you are dropping this class.',
            'drop_reason.min' => 'Please give the registrar a little more detail — at least 10 characters.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['drop_reason' => 'reason'];
    }
}
