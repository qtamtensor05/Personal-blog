@extends ('layouts.app')

@section ('title', __('domains.title') . ' - ' . $blog->name)

@section ('content')
    <div class="container-custom section-spacing max-w-4xl">
        <div class="mb-8">
            <a
                href="{{ route('blogs.show', $blog->id) }}"
                class="text-secondary hover:text-accent-primary text-sm"
                >← {{ $blog->name }}</a
            >
            <h1 class="text-hero mt-2">{{ __('domains.title') }}</h1>
            <p class="text-secondary">{{ __('domains.intro') }}</p>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        <section class="card mb-8">
            <form
                method="POST"
                action="{{ route('blogs.domains.store', $blog->id) }}"
                class="flex flex-wrap items-end gap-3"
            >
                @csrf
                <div class="min-w-60 flex-1">
                    <x-input
                        name="hostname"
                        :label="__('ui.domain')"
                        placeholder="blog.example.com"
                        required
                        maxlength="253"
                    />
                </div>
                <button type="submit" class="btn-primary">{{ __('domains.add') }}</button>
            </form>
        </section>

        @if (count($domains) === 0)
            <x-empty-state :message="__('domains.no_domains')"
                >{{ __('domains.blog_available', ['url' => route('public.blog', $blog->slug)]) }}</x-empty-state
            >
        @else
            <div class="space-y-4">
                @foreach ($domains as ['domain' => $domain, 'dns' => $dns])
                    @php $status = $domain->status->value; @endphp
                    <section class="card space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-primary font-mono font-medium"
                                    >{{ $domain->hostname }}</span
                                >
                                <x-badge
                                    :variant="$status === 'ACTIVE' ? 'primary' : ($status === 'FAILED' ? 'highlight' : 'default')"
                                    >{{ __('ui.domain_status_'.$status) }}</x-badge
                                >
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if (in_array($status, ['PENDING', 'FAILED'], true))
                                    <form
                                        method="POST"
                                        action="{{ route('blogs.domains.verify', [$blog->id, $domain->id]) }}"
                                    >
                                        @csrf
                                        <button type="submit" class="btn-secondary text-sm">
                                            {{ __('ui.verify_dns') }}
                                        </button>
                                    </form>
                                @endif
                                @if (in_array($status, ['VERIFIED', 'DISABLED'], true))
                                    <form
                                        method="POST"
                                        action="{{ route('blogs.domains.activate', [$blog->id, $domain->id]) }}"
                                    >
                                        @csrf
                                        <button type="submit" class="btn-primary text-sm">
                                            {{ __('ui.activate') }}
                                        </button>
                                    </form>
                                @endif
                                @if ($status === 'ACTIVE')
                                    <a
                                        href="https://{{ $domain->hostname }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="btn-ghost text-sm"
                                        >{{ __('domains.open') }}</a
                                    >
                                    <form
                                        method="POST"
                                        action="{{ route('blogs.domains.disable', [$blog->id, $domain->id]) }}"
                                    >
                                        @csrf
                                        <button type="submit" class="btn-secondary text-sm">
                                            {{ __('ui.disable') }}
                                        </button>
                                    </form>
                                @endif
                                <form
                                    method="POST"
                                    action="{{ route('blogs.domains.destroy', [$blog->id, $domain->id]) }}"
                                    data-modal-confirm-form
                                    data-confirm-title="{{ __('ui.remove_domain') }}"
                                    data-confirm-message="{{ __('ui.remove_domain_confirm') }}"
                                >
                                    @csrf
                                    @method ('DELETE')
                                    <button
                                        type="submit"
                                        class="text-sm text-red-500 hover:underline"
                                    >
                                        {{ __('ui.remove') }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        @if (in_array($status, ['PENDING', 'FAILED'], true))
                            <div class="bg-surface-secondary space-y-1 rounded-md p-3 text-sm">
                                <p class="text-primary font-medium">{{ __('domains.txt_help') }}</p>
                                <p><span class="text-secondary">{{ __('domains.name') }}</span> <code class="font-mono break-all">{{ $dns['name'] }}</code></p>
                                <p><span class="text-secondary">{{ __('domains.value') }}</span> <code class="font-mono break-all">{{ $dns['value'] }}</code></p>
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </div>
@endsection
