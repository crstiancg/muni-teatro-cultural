<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }} · {{ config('app.name') }}</title>
    <meta name="description" content="{{ $descripcion }}">
    <link rel="canonical" href="{{ $urlPerfil }}">

    {{-- og:url apunta a este mismo link: si apuntara a la SPA, el bot la volvería a leer sin etiquetas --}}
    <meta property="og:type" content="profile">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="es_PE">
    <meta property="og:title" content="{{ $titulo }}">
    <meta property="og:description" content="{{ $descripcion }}">
    <meta property="og:url" content="{{ $urlCompartir }}">
    @if ($imagen)
        <meta property="og:image" content="{{ $imagen }}">
        <meta property="og:image:alt" content="{{ $titulo }}">
    @endif

    <meta name="twitter:card" content="{{ $imagen ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $titulo }}">
    <meta name="twitter:description" content="{{ $descripcion }}">
    @if ($imagen)
        <meta name="twitter:image" content="{{ $imagen }}">
    @endif

    {{-- por si una persona llega acá con un user-agent raro --}}
    <meta http-equiv="refresh" content="0; url={{ $urlPerfil }}">
</head>
<body>
    <p><a href="{{ $urlPerfil }}">Ver el perfil de {{ $titulo }}</a></p>
</body>
</html>
