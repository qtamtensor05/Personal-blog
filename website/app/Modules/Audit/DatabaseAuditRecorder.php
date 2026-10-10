<?php

namespace App\Modules\Audit;

use App\Shared\Contracts\AuditRecorder;
use Illuminate\Support\Facades\DB;

final class DatabaseAuditRecorder implements AuditRecorder
{
    private const REDACTED = ['password', 'token', 'secret', 'otp', 'recovery_codes'];

    public function record(
        ?int $actorId,
        string $action,
        string $resourceType,
        string|int|null $resourceId,
        ?int $blogId = null,
        array $before = [],
        array $after = [],
    ): void {
        DB::table('admin_audit_logs')->insert([
            'actor_user_id' => $actorId,
            'blog_id' => $blogId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId === null ? null : (string) $resourceId,
            'before_json' => json_encode($this->redact($before), JSON_THROW_ON_ERROR),
            'after_json' => json_encode($this->redact($after), JSON_THROW_ON_ERROR),
            'ip_address' => request()?->ip(),
            'request_id' => request()?->header('X-Request-ID'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function redact(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), self::REDACTED, true)) {
                $payload[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $payload[$key] = $this->redact($value);
            }
        }

        return $payload;
    }
}
