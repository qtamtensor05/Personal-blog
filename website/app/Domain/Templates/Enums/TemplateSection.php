<?php

declare(strict_types=1);

namespace App\Domain\Templates\Enums;

/**
 * Allowed sections a template can include. Each section defines its own layout and component slots.
 */
enum TemplateSection: string
{
    case Header = 'header';
    case Navigation = 'navigation';
    // Top of the blog: title, subtitle, hero image or illustration.
    case Hero = 'hero';

    // Standalone image block with a configurable placement.
    case Image = 'image';
    case ImageText = 'image_text';
    case Gallery = 'gallery';
    case Cta = 'cta';

    // Full-page Tiptap document for flexible template composition.
    case Document = 'document';

    // List of recent posts, with configurable count and layout (grid, list).
    case RecentPosts = 'recent_posts';

    // Featured post spotlight, shown above or beside recent posts.
    case FeaturedPost = 'featured_post';

    // Categories navigation, as cards or a list.
    case Categories = 'categories';

    // About text and author info (name, bio, avatar).
    case About = 'about';
    case Profile = 'profile';
    case SidebarPosts = 'sidebar_posts';

    // Newsletter signup form.
    case Newsletter = 'newsletter';

    // Footer: copyright, links, social icons.
    case Footer = 'footer';
}
