<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel CMS V4') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 2em auto; padding: 0 1em; color: #1e293b; }
        h1 { color: #2563eb; }
        .feature-list { list-style: none; padding: 0; }
        .feature-list li { padding: 0.5em 0; border-bottom: 1px solid #e5e7eb; }
        .tenant-card { background: #f8fafc; padding: 1em; margin: 1em 0; border-radius: 8px; border-left: 4px solid #2563eb; }
        .badge { background: #2563eb; color: white; padding: 0.2em 0.6em; border-radius: 4px; font-size: 0.8em; }
    </style>
</head>
<body>
    <h1>{{ config('app.name', 'Laravel CMS V4') }}</h1>
    <p>Welcome to the V4 multi-tenant CMS platform. This is the central domain.</p>

    <h2>V4 Features</h2>
    <ul class="feature-list">
        <li><span class="badge">V4.17</span> Multi-Domain & Subdomain Connectivity Layer</li>
        <li><span class="badge">V4.18</span> External Laravel Connector (5 modes)</li>
        <li><span class="badge">V4.19.1</span> Workflow Engine (DAG with 7 node types)</li>
        <li><span class="badge">V4.19.2</span> A/B Testing with statistical significance</li>
        <li><span class="badge">V4.19.3</span> Real-time Collaborative Editing (Yjs)</li>
        <li><span class="badge">V4.19.4</span> AI RAG with per-tenant vector store</li>
        <li><span class="badge">V4.19.5</span> Personalization & Segments (19 conditions)</li>
        <li><span class="badge">V4.19.6</span> SAML 2.0 SSO</li>
        <li><span class="badge">V4.19.7</span> SCIM 2.0 User Provisioning</li>
        <li><span class="badge">V4.19.8</span> Audit Log Streaming (6 destinations)</li>
        <li><span class="badge">V4.19.9</span> Form Analytics & Lead Scoring</li>
    </ul>

    <h2>Test Tenants</h2>
    <div class="tenant-card">
        <strong>AdvMedi</strong> (V3 + V4) — <a href="http://advmedi.test">advmedi.test</a>,
        <a href="http://shop.advmedi.test">shop.advmedi.test</a>,
        <a href="http://blog.advmedi.test">blog.advmedi.test</a>
    </div>
    <div class="tenant-card">
        <strong>BitDreamIT</strong> (V3) — <a href="http://bitdreamit.test">bitdreamit.test</a>
    </div>
    <div class="tenant-card">
        <strong>Shopland</strong> (V4 Connector Demo) — <a href="http://shopland.test">shopland.test</a>
    </div>
    <div class="tenant-card">
        <strong>EnterpriseCorp</strong> (V4 SAML + SCIM + Audit) — <a href="http://enterprise.test">enterprise.test</a>
    </div>
    <div class="tenant-card">
        <strong>Multilingual Co.</strong> (V4 Wildcard + Per-Domain Locale) —
        <a href="http://multilingual.fr">multilingual.fr</a>,
        <a href="http://multilingual.de">multilingual.de</a>,
        <a href="http://multilingual.bn">multilingual.bn</a>,
        <a href="http://paris.multilingual.test">paris.multilingual.test</a> (wildcard)
    </div>

    <p style="margin-top: 3em; color: #64748b; font-size: 0.9em;">
        Login: <code>admin@platform.test</code> / <code>password</code><br>
        Or: <code>admin@{tenant-slug}.test</code> / <code>password</code>
    </p>
</body>
</html>
