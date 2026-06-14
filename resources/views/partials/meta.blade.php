{{--
    Shared SEO / social head tags. One source of truth for meta description,
    canonical, robots, Open Graph, Twitter cards, and the brand vocabulary
    across every entry point (public listing, company pages, auth, dashboard).
    Callers override via @include with data:
      $metaTitle, $metaDescription, $metaUrl, $metaImage, $metaIndex, $metaType.
--}}
@php
    // Production is the only indexable host. Any other host (staging, preview,
    // IP, localhost) must emit noindex so it never competes with production for
    // rankings. We key off the request host rather than an env flag.
    $productionHost = 'internship-ratings.sb.sa';
    $isProductionHost = request()->getHost() === $productionHost;

    // Public, indexable routes. Everything else (auth, settings, dashboard)
    // defaults to noindex unless a caller explicitly opts in.
    $publicRoutes = ['home', 'companies.index', 'companies.show', 'ratings.create'];
    $isPublicRoute = request()->routeIs(...$publicRoutes);

    $siteName = 'تقييم التدريب';
    $metaTitle ??= $siteName;
    $fullTitle = $metaTitle === $siteName ? $siteName : $metaTitle.' - '.$siteName;
    $metaDescription ??= 'منصّة عربية لتقييم جهات التدريب التعاوني والتدريب الصيفي. اقرأ تجارب المتدربين الحقيقية وتقييماتهم لمختلف الشركات والجهات، وشارك تجربتك لمساعدة غيرك على اختيار جهة التدريب المناسبة.';
    $metaUrl ??= url()->current();
    $metaImage ??= url('/og-image.png');
    $metaType ??= 'website';

    // Default to indexable only for public routes on the production host.
    $metaIndex ??= $isPublicRoute;
    $metaIndex = $metaIndex && $isProductionHost;

    // On non-production hosts point the canonical at production so any leaked
    // crawl consolidates onto the real URL (noindex remains the decisive signal).
    $metaCanonical = $isProductionHost
        ? $metaUrl
        : 'https://'.$productionHost.request()->getRequestUri();
@endphp
<meta name="description" content="{{ $metaDescription }}">
<link rel="canonical" href="{{ $metaCanonical }}">
@if ($metaIndex)
    <meta name="robots" content="index, follow, max-image-preview:large">
@else
    <meta name="robots" content="noindex, nofollow">
@endif

<meta name="theme-color" content="#3b82f6">
<meta name="application-name" content="{{ $siteName }}">
<meta name="apple-mobile-web-app-title" content="{{ $siteName }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">

<meta property="og:type" content="{{ $metaType }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $metaUrl }}">
<meta property="og:image" content="{{ $metaImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $siteName }}">
<meta property="og:locale" content="ar_SA">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $metaImage }}">
