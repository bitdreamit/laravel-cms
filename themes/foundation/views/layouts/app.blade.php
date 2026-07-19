<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('cms.name'))</title>
    <meta name="description" content="@yield('meta_description', '')">
    <link rel="icon" type="image/x-icon" href="@yield('favicon', '/favicon.ico')">
    <style>
        :root {
            --brand-color: #2563eb;
            --brand-color-hover: #1d4ed8;
            --secondary-color: #64748b;
            --text-color: #1e293b;
            --bg-color: #ffffff;
            --border-radius: 8px;
            --max-width: 1200px;
            --font-heading: 'Inter', system-ui, sans-serif;
            --font-body: 'Inter', system-ui, sans-serif;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font-body); color: var(--text-color); line-height: 1.6; background: var(--bg-color); }
        .container { max-width: var(--max-width); margin: 0 auto; padding: 0 1rem; }
        header { border-bottom: 1px solid #e5e7eb; padding: 1rem 0; }
        header .container { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-family: var(--font-heading); font-weight: 700; font-size: 1.5em; color: var(--brand-color); text-decoration: none; }
        nav ul { display: flex; gap: 1.5rem; list-style: none; }
        nav a { color: var(--text-color); text-decoration: none; font-weight: 500; }
        nav a:hover { color: var(--brand-color); }
        main { min-height: 60vh; padding: 2rem 0; }
        .hero { background: linear-gradient(135deg, var(--brand-color), var(--brand-color-hover)); color: white; padding: 4rem 0; text-align: center; margin-bottom: 2rem; }
        .hero h1 { font-size: 2.5em; margin-bottom: 1rem; }
        .hero p { font-size: 1.2em; opacity: 0.9; max-width: 600px; margin: 0 auto 2rem; }
        .btn { display: inline-block; padding: 0.75em 1.5em; background: white; color: var(--brand-color); text-decoration: none; border-radius: var(--border-radius); font-weight: 600; }
        .btn:hover { background: #f8fafc; }
        .entries-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin: 2rem 0; }
        .entry-card { border: 1px solid #e5e7eb; border-radius: var(--border-radius); padding: 1.5rem; background: white; transition: box-shadow 0.2s; }
        .entry-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .entry-card h3 { margin-bottom: 0.5rem; }
        .entry-card h3 a { color: var(--text-color); text-decoration: none; }
        .entry-card h3 a:hover { color: var(--brand-color); }
        .entry-card .meta { color: var(--secondary-color); font-size: 0.9em; margin-bottom: 0.5rem; }
        .entry-card p { color: #475569; }
        .entry-detail { background: white; padding: 2rem; border-radius: var(--border-radius); }
        .entry-detail h1 { margin-bottom: 1rem; }
        .entry-detail .meta { color: var(--secondary-color); margin-bottom: 2rem; }
        footer { background: #1e293b; color: #cbd5e1; padding: 3rem 0 1rem; margin-top: 4rem; }
        footer .container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem; }
        footer h4 { color: white; margin-bottom: 1rem; }
        footer ul { list-style: none; }
        footer a { color: #cbd5e1; text-decoration: none; }
        footer a:hover { color: white; }
        footer .copyright { grid-column: 1/-1; text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #334155; }
        .pagination { text-align: center; margin: 2rem 0; }
        .pagination a, .pagination span { display: inline-block; padding: 0.5em 1em; border: 1px solid #e5e7eb; margin: 0 0.2em; border-radius: 4px; text-decoration: none; color: var(--text-color); }
        .pagination .current { background: var(--brand-color); color: white; border-color: var(--brand-color); }
        @media (max-width: 768px) {
            footer .container { grid-template-columns: 1fr; }
            .entries-grid { grid-template-columns: 1fr; }
        }
    </style>
    @yield('head_extra')
</head>
<body>
    @include('theme::layouts.partials.header')
    <main>
        @yield('content')
    </main>
    @include('theme::layouts.partials.footer')
    @yield('body_extra')
</body>
</html>
