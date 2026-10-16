<?php

namespace App\Modules\Content\Types;

use App\Models\User;

/**
 * Strategy describing everything that differs between kinds of blog content
 * (posts, pages). Adding a new kind means adding an implementation and tagging
 * it in the service provider; no existing action needs to change.
 */
interface ContentType
{
    /** Database table and route key, e.g. "posts". */
    public function table(): string;

    /** Singular noun used in audit actions and resource types, e.g. "post". */
    public function singular(): string;

    /**
     * Editable fields that are copied from a draft snapshot to the live row.
     *
     * @return list<string>
     */
    public function draftFields(): array;

    /** Whether the kind owns category/tag relations. */
    public function supportsTerms(): bool;

    /**
     * Columns that only apply when the row is first inserted.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function creationAttributes(User $actor, array $data): array;

    /** Throws a validation error when the blog may not create more content of this kind. */
    public function assertCanCreate(int $blogId): void;

    /** Throws when the row is still referenced and therefore cannot be archived. */
    public function assertCanArchive(int $blogId, object $row): void;
}
