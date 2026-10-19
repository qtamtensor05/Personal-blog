<form method="POST" action="{{ route('interface-locale.update') }}" class="inline-flex items-center">
    @csrf
    <label class="sr-only" for="interface-locale-{{ $id ?? 'public' }}">{{ __('template.language') }}</label>
    <select id="interface-locale-{{ $id ?? 'public' }}" name="locale" class="input w-auto py-1 text-sm" onchange="this.form.submit()">
        <option value="vi" @selected(app()->getLocale() === 'vi')>VI</option>
        <option value="en" @selected(app()->getLocale() === 'en')>EN</option>
    </select>
</form>
