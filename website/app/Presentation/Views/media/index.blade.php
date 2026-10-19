@extends ('layouts.app')

@section ('title', 'Media - ' . $blog->name)

@section ('content')
    <div class="container-custom section-spacing max-w-6xl" data-media-library>
        <div class="mb-8">
            <a
                href="{{ route('blogs.show', $blog->id) }}"
                class="text-secondary hover:text-accent-primary text-sm"
                >← {{ $blog->name }}</a
            >
            <h1 class="text-hero mt-2">Thư viện Media</h1>
            <p class="text-secondary">Tải hình ảnh hoặc video lên một lần để chọn lại trong cấu hình template và bài viết.</p>
        </div>

        <x-alert type="error" class="mb-6 hidden" data-media-error></x-alert>

        <section class="card mb-8">
            <h2 class="text-primary text-lg font-semibold">Thêm Media</h2>
            <form
                class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end"
                data-media-upload
            >
                <div>
                    <label for="media-file" class="text-primary mb-1 block text-sm font-medium"
                        >Hình ảnh hoặc video</label
                    >
                    <input
                        id="media-file"
                        name="file"
                        type="file"
                        required
                        accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"
                        class="input"
                    />
                    <p class="text-secondary mt-1 text-xs">Ảnh: JPG, PNG, WEBP, GIF. Video: MP4, WEBM, MOV. Tối đa {{ config('media.max_upload_kb') / 1024 }} MB.</p>
                </div>
                <x-input
                    name="alt_text"
                    label="Mô tả Media"
                    maxlength="500"
                    hint="Mô tả ngắn giúp nhận biết và hỗ trợ accessibility."
                />
                <button type="submit" class="btn-primary" data-media-submit>Tải lên</button>
            </form>
        </section>

        <section>
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="text-primary text-xl font-semibold">Media của blog</h2>
                <span class="text-secondary text-sm">{{ count($assets) }} tệp</span>
            </div>
            @if (count($assets) === 0)
                <x-empty-state message="Chưa có Media"
                    >Tải hình ảnh hoặc video đầu tiên ở phía trên.</x-empty-state
                >
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-media-grid>
                    @foreach ($assets as $asset)
                        <article
                            class="card overflow-hidden p-0"
                            data-media-item="{{ $asset->id }}"
                        >
                            <div class="bg-surface-secondary aspect-video">
                                @if ($asset->resourceType === 'video')
                                    <video
                                        src="{{ $asset->secureUrl }}"
                                        controls
                                        preload="metadata"
                                        class="h-full w-full object-cover"
                                    ></video>
                                @else
                                    <img
                                        src="{{ $asset->secureUrl }}"
                                        alt="{{ $asset->altText ?? '' }}"
                                        class="h-full w-full object-cover"
                                    />
                                @endif
                            </div>
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-primary truncate font-medium">{{
                                            $asset->altText ?:
                                                $asset->originalName
                                        }}</p>
                                        <p class="text-secondary mt-1 text-xs">#{{ $asset->id }} · {{
                                            $asset->resourceType === 'video'
                                                ? 'Video'
                                                : 'Hình ảnh'
                                        }} · {{ number_format(($asset->bytes ?? 0) / 1024, 1) }} KB</p>
                                    </div>
                                    <button
                                        type="button"
                                        class="text-sm text-red-500 hover:underline"
                                        data-media-delete="{{ route('media.destroy', $asset->id) }}"
                                    >
                                        Xóa
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection

@push ('scripts')
    <script>
        (() => {
            const root = document.querySelector('[data-media-library]');
            if (!root) return;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const error = root.querySelector('[data-media-error]');
            const showError = (message) => {
                error.textContent = message;
                error.classList.remove('hidden');
            };

            root.querySelector('[data-media-upload]').addEventListener('submit', async (event) => {
                event.preventDefault();
                error.classList.add('hidden');
                const button = event.currentTarget.querySelector('[data-media-submit]');
                const file = event.currentTarget.querySelector('[name="file"]').files[0];
                const maxBytes = {{
                    (int) config('media.max_upload_kb') *
                        1024
                }};
                if (!file) return showError('Vui lòng chọn hình ảnh hoặc video để tải lên.');
                if (file.size > maxBytes)
                    return showError('Tệp không được lớn hơn {{
                        config('media.max_upload_kb') /
                            1024
                    }} MB.');
                const body = new FormData(event.currentTarget);
                body.append('scope', 'BLOG');
                body.append('blog_id', '{{ $blog->id }}');
                button.disabled = true;
                button.textContent = 'Đang tải...';
                try {
                    const response = await fetch('{{ route('media.store') }}', {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                        body,
                    });
                    const result = await response.json().catch(() => ({}));
                    if (!response.ok)
                        throw new Error(
                            result.message ??
                                Object.values(result.errors ?? {})[0]?.[0] ??
                                'Không thể tải Media.',
                        );
                    window.location.reload();
                } catch (exception) {
                    showError(exception.message);
                    button.disabled = false;
                    button.textContent = 'Tải lên';
                }
            });

            root.querySelectorAll('[data-media-delete]').forEach((button) =>
                button.addEventListener('click', async () => {
                    const accepted = await window.requestModalConfirmation(
                        'Xóa Media',
                        'Media đang được sử dụng sẽ không thể xóa. Bạn có muốn tiếp tục?',
                    );
                    if (!accepted) return;
                    const response = await fetch(button.dataset.mediaDelete, {
                        method: 'DELETE',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    });
                    if (response.ok) return button.closest('[data-media-item]').remove();
                    const result = await response.json().catch(() => ({}));
                    showError(result.message ?? 'Không thể xóa Media.');
                }),
            );
        })();
    </script>
@endpush
