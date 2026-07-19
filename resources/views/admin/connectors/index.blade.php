@extends('admin.layouts.app')
@section('page-title', 'Connectors')

@section('content')
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2>Registered Connectors</h2>
        <a href="/admin/connectors/create" class="btn">+ Register Connector</a>
    </div>
    <table>
        <thead>
            <tr><th>Name</th><th>Base URL</th><th>Subscribed Events</th><th>Last Seen</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody id="connectors-table">
            <tr><td colspan="6">Loading...</td></tr>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Connector Integration</h3>
    <p style="margin:1em 0; color:#64748b;">
        To connect an existing Laravel app, install the connector package:
    </p>
    <pre style="background:#1e293b; color:#e2e8f0; padding:1em; border-radius:4px; overflow-x:auto;">composer require platform/laravel-cms-connector
php artisan cms-connector:install</pre>
</div>

<script>
fetch('/admin/connectors', { headers: { 'Accept': 'application/json' }})
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById('connectors-table');
        tbody.innerHTML = '';
        (data.data || data || []).forEach(c => {
            tbody.innerHTML += `<tr>
                <td>${c.name}</td>
                <td><a href="${c.base_url}" target="_blank">${c.base_url}</a></td>
                <td>${(c.subscribed_events || []).join(', ') || '-'}</td>
                <td>${c.last_seen_at ? new Date(c.last_seen_at).toLocaleString() : 'Never'}</td>
                <td>${c.is_active ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>'}</td>
                <td>
                    <a href="/admin/connectors/${c.id}" class="btn btn-sm">View</a>
                </td>
            </tr>`;
        });
    });
</script>
@endsection
