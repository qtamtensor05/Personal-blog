<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ownership links cascade; optional media/template references become null;
     * audit and actor links remain restrictive.
     *
     * @var list<array{0: string, 1: string, 2: list<string>, 3: string, 4: list<string>, 5: string}>
     */
    private const RULES = [
        ['account_restrictions', 'fk_6_account_restrictions', ['user_id'], 'users', ['id'], 'CASCADE'],
        ['sessions', 'fk_5_sessions', ['user_id'], 'users', ['id'], 'CASCADE'],
        ['blog_designs', 'fk_55_blog_designs', ['blog_id'], 'blogs', ['id'], 'CASCADE'],
        ['blog_designs', 'fk_56_blog_designs', ['template_id'], 'templates', ['id'], 'SET NULL'],
        ['blog_designs', 'fk_57_blog_designs', ['draft_template_id'], 'templates', ['id'], 'SET NULL'],
        ['blog_domains', 'fk_16_blog_domains', ['blog_id'], 'blogs', ['id'], 'CASCADE'],
        ['blog_settings', 'fk_15_blog_settings', ['blog_id'], 'blogs', ['id'], 'CASCADE'],
        ['blog_settings', 'fk_38_blog_settings', ['logo_media_id'], 'media_assets', ['id'], 'SET NULL'],
        ['blog_settings', 'fk_39_blog_settings', ['favicon_media_id'], 'media_assets', ['id'], 'SET NULL'],
        ['blog_settings', 'fk_40_blog_settings', ['og_media_id'], 'media_assets', ['id'], 'SET NULL'],
        ['blog_topic_assignments', 'fk_13_blog_topic_assignments', ['blog_id'], 'blogs', ['id'], 'CASCADE'],
        ['blog_topic_assignments', 'fk_14_blog_topic_assignments', ['topic_id'], 'blog_topics', ['id'], 'CASCADE'],
        ['categories', 'fk_28_categories', ['blog_id'], 'blogs', ['id'], 'CASCADE'],
        ['categories', 'fk_29_categories', ['blog_id', 'parent_id'], 'categories', ['blog_id', 'id'], 'CASCADE'],
        ['content_reports', 'fk_42_content_reports', ['blog_id'], 'blogs', ['id'], 'CASCADE'],
        ['content_reports', 'fk_43_content_reports', ['blog_id', 'post_id'], 'posts', ['blog_id', 'id'], 'CASCADE'],
        ['media_assets', 'fk_35_media_assets', ['blog_id'], 'blogs', ['id'], 'CASCADE'],
        ['notifications', 'fk_50_notifications', ['recipient_user_id'], 'users', ['id'], 'CASCADE'],
        ['pages', 'fk_22_pages', ['blog_id'], 'blogs', ['id'], 'CASCADE'],
        ['pages', 'fk_54_pages', ['og_media_id'], 'media_assets', ['id'], 'SET NULL'],
        ['platform_role_permissions', 'fk_0_platform_role_permissions', ['role_id'], 'platform_roles', ['id'], 'CASCADE'],
        ['platform_role_permissions', 'fk_2_platform_role_permissions', ['permission_id'], 'permissions', ['id'], 'CASCADE'],
        ['post_categories', 'fk_31_post_categories', ['blog_id', 'post_id'], 'posts', ['blog_id', 'id'], 'CASCADE'],
        ['post_categories', 'fk_32_post_categories', ['blog_id', 'category_id'], 'categories', ['blog_id', 'id'], 'CASCADE'],
        ['post_tags', 'fk_33_post_tags', ['blog_id', 'post_id'], 'posts', ['blog_id', 'id'], 'CASCADE'],
        ['post_tags', 'fk_34_post_tags', ['blog_id', 'tag_id'], 'tags', ['blog_id', 'id'], 'CASCADE'],
        ['posts', 'fk_25_posts', ['blog_id'], 'blogs', ['id'], 'CASCADE'],
        ['posts', 'fk_51_posts', ['cover_media_id'], 'media_assets', ['id'], 'SET NULL'],
        ['posts', 'fk_52_posts', ['og_media_id'], 'media_assets', ['id'], 'SET NULL'],
        ['tags', 'fk_30_tags', ['blog_id'], 'blogs', ['id'], 'CASCADE'],
        ['template_category_assignments', 'fk_20_template_category_assignments', ['template_id'], 'templates', ['id'], 'CASCADE'],
        ['template_category_assignments', 'fk_21_template_category_assignments', ['category_id'], 'template_categories', ['id'], 'CASCADE'],
        ['templates', 'fk_41_templates', ['preview_media_id'], 'media_assets', ['id'], 'SET NULL'],
        ['user_platform_roles', 'fk_1_user_platform_roles', ['role_id'], 'platform_roles', ['id'], 'CASCADE'],
        ['user_platform_roles', 'fk_3_user_platform_roles', ['user_id'], 'users', ['id'], 'CASCADE'],
        ['user_platform_permissions', 'fk_0_user_platform_permissions', ['user_id'], 'users', ['id'], 'CASCADE'],
        ['user_platform_permissions', 'fk_1_user_platform_permissions', ['permission_id'], 'permissions', ['id'], 'CASCADE'],
        ['user_platform_permissions', 'fk_2_user_platform_permissions', ['granted_by'], 'users', ['id'], 'RESTRICT'],
        ['users', 'fk_37_users', ['avatar_media_id'], 'media_assets', ['id'], 'SET NULL'],
    ];

    public function up(): void
    {
        $this->dropCheckIfExists('blog_designs', 'ck_blog_designs_1');
        $this->dropCheckIfExists('blog_designs', 'ck_blog_designs_2');

        foreach (self::RULES as $rule) {
            $this->replaceForeignKey(...$rule);
        }
    }

    public function down(): void
    {
        foreach (self::RULES as $rule) {
            $this->replaceForeignKey(
                $rule[0], $rule[1], $rule[2], $rule[3], $rule[4], 'RESTRICT',
            );
        }

        DB::statement('ALTER TABLE `blog_designs` ADD CONSTRAINT `ck_blog_designs_1` CHECK ((`template_id` IS NULL AND `published_data` IS NULL) OR (`template_id` IS NOT NULL AND `published_data` IS NOT NULL))');
        DB::statement('ALTER TABLE `blog_designs` ADD CONSTRAINT `ck_blog_designs_2` CHECK ((`draft_template_id` IS NULL AND `draft_data` IS NULL) OR (`draft_template_id` IS NOT NULL AND `draft_data` IS NOT NULL))');
    }

    /** @param list<string> $columns  @param list<string> $references */
    private function replaceForeignKey(
        string $table,
        string $constraint,
        array $columns,
        string $referencedTable,
        array $references,
        string $deleteRule,
    ): void {
        $columnSql = implode(', ', array_map(fn (string $column) => "`{$column}`", $columns));
        $referenceSql = implode(', ', array_map(fn (string $column) => "`{$column}`", $references));

        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` FOREIGN KEY ({$columnSql}) REFERENCES `{$referencedTable}` ({$referenceSql}) ON DELETE {$deleteRule} ON UPDATE RESTRICT");
    }

    private function dropCheckIfExists(string $table, string $constraint): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'CHECK')
            ->exists();

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP CHECK `{$constraint}`");
        }
    }
};
