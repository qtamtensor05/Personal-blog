<?php

declare(strict_types=1);

namespace App\Infrastructure\Administration;

use App\Application\Administration\Contracts\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class QueryBuilderAuditLogger implements AuditLogger
{
    public function __construct(private Request $request) {}

    public function log(
        int $actorId,
        string $action,
        string $resourceType,
        ?string $resourceId,
        ?array $before = null,
        ?array $after = null,
        ?int $blogId = null,
    ): void {
        $now = now();
        $requestId = $this->request->headers->get('X-Request-Id');

        DB::table('admin_audit_logs')->insert([
            'actor_user_id' => $actorId,
            'blog_id' => $blogId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'before_data' => $before === null ? null : json_encode($before),
            'after_data' => $after === null ? null : json_encode($after),
            'ip_address' => $this->request->ip(),
            'request_id' => is_string($requestId) && $requestId !== ''
                ? mb_substr($requestId, 0, 64)
                : (string) Str::uuid(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
