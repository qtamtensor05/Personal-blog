<section class="border-default bg-surface mt-4 grid gap-3 rounded-xl border p-4" data-image-writing aria-labelledby="image-writing-title">
    <h3 id="image-writing-title" class="text-primary font-semibold">{{ __('ai.title') }}</h3>
    <p class="text-secondary text-sm">{{ __('ai.help') }}</p>
    @unless ($aiAvailable)
        <p class="text-secondary text-sm" role="status">{{ __('ai.unconfigured') }}</p>
    @endunless
    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label for="ai-image" class="text-secondary mb-1 block text-sm">{{ __('ai.image') }}</label>
            <input id="ai-image" type="file" accept="image/jpeg,image/png,image/webp" class="input w-full" data-ai-file aria-describedby="ai-image-help" />
            <p id="ai-image-help" class="text-secondary mt-1 text-xs">{{ __('ai.file_help') }}</p>
        </div>
        <div>
            <label for="ai-language" class="text-secondary mb-1 block text-sm">{{ __('ai.language') }}</label>
            <select id="ai-language" class="input w-full" data-ai-language>
                <option value="vi" @selected(app()->getLocale() === 'vi')>Tiếng Việt</option>
                <option value="en" @selected(app()->getLocale() === 'en')>English</option>
            </select>
        </div>
    </div>
    <img data-ai-preview hidden class="max-h-40 max-w-full rounded-lg object-contain" alt="{{ __('ai.selected_image') }}" />
    <div>
        <label for="ai-alt" class="text-secondary mb-1 block text-sm">{{ __('ai.alt') }}</label>
        <input id="ai-alt" data-ai-alt class="input w-full" maxlength="500" />
    </div>
    <div>
        <label for="ai-instructions" class="text-secondary mb-1 block text-sm">{{ __('ai.instructions') }}</label>
        <textarea id="ai-instructions" data-ai-instructions class="input w-full" rows="2" maxlength="1000" placeholder="{{ __('ai.instructions_placeholder') }}"></textarea>
    </div>
    <fieldset class="border-default grid gap-2 rounded-lg border p-3 sm:grid-cols-2">
        <legend class="text-primary px-1 text-sm font-medium">{{ __('ai.fields') }}</legend>
        @foreach (['title' => __('post.title'), 'content' => __('post.content'), 'slug' => __('post.slug'), 'excerpt' => __('ai.excerpt'), 'seo_title' => __('post.seo_title'), 'seo_description' => __('post.seo_description'), 'image_alt' => __('ai.alt')] as $field => $label)
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" value="{{ $field }}" data-ai-field data-label="{{ $label }}" />
                {{ $label }}
            </label>
        @endforeach
        <p class="text-secondary text-xs sm:col-span-2">{{ __('ai.fields_help') }}</p>
    </fieldset>
    <div>
        <button type="button" class="btn-secondary" data-ai-generate disabled>{{ __('ai.generate') }}</button>
    </div>
    <p data-ai-status class="text-secondary text-sm" role="status" aria-live="polite"></p>
</section>
