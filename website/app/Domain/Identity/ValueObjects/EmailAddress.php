<?php

declare(strict_types=1);

namespace App\Domain\Identity\ValueObjects;

use DomainException;

final readonly class EmailAddress
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException('Invalid email address.');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
