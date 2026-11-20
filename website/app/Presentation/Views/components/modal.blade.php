@props (['id' => 'appModal', 'title' => null])

<div
    id="{{ $id }}"
    class="fixed inset-0 z-100 hidden items-center justify-center bg-black/50 px-4"
    data-modal
    data-close-label="{{ __('Close') }}"
    data-confirm-label="{{ __('Confirm') }}"
    data-error-title="{{ __('Something went wrong') }}"
    data-confirm-title="{{ __('Confirm action') }}"
    data-confirm-message="{{ __('Are you sure you want to continue?') }}"
    aria-hidden="true"
>
    <div
        class="border-default bg-surface w-full max-w-md rounded-lg border p-6 shadow-2xl"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
        tabindex="-1"
    >
        <div class="flex items-start justify-between gap-4">
            <h2 id="{{ $id }}-title" class="text-primary text-lg font-semibold">{{ $title ?? __('Notice') }}</h2>
            <button
                type="button"
                class="btn-ghost -mt-2 -mr-2 p-2"
                data-modal-close
                aria-label="{{ __('Close') }}"
            >
                &times;
            </button>
        </div>
        <p class="text-secondary mt-3 text-sm" data-modal-message></p>
        <input type="text" class="input mt-4 hidden" data-modal-input />
        <div class="mt-6 flex justify-end">
            <button type="button" class="btn-ghost hidden px-4 py-2" data-modal-cancel>
                {{ __('Cancel') }}
            </button>
            <button type="button" class="btn-primary px-4 py-2" data-modal-confirm>{{ __('Close') }}</button>
        </div>
    </div>
</div>

<script type="application/json" data-editor-messages>
    {!! Illuminate\Support\Js::encode([
        'Post content' => __('Post content'),
        'Editor ready.' => __('Editor ready.'),
        'Previewing post content.' => __('Previewing post content.'),
        'Editing post content.' => __('Editing post content.'),
        'Could not save the draft.' => __('Could not save the draft.'),
        'Unsaved changes' => __('Unsaved changes'),
        'Draft saved' => __('Draft saved'),
        'Offline. Changes are not saved yet.' => __('Offline. Changes are not saved yet.'),
        'Add link' => __('Add link'),
        'Link URL (https://…)' => __('Link URL (https://…)'),
        'Use a link starting with https://, http:// or mailto:.' => __('Use a link starting with https://, http:// or mailto:.'),
        'Image description' => __('Image description'),
        'Describe the image (alt text)' => __('Describe the image (alt text)'),
        'Uploading cover image…' => __('Uploading cover image…'),
        'Unable to upload the image.' => __('Unable to upload the image.'),
        'Image uploaded. Select “Save details” to apply.' => __('Image uploaded. Select “Save details” to apply.'),
        'The editor could not load. Reload the page; if the problem persists, contact an administrator.' => __('The editor could not load. Reload the page; if the problem persists, contact an administrator.'),
        'Template content editor' => __('Template content editor'),
        'Uploading image to Cloudinary…' => __('Uploading image to Cloudinary…'),
        'Image uploaded.' => __('Image uploaded.'),
        'Saving draft…' => __('Saving draft…'),
        'Add at least one section before saving the template.' => __('Add at least one section before saving the template.'),
        'The template could not be saved. Check the form and try again.' => __('The template could not be saved. Check the form and try again.'),
        'Image uploaded to Cloudinary.' => __('Image uploaded to Cloudinary.'),
        'Choose an image to upload to Cloudinary.' => __('Choose an image to upload to Cloudinary.'),
        'Image upload failed.' => __('Image upload failed.'),
        'The template could not be saved.' => __('The template could not be saved.'),
    ]) !!}
</script>
