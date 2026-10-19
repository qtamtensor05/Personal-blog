@php
    $phoneHref = !empty($contact['phone']) ? preg_replace('/[^+0-9]/', '', $contact['phone']) : null;
    $channels = array_values(
        array_filter([
            !empty($contact['phone'])
                ? [
                    'label' => 'Điện thoại',
                    'value' => $contact['phone'],
                    'href' => 'tel:' . $phoneHref,
                    'icon' => '☎',
                ]
                : null,
            !empty($contact['email'])
                ? [
                    'label' => 'Email',
                    'value' => $contact['email'],
                    'href' => 'mailto:' . $contact['email'],
                    'icon' => '✉',
                ]
                : null,
            !empty($contact['facebookUrl'])
                ? [
                    'label' => 'Facebook',
                    'value' => 'Facebook',
                    'href' => $contact['facebookUrl'],
                    'icon' => 'f',
                ]
                : null,
            !empty($contact['youtubeUrl'])
                ? [
                    'label' => 'YouTube',
                    'value' => 'YouTube',
                    'href' => $contact['youtubeUrl'],
                    'icon' => '▶',
                ]
                : null,
            !empty($contact['tiktokUrl'])
                ? [
                    'label' => 'TikTok',
                    'value' => 'TikTok',
                    'href' => $contact['tiktokUrl'],
                    'icon' => '♪',
                ]
                : null,
            !empty($contact['zaloUrl'])
                ? ['label' => 'Zalo', 'value' => 'Zalo', 'href' => $contact['zaloUrl'], 'icon' => 'Z']
                : null,
        ]),
    );
@endphp
<main class="le-contact-page">
    <div>
        <p class="le-mark" aria-hidden="true">✦</p>
        <p class="le-kicker">Kết nối</p>
        <h1>
            {{
                $contact['title'] ??
                    'Liên hệ với tôi'
            }}
        </h1>
        <p>{{
            $contact['description'] ??
                'Hãy kết nối qua một trong những kênh bên dưới.'
        }}</p>
        @if ($channels !== [])
            <div class="le-contact-links" aria-label="Các kênh liên hệ">
                @foreach ($channels as $channel)
                    <a
                        href="{{ $channel['href'] }}"
                        @if (str_starts_with($channel['href'], 'http')) target="_blank" rel="noopener noreferrer" @endif
                    >
                        <span
                            class="le-contact-icon"
                            aria-hidden="true"
                            >{{ $channel['icon'] }}</span
                        >
                        <span
                            ><strong>{{ $channel['label'] }}</strong
                            ><small>{{ $channel['value'] }}</small></span
                        >
                    </a>
                @endforeach
            </div>
        @endif
    </div>
    <div>
        <p class="le-mark">✤</p>
        <p class="le-kicker">Kết nối</p>
        <h1>
            {{
                $contact['title'] ??
                    "Let me know what's on your mind"
            }}
        </h1>
        <p>{{
            $contact['description'] ??
                'Bạn có một câu chuyện muốn chia sẻ? Hãy gửi lời nhắn cho tôi.'
        }}</p>
        <a href="{{ $contact['ctaUrl'] ?? 'mailto:hello@example.com' }}">{{
            $contact['ctaLabel'] ??
                'Gửi lời nhắn'
        }}</a>
    </div>
</main>
