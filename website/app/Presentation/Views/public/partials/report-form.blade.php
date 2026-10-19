{{-- Expects $blog and optional $postSlug. Hidden for the blog owner and for guests. --}}
@auth
    @if (auth()->id() !== $blog->ownerUserId)
        <details class="text-sm opacity-80">
            <summary class="cursor-pointer">
                {{ __('public.report_content', ['content' => isset($postSlug) ? __('public.this_post') : __('public.this_blog')]) }}
            </summary>

            @if (session('report_status'))
                <p class="mt-2" role="status">{{ session('report_status') }}</p>
            @endif
            @error ('report')
                <p class="mt-2 text-red-600" role="alert">{{ $message }}</p>
            @enderror

            <form
                method="POST"
                action="{{ route('public.report', $blog->slug) }}"
                class="mt-2 max-w-md space-y-2"
            >
                @csrf
                @isset ($postSlug)
                    <input type="hidden" name="post" value="{{ $postSlug }}" />
                @endisset
                <label for="report-reason" class="sr-only">{{ __('public.report_reason') }}</label>
                <textarea
                    id="report-reason"
                    name="reason"
                    rows="3"
                    minlength="10"
                    maxlength="2000"
                    required
                    class="textarea min-h-0"
                    placeholder="{{ __('public.report_placeholder') }}"
                ></textarea>
                <button type="submit" class="btn-secondary text-sm">{{ __('public.send_report') }}</button>
            </form>
        </details>
    @endif
@endauth
