<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:254',
                Rule::unique('users', 'email')
                    ->ignore($this->user()?->getKey()),
            ],
            'current_password' => ['required', 'current_password:web'],
        ];
    }
}
