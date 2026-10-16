<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RefreshTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string', 'max:255'],
        ];
    }
}
