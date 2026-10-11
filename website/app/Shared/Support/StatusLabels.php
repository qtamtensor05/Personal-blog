<?php

namespace App\Shared\Support;

final class StatusLabels
{
    public static function label(string $status): string
    {
        return ['DRAFT' => 'Bản nháp', 'PUBLISHED' => 'Đã xuất bản', 'HIDDEN' => 'Ngừng cho chọn', 'RETIRED' => 'Đã ngừng', 'ACTIVE' => 'Hoạt động', 'SUSPENDED' => 'Đình chỉ', 'SCHEDULED' => 'Đã lên lịch', 'ARCHIVED' => 'Lưu trữ', 'READY' => 'Sẵn sàng', 'FAILED' => 'Thất bại', 'UPLOADING' => 'Đang tải', 'DELETING' => 'Đang xóa'][$status] ?? $status;
    }
}
