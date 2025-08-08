<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => 'integer|required',
            'value' => 'integer|required',
            'employee_code' => ['required', 'string', Rule::exists('employees', 'code')],
            'notes' => 'nullable|string'
        ];
    }

    public function messages()
    {
        return [
            'employee_code.exists' => 'الموظف غير موجود أو الكود غير صحيح.'
        ];
    }
}
