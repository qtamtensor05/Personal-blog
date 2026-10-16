<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class IssueTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:4096'],
        ];
    }
}
