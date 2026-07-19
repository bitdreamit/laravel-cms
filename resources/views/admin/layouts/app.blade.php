<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} — {{ config('cms.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #f8fafc; color: #1e293b; display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #1e293b; color: #e2e8f0; padding: 1rem; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar h2 { color: #fff; font-size: 1.2em; padding: 0.5em 0; margin-bottom: 1em; border-bottom: 1px solid #334155; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar li { margin: 0.2em 0; }
        .sidebar a { color: #cbd5e1; text-decoration: none; padding: 0.5em 0.8em; display: block; border-radius: 4px; font-size: 0.9em; }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: #fff; }
        .sidebar .section { margin-top: 1.5em; font-size: 0.75em; text-transform: uppercase; color: #64748b; padding: 0 0.8em; }
        .main { flex: 1; margin-left: 250px; padding: 0; }
        .header { background: #fff; padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10; }
        .header h1 { font-size: 1.25em; }
        .header .user-info { display: flex; align-items: center; gap: 1em; }
        .content { padding: 1.5rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1rem; }
        .btn { display: inline-block; padding: 0.5em 1em; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.9em; border: none; cursor: pointer; }
        .btn:hover { background: #1d4ed8; }
        .btn-danger { background: #dc2626; }
        .btn-success { background: #16a34a; }
        .btn-sm { padding: 0.3em 0.6em; font-size: 0.8em; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; }
        th, td { padding: 0.75em 1em; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f8fafc; font-weight: 600; font-size: 0.85em; text-transform: uppercase; color: #64748b; }
        .badge { display: inline-block; padding: 0.2em 0.6em; border-radius: 4px; font-size: 0.75em; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-info { background: #dbeafe; color: #2563eb; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-card .value { font-size: 2em; font-weight: 700; color: #2563eb; }
        .stat-card .label { color: #64748b; font-size: 0.9em; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.3em; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.5em; border: 1px solid #d1d5db; border-radius: 4px; font-family: inherit; }
        .alert { padding: 0.75em 1em; border-radius: 4px; margin-bottom: 1rem; }
        .alert-success { background: #dcfce7; color: #16a34a; }
        .alert-error { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h2>{{ config('cms.name', 'CMS V4') }}</h2>
        <div class="section">Content</div>
        <ul>
            <li><a href="/admin/entries">Entries</a></li>
            <li><a href="/admin/collections">Collections</a></li>
            <li><a href="/admin/blueprints">Blueprints</a></li>
            <li><a href="/admin/taxonomies">Taxonomies</a></li>
            <li><a href="/admin/globals">Globals</a></li>
            <li><a href="/admin/navigations">Navigation</a></li>
            <li><a href="/admin/forms">Forms</a></li>
            <li><a href="/admin/assets">Assets</a></li>
        </ul>
        <div class="section">V4 Features</div>
        <ul>
            <li><a href="/admin/domains">Domains</a></li>
            <li><a href="/admin/themes">Themes</a></li>
            <li><a href="/admin/workflows">Workflows</a></li>
            <li><a href="/admin/experiments">Experiments</a></li>
            <li><a href="/admin/rag/playground">RAG Playground</a></li>
            <li><a href="/admin/segments">Segments</a></li>
            <li><a href="/admin/personalization-rules">Personalization</a></li>
            <li><a href="/admin/connectors">Connectors</a></li>
        </ul>
        <div class="section">User & Security</div>
        <ul>
            <li><a href="/admin/users">Users</a></li>
            <li><a href="/admin/roles">Roles</a></li>
            <li><a href="/admin/saml-idps">SAML IdPs</a></li>
            <li><a href="/admin/scim-tokens">SCIM Tokens</a></li>
            <li><a href="/admin/audit-streams">Audit Streams</a></li>
        </ul>
        <div class="section">Settings</div>
        <ul>
            <li><a href="/admin/billing">Billing</a></li>
            <li><a href="/admin/redirects">Redirects</a></li>
            <li><a href="/admin/imports">Import</a></li>
            <li><a href="/admin/utilities">Utilities</a></li>
            <li><a href="/admin/feature-flags">Feature Flags</a></li>
        </ul>
    </aside>
    <div class="main">
        <div class="header">
            <h1>@yield('page-title', $title ?? 'Dashboard')</h1>
            <div class="user-info">
                <span>{{ auth()->user()?->name }}</span>
                <span class="badge badge-info">{{ tenant()?->name }}</span>
                <form method="POST" action="/logout" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                </form>
            </div>
        </div>
        <div class="content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
            @yield('content')
        </div>
    </div>
</body>
</html>
