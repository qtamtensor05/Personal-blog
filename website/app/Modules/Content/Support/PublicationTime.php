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

        try {
            $time = CarbonImmutable::createFromFormat(
                '!Y-m-d\TH:i',
                $localInput,
                $timezone
            );

            if (
                $time === false ||
                $time->format('Y-m-d\TH:i') !== $localInput
            ) {
                throw new \InvalidArgumentException(
                    'Thời gian không hợp lệ.'
                );
            }

            if (
                $time->utc()->lessThanOrEqualTo(
                    CarbonImmutable::now('UTC')
                )
            ) {
                throw new \InvalidArgumentException(
                    'Hãy chọn thời gian trong tương lai.'
                );
            }

            return $time->utc()->format('Y-m-d H:i:s');

        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'scheduled_at' =>
                    'Lịch đăng không hợp lệ hoặc đã qua. Hãy chọn lại giờ theo múi giờ blog.'
            ]);
        }
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
