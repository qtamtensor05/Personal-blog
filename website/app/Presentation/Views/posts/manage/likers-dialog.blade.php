<dialog
    data-likers-dialog
    data-loading-message="{{ __('ui.loading_page') }}"
    data-error-message="{{ __('post.likers_load_failed') }}"
    data-empty-message="{{ __('post.no_likers') }}"
    data-previous-label="{{ __('notifications.previous') }}"
    data-next-label="{{ __('notifications.next') }}"
    data-pagination-label="{{ __('notifications.pagination') }}"
    class="border-default bg-surface text-primary fixed inset-0 z-50 m-auto max-h-[80vh] w-[calc(100vw-2rem)] max-w-lg rounded-xl border p-0 shadow-2xl backdrop:bg-black/60"
    aria-label="{{ __('post.likers') }}"
>
    <div class="border-default flex items-start justify-between gap-4 border-b px-5 py-4">
        <span class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700">
            <svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor" class="text-rose-600" aria-hidden="true">
                <path d="M12 21 3.5 12.4a5.5 5.5 0 0 1 7.8-7.8L12 5.3l.7-.7a5.5 5.5 0 0 1 7.8 7.8L12 21Z" />
            </svg>
            <span data-likers-count-value>0</span>
        </span>
        <button type="button" data-likers-close class="btn-ghost rounded-full px-3 py-1 text-xl" aria-label="{{ __('post.close_likers') }}">&times;</button>
    </div>
    <div data-likers-content class="max-h-[65vh] overflow-y-auto px-5 pb-4" aria-live="polite"></div>
</dialog>
