<?php

namespace App\Modules\Content\Types;

final class ContentTypeRegistry
{
    /** @var array<string, ContentType> */
    private array $types = [];

    /**
     * @param  iterable<ContentType>  $types
     */
    public function __construct(iterable $types)
    {
        foreach ($types as $type) {
            $this->types[$type->table()] = $type;
        }
    }

    /**
     * Resolve a type from its route key; an unknown key is a 404.
     */
    public function for(string $key): ContentType
    {
        abort_unless(isset($this->types[$key]), 404);

        return $this->types[$key];
    }

    /**
     * @return list<ContentType>
     */
    public function all(): array
    {
        return array_values($this->types);
    }
}
