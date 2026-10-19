<?php

declare(strict_types=1);

namespace App\Presentation\Web\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:DRAFT,PUBLISHED'],
            'visibility' => ['nullable', 'in:PUBLIC,UNLISTED,PRIVATE'],
            'discoverable' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nhập tên blog để người đọc nhận ra blog của bạn.',
            'name.max' => 'Tên blog tối đa 160 ký tự. Hãy rút gọn tên rồi thử lại.',
            'slug.regex' => 'Slug chỉ gồm chữ thường không dấu, số và dấu gạch ngang; ví dụ: blog-cua-toi.',
            'slug.max' => 'Slug tối đa 160 ký tự. Hãy rút gọn slug rồi thử lại.',
            'status.in' => 'Trạng thái chỉ có thể là bản nháp hoặc đã xuất bản.',
            'visibility.in' => 'Quyền hiển thị không hợp lệ. Hãy tải lại trang và chọn lại.',
        ];
    }
}
