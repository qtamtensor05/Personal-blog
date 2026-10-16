<?php

declare(strict_types=1);

namespace App\Application\Domains\Contracts;

interface DnsTxtResolver
{
    /**
     * TXT record values for the name; empty when the lookup fails or nothing is found.
     *
     * @return list<string>
     */
    public function txtRecords(string $name): array;
}
