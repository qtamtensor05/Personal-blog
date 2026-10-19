<main class="le-contact-page">
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
