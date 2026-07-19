<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Page Title' }} — {{ config('app.name') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 900px; margin: 0 auto; padding: 2em; color: #1e293b; line-height: 1.6; }
        .entry-list { list-style: none; padding: 0; }
        .entry-list li { padding: 1em 0; border-bottom: 1px solid #e5e7eb; }
        .entry-list a { color: #2563eb; text-decoration: none; font-weight: 500; }
        .entry-list a:hover { text-decoration: underline; }
        .entry-content { margin-top: 2em; }
        .meta { color: #64748b; font-size: 0.9em; }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
