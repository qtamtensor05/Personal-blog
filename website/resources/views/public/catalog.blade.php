<x-layouts.app :title="$title">
    <section class="page-head">
        <p class="eyebrow">THƯ VIỆN</p>
        <h1>{{ $title }}</h1>
    </section>
    <div class="grid">
        @forelse ($items as $item)
            <article class="card">
                <h2>
                    @if (isset($item->visibility))
                        <a href="/b/{{ $item->slug }}">{{ $item->name }}</a>
                    @else
                        {{ $item->name }}
                    @endif
                </h2>
                <p>{{ $item->description }}</p>
            </article>
        @empty
            <p>Chưa có nội dung phù hợp.</p>
        @endforelse
    </div>
    {{ $items->links() }}</x-layouts.app
>
