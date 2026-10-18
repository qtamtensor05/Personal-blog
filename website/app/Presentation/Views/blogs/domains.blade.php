@extends('layouts.app')

@section('title', 'Domains — '.$blog->name)

@section('content')
<div class="container-custom section-spacing max-w-4xl">
    <div class="mb-8">
        <a href="{{ route('blogs.show', $blog->id) }}" class="text-sm text-secondary hover:text-accent-primary">← {{ $blog->name }}</a>
        <h1 class="text-hero mt-2">Custom Domains</h1>
        <p class="text-secondary">
            Serve your blog on your own domain. Prove ownership with a DNS TXT record, then point the
            domain to this platform (CNAME or A record) and activate it.
        </p>
    </div>

    @if(session('status'))
        <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if($errors->any())
        <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    <section class="card mb-8">
        <form method="POST" action="{{ route('blogs.domains.store', $blog->id) }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-60">
                <x-input name="hostname" label="Domain" placeholder="blog.example.com" required maxlength="253" />
            </div>
            <button type="submit" class="btn-primary">Add domain</button>
        </form>
    </section>

    @if(count($domains) === 0)
        <x-empty-state message="No custom domains">Your blog is available at {{ route('public.blog', $blog->slug) }}.</x-empty-state>
    @else
        <div class="space-y-4">
            @foreach($domains as ['domain' => $domain, 'dns' => $dns])
                @php $status = $domain->status->value; @endphp
                <section class="card space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-primary font-mono">{{ $domain->hostname }}</span>
                            <x-badge :variant="$status === 'ACTIVE' ? 'primary' : ($status === 'FAILED' ? 'highlight' : 'default')">{{ $status }}</x-badge>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if(in_array($status, ['PENDING', 'FAILED'], true))
                                <form method="POST" action="{{ route('blogs.domains.verify', [$blog->id, $domain->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary text-sm">Verify DNS</button>
                                </form>
                            @endif
                            @if(in_array($status, ['VERIFIED', 'DISABLED'], true))
                                <form method="POST" action="{{ route('blogs.domains.activate', [$blog->id, $domain->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn-primary text-sm">Activate</button>
                                </form>
                            @endif
                            @if($status === 'ACTIVE')
                                <a href="https://{{ $domain->hostname }}" target="_blank" rel="noopener" class="btn-ghost text-sm">Open</a>
                                <form method="POST" action="{{ route('blogs.domains.disable', [$blog->id, $domain->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary text-sm">Disable</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('blogs.domains.destroy', [$blog->id, $domain->id]) }}"
                                  data-modal-confirm-form data-confirm-title="Remove domain" data-confirm-message="Remove this domain?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-500 hover:underline">Remove</button>
                            </form>
                        </div>
                    </div>

                    @if(in_array($status, ['PENDING', 'FAILED'], true))
                        <div class="rounded-md bg-surface-secondary p-3 text-sm space-y-1">
                            <p class="text-primary font-medium">Add this TXT record at your DNS provider:</p>
                            <p><span class="text-secondary">Name:</span> <code class="font-mono break-all">{{ $dns['name'] }}</code></p>
                            <p><span class="text-secondary">Value:</span> <code class="font-mono break-all">{{ $dns['value'] }}</code></p>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
</div>
@endsection
