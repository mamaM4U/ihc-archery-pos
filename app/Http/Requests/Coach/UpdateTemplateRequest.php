<?php

namespace App\Http\Requests\Coach;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! $this->user()->can('templates-update')) {
            return false;
        }

        $template = $this->route('template');

        // Allow if admin or if coach owns this template
        return $this->user()->hasRole('admin') || ($template && $template->coach_id === $this->user()->id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'template_name' => ['required', 'string', 'max:255'],
            'booking_open_days' => ['required', 'integer', 'min:1', 'max:90'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
            'slots' => ['required', 'array', 'min:1'],
            'slots.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'slots.*.session_name' => ['required', 'string', 'max:255'],
            'slots.*.start_time' => ['required', 'string'], // time format can be Checked or cast
            'slots.*.end_time' => ['required', 'string', 'after:slots.*.start_time'],
            'slots.*.location' => ['required', 'string', 'max:255'],
            'slots.*.max_capacity' => ['required', 'integer', 'min:1'],
            'slots.*.duration_minutes' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'slots.*.day_of_week' => 'hari',
            'slots.*.session_name' => 'nama sesi',
            'slots.*.start_time' => 'waktu mulai',
            'slots.*.end_time' => 'waktu selesai',
            'slots.*.location' => 'lokasi',
            'slots.*.max_capacity' => 'kapasitas maksimum',
            'slots.*.duration_minutes' => 'durasi (menit)',
        ];
    }
}
