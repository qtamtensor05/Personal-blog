@extends ('layouts.app')

@section ('title', 'Pages — ' . $blog->name)

@section ('content')
    <div class="container-custom section-spacing max-w-5xl">
        <div class="mb-8">
            <a
                href="{{ route('blogs.show', $blog->id) }}"
                class="text-secondary hover:text-accent-primary text-sm"
            >
                <i class="fa-solid fa-arrow-left"></i>
                {{ $blog->name }}
            </a>
            <h1 class="text-hero mt-2">{{ __('pages.title') }}</h1>
            <p class="text-secondary">{{ __('pages.help') }}</p>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        <section class="card mb-8">
            <h2 class="text-primary mb-4 text-lg font-semibold">{{ __('pages.new') }}</h2>
            <form
                method="POST"
                action="{{ route('blogs.pages.store', $blog->id) }}"
                class="grid items-end gap-3 sm:grid-cols-[1fr_1fr_auto]"
            >
                @csrf
                <x-input name="title" :label="__('pages.title_label')" required maxlength="160" />
                <x-input name="slug" :label="__('pages.slug')" required maxlength="80" :hint="__('pages.slug_hint')" />
                <button type="submit" class="btn-primary">{{ __('pages.create_write') }}</button>
            </form>
        </section>

        @if (count($pages) === 0)
            <x-empty-state :message="__('pages.empty')"
                >{{ __('pages.empty_help') }}</x-empty-state
            >
        @else
            <div class="card overflow-x-auto p-0">
                <table class="w-full text-sm">
                    <thead class="bg-surface-secondary border-default border-b">
                        <tr>
                            <th class="text-primary px-4 py-3 text-left font-semibold">{{ __('pages.title_label') }}</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">{{ __('pages.status') }}</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">{{ __('pages.updated') }}</th>
                            <th class="px-4 py-3"><span class="sr-only">{{ __('pages.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-default divide-y">
                        @foreach ($pages as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <a
                                        href="{{ route('blogs.pages.edit', [$blog->id, $item->id]) }}"
                                        class="text-primary hover:text-accent-primary font-medium"
                                    >
                                        {{ $item->editable()->title }}
                                    </a>
                                    <div class="text-secondary font-mono text-xs">
                                        {{ $item->slug }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-badge
                                        :variant="$item->status->value === 'PUBLISHED' ? 'primary' : 'default'"
                                        >{{ $item->status->value }}</x-badge
                                    >
                                    @if ($item->draft !== null && $item->publishedAt !== null)
                                        <x-badge variant="highlight">{{ __('pages.unpublished_edits') }}</x-badge>
                                    @endif
                                </td>
                                <td class="text-secondary px-4 py-3">
                                    {{
                                        \Carbon\Carbon::parse(
                                            $item->updatedAt,
                                        )->diffForHumans()
                                    }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a
                                        href="{{ route('blogs.pages.edit', [$blog->id, $item->id]) }}"
                                        class="text-accent-primary hover:underline"
                                        >Edit</a
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
