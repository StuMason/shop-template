<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Static content pages backed by markdown files in resources/markdown.
 * Template users edit the files (version-controlled); the first H1 becomes
 * the page title.
 */
class PageController extends Controller
{
    public function show(string $page): Response
    {
        abort_unless(in_array($page, self::available(), true), 404);

        $markdown = (string) file_get_contents(resource_path("markdown/{$page}.md"));

        $title = Str::of($markdown)->match('/^#\s+(.+)$/m')->toString() ?: Str::headline($page);

        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return Inertia::render('storefront/pages/show', [
            'page' => [
                'slug' => $page,
                'title' => $title,
                'html' => $html,
            ],
        ]);
    }

    /**
     * Slugs that have a markdown file.
     *
     * @return array<int, string>
     */
    public static function available(): array
    {
        return collect(glob(resource_path('markdown/*.md')) ?: [])
            ->map(fn (string $path): string => basename($path, '.md'))
            ->sort()
            ->values()
            ->all();
    }
}
