@if ($layout->showCover && $cover && $post->coverMediaId !== null && ! in_array($post->coverMediaId, $post->content->mediaIds(), true))
    <div data-image-frame class="media-frame mb-8 overflow-hidden rounded-md">
        <img
            src="{{ $cover['url'] }}"
            alt=""
            class="media-image block aspect-video w-full rounded-md object-cover"
            data-image-reveal
            @if ($cover['width'] && $cover['height']) width="{{ $cover['width'] }}" height="{{ $cover['height'] }}" @endif
        />
    </div>
@endif
