<section class="comments-section mt-12 border-t pt-8" aria-labelledby="comments-heading">
    <h2 id="comments-heading" class="text-primary mb-4 text-2xl font-semibold">Bình luận ({{ $comments['total'] }})</h2>
    @if (session('comment_status'))
        <p class="mb-4 text-sm font-semibold" role="status">{{ session('comment_status') }}</p>
    @endif
    @auth
        <form method="POST" action="{{ route('public.comments.store', [$blog->slug, $post->slug]) }}" class="card mb-6 space-y-3">
            @csrf
            <label for="comment-body" class="text-primary block text-sm font-medium">Viết bình luận</label>
            <textarea id="comment-body" name="body" rows="3" maxlength="2000" required class="textarea" placeholder="Chia sẻ suy nghĩ của bạn..."></textarea>
            <button class="btn-primary rounded-md px-4 py-2" type="submit">Gửi bình luận</button>
        </form>
    @else
        <p class="text-secondary mb-6 text-sm"><a class="underline" href="{{ route('login') }}">Đăng nhập</a> để bình luận.</p>
    @endauth
    @forelse ($comments['items'] as $comment)
        @include('public.partials.comment-item', ['comment' => $comment])
    @empty
        <p class="text-secondary">Chưa có bình luận nào.</p>
    @endforelse
</section>
