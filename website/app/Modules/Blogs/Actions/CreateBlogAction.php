<?php

namespace App\Modules\Blogs\Actions;

use App\Models\User;
use App\Modules\Blogs\Support\BlogSlugGenerator;
use App\Modules\Design\DesignDocument;
use App\Modules\Platform\Contracts\PlatformConfig;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateBlogAction
{
    private const MYSQL_DUPLICATE_ENTRY = 1062;

    public function __construct(
        private readonly PlatformConfig $config,
        private readonly BlogSlugGenerator $slugs,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(User $actor, array $input): int
    {
        if (! $actor->canAccessApplication()) {
            throw ValidationException::withMessages([
                'account' => 'Tài khoản phải được xác minh và đang hoạt động.',
            ]);
        }

        try {
            return DB::transaction(fn (): int => $this->create($actor, $input));
        } catch (QueryException $e) {
            // A concurrent request won the unique-slug race after our pre-check.
            if (($e->errorInfo[1] ?? null) === self::MYSQL_DUPLICATE_ENTRY) {
                throw ValidationException::withMessages([
                    'slug' => 'Đường dẫn đã được sử dụng bởi request đồng thời. Hãy thử lại với đường dẫn khác.',
                ]);
            }

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function create(User $actor, array $input): int
    {
        // Lock the user row to serialize quota checks for the same owner.
        DB::table('users')->where('id', $actor->id)->lockForUpdate()->first();

        $template = $this->findSelectableTemplate($input);
        $this->assertUnderBlogLimit($actor);

        $slug = $this->slugs->fromInput($input);

        if (DB::table('blogs')->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['slug' => 'Đường dẫn đã được sử dụng. Hãy chọn đường dẫn khác.']);
        }

        $blogId = $this->insertBlog($actor, $input, $slug);
        $this->insertSettings($blogId);

        if ($template) {
            $this->insertDesign($actor, $blogId, $template);
        }

        return $blogId;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function findSelectableTemplate(array $input): ?object
    {
        if (! isset($input['template_id'])) {
            return null;
        }

        $template = DB::table('templates')
            ->where('id', $input['template_id'])
            ->where('status', 'PUBLISHED')
            ->whereNull('deleted_at')
            ->sharedLock()
            ->first();

        if (! $template) {
            throw ValidationException::withMessages(['template_id' => 'Mẫu không còn cho phép chọn. Hãy chọn lại.']);
        }

        return $template;
    }

    private function assertUnderBlogLimit(User $actor): void
    {
        $limit = (int) $this->config->get('limits.maxBlogsPerUser', 3);

        $count = DB::table('blogs')
            ->where('owner_user_id', $actor->id)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->count();

        if ($count >= $limit) {
            throw ValidationException::withMessages(['name' => "Bạn đã đạt giới hạn {$limit} blog."]);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function insertBlog(User $actor, array $input, string $slug): int
    {
        return DB::table('blogs')->insertGetId([
            'owner_user_id' => $actor->id,
            'name' => $input['name'],
            'slug' => $slug,
            'description' => $input['description'] ?? null,
            'status' => 'DRAFT',
            'visibility' => 'PUBLIC',
            'discoverable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertSettings(int $blogId): void
    {
        DB::table('blog_settings')->insert([
            'blog_id' => $blogId,
            'locale' => 'vi',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'posts_per_page' => 10,
            'storage_quota_bytes' => (int) $this->config->get('limits.defaultStorageQuotaBytes', 104857600),
            'post_limit' => $this->config->get('limits.postsPerBlog'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertDesign(User $actor, int $blogId, object $template): void
    {
        DB::table('blog_designs')->insert([
            'blog_id' => $blogId,
            'draft_template_id' => $template->id,
            'draft_data' => json_encode(DesignDocument::snapshot($template), JSON_THROW_ON_ERROR),
            'updated_by' => $actor->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
