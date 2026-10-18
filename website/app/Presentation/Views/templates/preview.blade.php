@php
    $tokens = $data->designTokens;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $template->name }} — Preview</title>
    @vite(['app/Presentation/Css/app.css'])
</head>
<body class="antialiased" style="--d-primary: {{ $tokens->primaryColor }}; --d-secondary: {{ $tokens->secondaryColor }}; --d-accent: {{ $tokens->accentColor }}; --d-text: {{ $tokens->textColor }}; --d-bg: {{ $tokens->backgroundColor }}; --d-heading-font: '{{ $tokens->headingFont }}'; --d-body-font: '{{ $tokens->bodyFont }}'; background: var(--d-bg); color: var(--d-text); font-family: var(--d-body-font), sans-serif;">
    <div class="border-b border-default px-4 py-3 text-center text-sm" style="background: var(--d-accent); color: var(--d-text);">
        Template preview: {{ $template->name }}
    </div>
    <main class="container-custom max-w-4xl py-10 space-y-10">
        @foreach($data->sections as $section)
            @includeIf('blogs.design-sections.'.$section->section, ['blog' => $blog, 'section' => $section, 'mediaUrls' => $mediaUrls, 'documentHtml' => $documentHtml])
        @endforeach
    </main>
</body>
</html>
