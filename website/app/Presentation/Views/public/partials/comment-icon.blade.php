@php($name = (string) ($name ?? 'more'))
<svg class="bm-comment-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('more')<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>@break
        @case('reply')<path d="M9 17 4 12l5-5"/><path d="M4 12h10a6 6 0 0 1 6 6v1"/>@break
        @case('edit')<path d="M12 20h9"/><path d="m16.5 3.5 4 4L10 18H6v-4L16.5 3.5Z"/>@break
        @case('trash')<path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15M10 10v7M14 10v7"/>@break
        @case('hide')<path d="M3 3 21 21M10.8 10.8a2 2 0 0 0 2.4 2.4"/><path d="M9.9 5.2a11 11 0 0 1 2.1-.2c5.5 0 9 7 9 7a14 14 0 0 1-3.4 4.1M6.2 6.2C3.9 7.8 3 12 3 12s3.5 7 9 7c1.2 0 2.2-.3 3.2-.7"/>@break
        @case('show')<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>@break
        @case('flag')<path d="M5 21V5M5 5c4-3 7 3 11 0v11c-4 3-7-3-11 0"/>@break
    @endswitch
</svg>
