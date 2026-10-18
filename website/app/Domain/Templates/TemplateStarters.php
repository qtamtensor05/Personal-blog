<?php

declare(strict_types=1);

namespace App\Domain\Templates;

use App\Domain\Templates\ValueObjects\TemplateData;
use App\Domain\Templates\ValueObjects\TemplateDesignTokens;
use App\Domain\Templates\ValueObjects\TemplatePostLayout;
use App\Domain\Templates\ValueObjects\TemplateSectionConfig;

final class TemplateStarters
{
    public static function blog(): TemplateData
    {
        return new TemplateData([
            new TemplateSectionConfig('site-header', 'header', [], 'header', 'Header'),
            new TemplateSectionConfig('hero', 'hero', [], 'body', 'Banner chính'),
            new TemplateSectionConfig('latest-posts', 'recent_posts', [], 'body', 'Bài viết mới'),
            new TemplateSectionConfig('categories', 'categories', [], 'body', 'Chuyên mục'),
            new TemplateSectionConfig('sidebar-profile', 'profile', [], 'sidebar', 'Giới thiệu blogger'),
            new TemplateSectionConfig('site-footer', 'footer', [], 'footer', 'Footer'),
        ], TemplateDesignTokens::default(), TemplatePostLayout::default());
    }

    public static function lifestyleEditorial(): TemplateData
    {
        return new TemplateData([
            new TemplateSectionConfig('site-header', 'header', [
                'title' => 'Turning Heads',
                'description' => 'Những câu chuyện nhỏ, cảm hứng lớn.',
            ], 'header', 'Nhận diện và menu'),
            new TemplateSectionConfig('hero', 'hero', [
                'eyebrow' => 'Nhật ký sống chậm',
                'title' => 'My Life. My Blog.',
                'description' => 'Một góc nhỏ dành cho những câu chuyện đời thường, hành trình và nguồn cảm hứng mỗi ngày.',
                'ctaLabel' => 'Khám phá bài viết',
                'ctaUrl' => '#latest-posts',
            ], 'body', 'Ảnh bìa chính'),
            new TemplateSectionConfig('latest-posts', 'recent_posts', [
                'title' => 'Bài viết mới nhất',
                'archiveTitle' => 'Tất cả bài viết',
                'archiveDescription' => 'Những câu chuyện, trải nghiệm và cảm hứng mới nhất từ blog.',
                'limit' => 3,
            ], 'body', 'Bài viết mới'),
            new TemplateSectionConfig('about', 'about', [
                'title' => 'About me',
                'description' => 'Đây là nơi bạn giới thiệu câu chuyện, phong cách sống và những điều mình muốn chia sẻ với độc giả.',
            ], 'body', 'Giới thiệu'),
            new TemplateSectionConfig('gallery', 'gallery', [
                'title' => 'Những khoảnh khắc',
            ], 'body', 'Bộ sưu tập ảnh'),
            new TemplateSectionConfig('sidebar-profile', 'profile', [
                'title' => 'Góc nhỏ của tôi',
                'description' => 'Xin chào, mình là người kể chuyện phía sau blog này.',
            ], 'sidebar', 'Thông tin blogger'),
            new TemplateSectionConfig('newsletter', 'newsletter', [
                'title' => 'Nhận bài viết mới',
                'description' => 'Đăng ký để không bỏ lỡ những câu chuyện tiếp theo.',
                'ctaLabel' => 'Đăng ký',
            ], 'sidebar', 'Newsletter'),
            new TemplateSectionConfig('contact', 'cta', [
                'title' => "Let me know what's on your mind",
                'description' => 'Bạn có một câu chuyện muốn chia sẻ? Hãy gửi lời nhắn cho tôi.',
                'ctaLabel' => 'Liên hệ',
                'ctaUrl' => 'mailto:hello@example.com',
            ], 'body', 'Liên hệ'),
            new TemplateSectionConfig('site-footer', 'footer', [
                'description' => 'Những câu chuyện mới được gửi đến bạn mỗi tuần.',
                'copyright' => '© 2026 Turning Heads. All rights reserved.',
            ], 'footer', 'Footer'),
        ], TemplateDesignTokens::default(), TemplatePostLayout::default(), TemplateLayouts::LIFESTYLE_EDITORIAL);
    }
}
