<?php
namespace App\Modules\Media\Actions;

use App\Shared\Contracts\CloudinaryGateway;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ReconcileMediaAction
{
    public function __construct(
        private readonly CloudinaryGateway $cloudinary
    ) {}

    public function execute(): array
    {
        $result = [
            'deleted' => 0,
            'failed' => 0,
            'stale_uploads' => 0,
        ];

        $rows = DB::table('media_assets')
            ->whereIn('status', ['DELETING', 'FAILED'])
            ->whereNotNull('cloudinary_public_id')
            ->where('updated_at', '<', now()->subMinutes(10))
            ->orderBy('id')
            ->limit(100)
            ->get(['id', 'cloudinary_public_id']);

        foreach ($rows as $row) {
            try {
                if (
                    !$this->cloudinary->destroy(
                        $row->cloudinary_public_id
                    )
                ) {
                    $result['failed']++;
                    continue;
                }

                $result['deleted'] += DB::table('media_assets')
                    ->where('id', $row->id)
                    ->whereIn('status', ['DELETING', 'FAILED'])
                    ->where(
                        'cloudinary_public_id',
                        $row->cloudinary_public_id
                    )
                    ->update([
                        'status' => 'DELETED',
                        'deleted_at' => now(),
                        'updated_at' => now(),
                    ]);

            } catch (Throwable $e) {
                report($e);
                $result['failed']++;
            }
        }

        $result['stale_uploads'] = DB::table('media_assets')
            ->where('status', 'UPLOADING')
            ->where('created_at', '<', now()->subHours(2))
            ->whereNull('cloudinary_public_id')
            ->update([
                'status' => 'FAILED',
                'updated_at' => now(),
            ]);

        return $result;
    }
}
