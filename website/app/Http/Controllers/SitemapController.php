<?php
namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class SitemapController extends Controller
{
    public function index(): Response
    {
        $blogs = DB::table('blogs')
            ->where([
                'status' => 'PUBLISHED',
                'visibility' => 'PUBLIC',
                'discoverable' => true,
            ])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->limit(1000)
            ->get(['id', 'slug', 'updated_at']);

        $urls = [];

        foreach ($blogs as $blog) {
            $base = '/b/'.$blog->slug;
            $urls[] = url($base);

            foreach (['posts', 'pages'] as $table) {
                $paths = DB::table($table)
                    ->where([
                        'blog_id' => $blog->id,
                        'status' => 'PUBLISHED'
                    ])
                    ->whereNull('deleted_at')
                    ->where('robots', 'like', 'index,%')
                    ->orderBy('id')
                    ->limit(1000)
                    ->pluck('slug');

                foreach ($paths as $slug) {
                    $urls[] = url(
                        $base.'/'.$table.'/'.$slug
                    );
                }
            }
        }

        $escape = fn (string $s): string =>
            htmlspecialchars(
                $s,
                ENT_XML1 | ENT_QUOTES,
                'UTF-8'
            );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $location) {
            $xml .= '<url><loc>'
                .$escape($location)
                .'</loc></url>';
        }

        $xml .= '</urlset>';

        return response(
            $xml,
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8']
        );
    }
}
