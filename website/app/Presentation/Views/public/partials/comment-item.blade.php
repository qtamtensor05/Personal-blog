@php
    $commentId = (int) $comment['id'];
    $base = [$blog->slug, $post->slug, $commentId];
    $hidden = (bool) ($comment['is_owner_hidden'] ?? false);
    $adminHidden = (bool) ($comment['is_admin_hidden'] ?? false);
    $placeholder = (bool) ($comment['placeholder'] ?? false);
    $hasMenu = ! $placeholder && ($comment['can_edit'] || $comment['can_delete'] || $comment['can_hide'] || $comment['can_unhide'] || $comment['can_report']);
@endphp

<article id="comment-{{ $commentId }}" class="bm-comment {{ $hidden ? 'bm-comment--owner-hidden' : '' }} {{ $adminHidden ? 'bm-comment--admin-hidden' : '' }} {{ $placeholder ? 'bm-comment--placeholder' : '' }}" data-comment-id="{{ $commentId }}">
    <div class="bm-comment__surface">
        @if ($placeholder)
            <p class="bm-comment__placeholder">Bình luận không còn hiển thị.</p>
        @else
            <header class="bm-comment__header">
                <span class="bm-comment__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) ($comment['author_name'] ?? '?'), 0, 1)) }}</span>
                <div class="bm-comment__identity">
                    <strong class="bm-comment__author">{{ $comment['author_name'] }}</strong>
                    @if ($hidden)<span class="bm-comment__state">Chủ bài đã ẩn</span>@elseif ($adminHidden)<span class="bm-comment__state">Admin đã ẩn</span>@endif
                </div>
                <time class="bm-comment__time">{{ $comment['created_at'] }}</time>
                @if ($hasMenu)
                    <details class="bm-comment-menu">
                        <summary class="bm-comment-menu__trigger" aria-label="Tùy chọn bình luận">@include('public.partials.comment-icon', ['name' => 'more'])</summary>
                        <div class="bm-comment-menu__panel">
                            @if ($comment['can_edit'])
                                <button type="button" class="bm-comment-menu__action" data-bm-toggle="edit" data-target="edit-comment-{{ $commentId }}">@include('public.partials.comment-icon', ['name' => 'edit'])<span>Chỉnh sửa</span></button>
                            @endif
                            @if ($comment['can_delete'])
                                <form method="POST" action="{{ route('public.comments.destroy', $base) }}">@csrf @method('DELETE')<button type="submit" class="bm-comment-menu__action bm-comment-menu__action--danger">@include('public.partials.comment-icon', ['name' => 'trash'])<span>Xóa</span></button></form>
                            @endif
                            @if ($comment['can_hide'])
                                <form method="POST" action="{{ route('public.comments.hide', $base) }}">@csrf<button type="submit" class="bm-comment-menu__action">@include('public.partials.comment-icon', ['name' => 'hide'])<span>Ẩn bình luận</span></button></form>
                            @endif
                            @if ($comment['can_unhide'])
                                <form method="POST" action="{{ route('public.comments.unhide', $base) }}">@csrf<button type="submit" class="bm-comment-menu__action">@include('public.partials.comment-icon', ['name' => 'show'])<span>Hiện lại bình luận</span></button></form>
                            @endif
                            @if ($comment['can_report'])
                                <button type="button" class="bm-comment-menu__action" data-bm-toggle="report" data-target="report-comment-{{ $commentId }}">@include('public.partials.comment-icon', ['name' => 'flag'])<span>Báo cáo</span></button>
                            @endif
                        </div>
                    </details>
                @endif
            </header>
            <div class="bm-comment__body">{{ $comment['body'] }}</div>
            @if ($hidden)<span class="bm-comment__state">Bình luận đã bị ẩn</span>@endif
            <div class="bm-comment__footer">
                @if ($comment['can_reply'])<button type="button" class="bm-comment__reply-btn" data-bm-toggle="reply" data-target="reply-comment-{{ $commentId }}" aria-expanded="false">@include('public.partials.comment-icon', ['name' => 'reply'])<span>Trả lời</span></button>@endif
            </div>
            @if ($comment['can_reply'])
                <form id="reply-comment-{{ $commentId }}" class="bm-comment__composer" hidden method="POST" action="{{ route('public.comments.store', [$blog->slug, $post->slug]) }}">
                    @csrf <input type="hidden" name="parent_id" value="{{ $commentId }}" />
                    <label class="bm-comment__label" for="reply-body-{{ $commentId }}">Trả lời {{ $comment['author_name'] }}</label>
                    <textarea id="reply-body-{{ $commentId }}" name="body" rows="2" maxlength="2000" required placeholder="Viết câu trả lời..."></textarea>
                    <div class="bm-comment__composer-actions"><button type="button" class="bm-comment__cancel" data-bm-close="reply-comment-{{ $commentId }}">Hủy</button><button type="submit" class="bm-comment__submit">Gửi trả lời</button></div>
                </form>
            @endif
            @if ($comment['can_edit'])
                <form id="edit-comment-{{ $commentId }}" class="bm-comment__composer" hidden method="POST" action="{{ route('public.comments.update', $base) }}">
                    @csrf @method('PATCH') <label class="bm-comment__label" for="edit-body-{{ $commentId }}">Chỉnh sửa bình luận</label><textarea id="edit-body-{{ $commentId }}" name="body" rows="2" maxlength="2000" required>{{ $comment['body'] }}</textarea>
                    <div class="bm-comment__composer-actions"><button type="button" class="bm-comment__cancel" data-bm-close="edit-comment-{{ $commentId }}">Hủy</button><button type="submit" class="bm-comment__submit">Lưu thay đổi</button></div>
                </form>
            @endif
            @if ($comment['can_report'])
                <div id="report-comment-{{ $commentId }}" class="bm-comment__composer" hidden>
                    <form method="POST" action="{{ route('public.comments.report', $base) }}">@csrf<label class="bm-comment__label" for="report-reason-{{ $commentId }}">Lý do báo cáo</label><textarea id="report-reason-{{ $commentId }}" name="reason" rows="2" maxlength="2000" required></textarea><div class="bm-comment__composer-actions"><button type="button" class="bm-comment__cancel" data-bm-close="report-comment-{{ $commentId }}">Hủy</button><button type="submit" class="bm-comment__submit">Gửi báo cáo</button></div></form>
                </div>
            @endif
        @endif
    </div>
    <div class="comment-replies" data-replies-for="{{ $commentId }}"></div>
    @if ($comment['replies_count'] > 0)<button type="button" class="bm-comment__load-replies" data-comment-action="load-replies" data-page="1" data-url="{{ route('public.comments.replies', [$blog->slug, $post->slug, $commentId]) }}">Xem phản hồi ({{ $comment['replies_count'] }})</button>@endif
</article>
