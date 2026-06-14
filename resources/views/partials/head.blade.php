<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $appName = config('app.name') === 'Laravel' ? 'تقييم التدريب' : config('app.name');
@endphp
<title>
    {{ filled($title ?? null) ? $title.' - '.$appName : $appName }}
</title>

@include('partials.meta', array_filter([
    'metaTitle' => $title ?? null,
    'metaDescription' => $metaDescription ?? null,
    'metaType' => $metaType ?? null,
]))

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:400,500,600,700" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
