@php
    $phoneHref = !empty($contact['phone']) ? preg_replace('/[^+0-9]/', '', $contact['phone']) : null;
    $channels = array_values(
        array_filter([
            !empty($contact['phone'])
                ? [
                    'label' => __('public.phone'),
                    'value' => $contact['phone'],
                    'href' => 'tel:' . $phoneHref,
                    'icon' => 'fa-solid fa-phone',
                ]
                : null,
            !empty($contact['email'])
                ? [
                    'label' => __('ui.email'),
                    'value' => $contact['email'],
                    'href' => 'mailto:' . $contact['email'],
                    'icon' => 'fa-solid fa-envelope',
                ]
                : null,
            !empty($contact['facebookUrl'])
                ? [
                    'label' => 'Facebook',
                    'value' => 'Facebook',
                    'href' => $contact['facebookUrl'],
                    'icon' => 'fa-brands fa-facebook',
                ]
                : null,
            !empty($contact['youtubeUrl'])
                ? [
                    'label' => 'YouTube',
                    'value' => 'YouTube',
                    'href' => $contact['youtubeUrl'],
                    'icon' => 'fa-brands fa-youtube',
                ]
                : null,
            !empty($contact['tiktokUrl'])
                ? [
                    'label' => 'TikTok',
                    'value' => 'TikTok',
                    'href' => $contact['tiktokUrl'],
                    'icon' => 'fa-brands fa-tiktok',
                ]
                : null,
            !empty($contact['zaloUrl'])
                ? [
                    'label' => 'Zalo',
                    'value' => 'Zalo',
                    'href' => $contact['zaloUrl'],
                    'icon' => 'fa-solid fa-comment-dots',
                ]
                : null,
        ]),
    );
@endphp
<main class="le-contact-page">
    <div>
        <i class="fa-solid fa-envelope le-mark" aria-hidden="true"></i>
        <p class="le-kicker">{{ __('public.contact') }}</p>
        <h1>
            {{
                $contact['title'] ??
                    __('public.contact_title')
            }}
        </h1>
        <p>{{
            $contact['description'] ??
                __('public.contact_description')
        }}</p>
        @if ($channels !== [])
            <div class="le-contact-links" aria-label="{{ __('public.contact_channels') }}">
                @foreach ($channels as $channel)
                    <a
                        href="{{ $channel['href'] }}"
                        @if (str_starts_with($channel['href'], 'http')) target="_blank" rel="noopener noreferrer" @endif
                    >
                        <span class="le-contact-icon" aria-hidden="true"
                            ><i class="{{ $channel['icon'] }}"></i
                        ></span>
                        <span
                            ><strong>{{ $channel['label'] }}</strong
                            ><small>{{ $channel['value'] }}</small></span
                        >
                    </a>
                @endforeach
            </div>
        @endif
        <a
            class="le-page-cta"
            href="{{ $contact['ctaUrl'] ?? 'mailto:'.($contact['email'] ?? 'hello@example.com') }}"
            >{{
                $contact['ctaLabel'] ??
                    __('public.send_message')
            }} <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i
        ></a>
    </div>
</main>
