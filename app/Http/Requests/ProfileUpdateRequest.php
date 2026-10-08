<?php
namespace App\Http\Requests;

use App\Services\StudentProfileService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array {
        if (!$this->user()->isPembina()) {
            $rules = StudentProfileService::rules();
            if ($this->user()->isPanitia()) { $rules['class_name'] = ['prohibited']; }
            return array_merge($rules, [
                'name' => ['prohibited'], 'username' => ['prohibited'],
                'full_name' => ['prohibited'], 'student_number' => ['prohibited'],
                'role' => ['prohibited'],
            ]);
        }
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255',
                Rule::unique('users', 'username')->ignore($this->user()->id),
                Rule::unique('students', 'student_number')->ignore($this->user()->student?->id)],
        ];
    }
}
