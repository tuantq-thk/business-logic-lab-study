<?php

namespace App\Features\CancellationRescheduling\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MutateCourseRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('reason'))) {
            $this->merge(['reason' => trim($this->input('reason'))]);
        }
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:cancel,reschedule'],
            'registration_id' => ['required', 'integer', 'min:1'],
            'target_course_id' => ['nullable', 'required_if:action,reschedule', 'integer', 'exists:cancellation_courses,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'action.required' => __('cancellation_rescheduling.validation.action'),
            'action.in' => __('cancellation_rescheduling.validation.action'),
            'registration_id.required' => __('cancellation_rescheduling.validation.registration'),
            'registration_id.integer' => __('cancellation_rescheduling.validation.registration'),
            'registration_id.min' => __('cancellation_rescheduling.validation.registration'),
            'target_course_id.required_if' => __('cancellation_rescheduling.validation.target_required'),
            'target_course_id.integer' => __('cancellation_rescheduling.validation.target_unavailable'),
            'target_course_id.exists' => __('cancellation_rescheduling.validation.target_unavailable'),
            'reason.string' => __('cancellation_rescheduling.validation.reason'),
            'reason.max' => __('cancellation_rescheduling.validation.reason'),
        ];
    }
}
