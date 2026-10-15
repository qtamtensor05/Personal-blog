<?php

namespace App\Modules\Content\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PublicationTime
{
    public function timezone(int $blogId): string
    {
        $timezone = (string) (
            DB::table('blog_settings')
                ->where('blog_id', $blogId)
                ->value('timezone')
            ?: 'Asia/Ho_Chi_Minh'
        );

        return in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : 'Asia/Ho_Chi_Minh';
    }

    public function toUtc(int $blogId, string $localInput): string
    {
        $timezone = $this->timezone($blogId);

        // HTML datetime-local dùng múi giờ của blog.
        // SQL datetime cũ được hiểu là UTC để giữ tương thích.
        $formats = [
            ['Y-m-d\TH:i', $timezone],
            ['Y-m-d\TH:i:s', $timezone],
            ['Y-m-d H:i:s', 'UTC'],
            ['Y-m-d H:i', 'UTC'],
        ];

        $time = null;

        foreach ($formats as [$format, $inputTimezone]) {
            try {
                $candidate = CarbonImmutable::createFromFormat(
                    '!'.$format,
                    $localInput,
                    $inputTimezone
                );

                if (
                    $candidate !== false &&
                    $candidate->format($format) === $localInput
                ) {
                    $time = $candidate;
                    break;
                }
            } catch (\Throwable) {
                // Thử định dạng tiếp theo.
            }
        }

        if (
            $time === null ||
            $time->utc()->lessThanOrEqualTo(
                CarbonImmutable::now('UTC')
            )
        ) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Lịch đăng không hợp lệ hoặc đã qua. Hãy chọn lại thời gian.',
            ]);
        }

        return $time->utc()->format('Y-m-d H:i:s');
    }

    public function toLocalInput(
        int $blogId,
        ?string $utc
    ): ?string {
        return $utc
            ? CarbonImmutable::parse($utc, 'UTC')
                ->setTimezone($this->timezone($blogId))
                ->format('Y-m-d\TH:i')
            : null;
    }
}
