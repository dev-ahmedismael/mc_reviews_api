<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'name' => 'string|required',
            'email' => 'string|required|email',
            'password' => 'string|required|min_digits:8'
        ];
    }

    public function messages()
    {
        return [
            'password.min_digits' => 'كلمة المرور يجب أن تحتوي على 8 أرقام على الأقل.'

        ];
    }
}
