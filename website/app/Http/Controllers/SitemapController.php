<?php
namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class SitemapController extends Controller
{
    private const URLS_PER_FILE = 50000;

    public function index(): Response
    {
        $total = $this->urlsQuery()->count();
        $pages = (int) ceil($total / self::URLS_PER_FILE);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        for ($page = 1; $page <= $pages; $page++) {
            $location = url('/sitemaps/'.$page.'.xml');
            $xml .= '<sitemap><loc>'.$this->escape($location).'</loc></sitemap>';
        }

        $xml .= '</sitemapindex>';

        return $this->xml($xml);
    }

    public function part(int $page): Response
    {
        abort_if($page < 1, 404);

        $urls = $this->urlsQuery()
            ->orderBy('loc')
            ->offset(($page - 1) * self::URLS_PER_FILE)
            ->limit(self::URLS_PER_FILE)
            ->pluck('loc');

        abort_if($urls->isEmpty(), 404);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $location) {
            $xml .= '<url><loc>'.$this->escape((string) $location).'</loc></url>';
        }

        $xml .= '</urlset>';

        return $this->xml($xml);
    }

    private function urlsQuery(): Builder
    {
        $base = rtrim(url('/'), '/');

        $blogs = DB::table('blogs as b')
            ->where('b.status', 'PUBLISHED')
            ->where('b.visibility', 'PUBLIC')
            ->where('b.discoverable', true)
            ->whereNull('b.deleted_at')
            ->selectRaw("CONCAT(?, '/b/', b.slug) AS loc", [$base]);

        $posts = $this->contentUrls('posts', $base);
        $pages = $this->contentUrls('pages', $base);

        $all = $blogs->unionAll($posts)->unionAll($pages);

        return DB::query()->fromSub($all, 'sitemap_urls');
    }

    private function contentUrls(string $table, string $base): Builder
    {
        // Only called with the two fixed content table names.
        $path = '/'.$table.'/';

        return DB::table($table.' as c')
            ->join('blogs as b', 'b.id', '=', 'c.blog_id')
            ->where('b.status', 'PUBLISHED')
            ->where('b.visibility', 'PUBLIC')
            ->where('b.discoverable', true)
            ->whereNull('b.deleted_at')
            ->where('c.status', 'PUBLISHED')
            ->whereNull('c.deleted_at')
            ->where('c.robots', 'like', 'index,%')
            ->where(function (Builder $query) use ($base, $path): void {
                $query->whereNull('c.canonical_url')
                    ->orWhere('c.canonical_url', '')
                    ->orWhereRaw(
                        "c.canonical_url = CONCAT(?, '/b/', b.slug, ?, c.slug)",
                        [$base, $path]
                    );
            })
            ->selectRaw(
                "CONCAT(?, '/b/', b.slug, ?, c.slug) AS loc",
                [$base, $path]
            );
    }

    private function xml(string $body): Response
    {
        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}