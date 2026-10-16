<?php

declare(strict_types=1);

namespace App\Presentation\Web\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SaveDesignDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $color = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        // Font names only; no quotes or CSS syntax that could break out of the style attribute.
        $font = ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9 \-]+$/'];

        return [
            'primaryColor' => $color,
            'secondaryColor' => $color,
            'accentColor' => $color,
            'textColor' => $color,
            'backgroundColor' => $color,
            'headingFont' => $font,
            'bodyFont' => $font,
        ];
    }
}
