<?php

return [
    // Local storage is the development fallback when Cloudinary is not configured.
    'driver' => env('MEDIA_DRIVER', env('CLOUDINARY_URL') ? 'cloudinary' : 'local'),

    'local_disk' => env('MEDIA_LOCAL_DISK', 'public'),

    // Standard Cloudinary URL: cloudinary://<api_key>:<api_secret>@<cloud_name>
    'cloudinary_url' => env('CLOUDINARY_URL'),

    // Optional prefix so several environments can share one Cloudinary account.
    'folder_prefix' => env('CLOUDINARY_FOLDER_PREFIX', 'personal-blog'),

    'max_upload_kb' => (int) env('MEDIA_MAX_UPLOAD_KB', 5120),

    // SVG is excluded on purpose: it can carry scripts.
    'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'mov'],

    // Used when a blog has no blog_settings row yet (matches the column default).
    'default_blog_quota_bytes' => 104857600,
];
