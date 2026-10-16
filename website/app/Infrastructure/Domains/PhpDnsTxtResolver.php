<?php

declare(strict_types=1);

namespace App\Infrastructure\Domains;

use App\Application\Domains\Contracts\DnsTxtResolver;

final class PhpDnsTxtResolver implements DnsTxtResolver
{
    public function txtRecords(string $name): array
    {
        $records = @dns_get_record($name, DNS_TXT);

        if (! is_array($records)) {
            return [];
        }

        $values = [];

        foreach ($records as $record) {
            // Long TXT values may be split into several strings.
            $value = isset($record['entries']) && is_array($record['entries'])
                ? implode('', $record['entries'])
                : ($record['txt'] ?? null);

            if (is_string($value)) {
                $values[] = trim($value);
            }
        }

        return $values;
    }
}
