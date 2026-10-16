<?php

namespace App\Modules\Media;

use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for "is this media asset still referenced?", shared
 * by every delete use case so they cannot drift apart.
 */
final class MediaReferenceFinder
{
    /** Plain foreign-key columns that point at media_assets. */
    private const COLUMN_REFERENCES = [
        'users' => ['avatar_media_id'],
        'blog_settings' => ['logo_media_id', 'favicon_media_id', 'og_media_id'],
        'templates' => ['preview_media_id'],
        'posts' => ['cover_media_id', 'og_media_id'],
        'pages' => ['og_media_id'],
    ];

    /** JSON documents that may embed a media id. */
    private const DOCUMENT_REFERENCES = [
        'templates' => ['template_data', 'draft_data'],
        'blog_designs' => ['published_data', 'draft_data'],
        'posts' => ['content_json', 'draft_data'],
        'pages' => ['content_json', 'draft_data'],
        'platform_settings' => ['config_json'],
        'blog_settings' => ['social_links'],
    ];

    /** Keys whose value is treated as a media id inside JSON documents. */
    private const MEDIA_KEYS = [
        'mediaId',
        'media_id',
        'asset_id',
        'logoMediaId',
        'previewMediaId',
        'og_media_id',
        'cover_media_id',
    ];

    public function isReferencedByColumn(int $mediaId): bool
    {
        foreach (self::COLUMN_REFERENCES as $table => $columns) {
            foreach ($columns as $column) {
                if (DB::table($table)->where($column, $mediaId)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isReferencedByDocument(int $mediaId): bool
    {
        foreach (self::DOCUMENT_REFERENCES as $table => $columns) {
            foreach (DB::table($table)->select($columns)->cursor() as $row) {
                foreach ($columns as $column) {
                    if ($row->$column && $this->documentContains(json_decode($row->$column, true), $mediaId)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Pure helper: does a decoded JSON structure reference the media id?
     */
    public function documentContains(mixed $value, int $mediaId): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $key => $child) {
            if (
                in_array($key, self::MEDIA_KEYS, true)
                && (is_int($child) || is_string($child))
                && (string) $child === (string) $mediaId
            ) {
                return true;
            }

            if ($this->documentContains($child, $mediaId)) {
                return true;
            }
        }

        return false;
    }
}
