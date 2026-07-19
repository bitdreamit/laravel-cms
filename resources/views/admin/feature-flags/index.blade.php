@extends('admin.layouts.app')
@section('page-title', 'Feature Flags')

@section('content')
<div class="card">
    <h2>V4 Feature Flags</h2>
    <p style="margin:1em 0; color:#64748b;">Enable or disable V4 features for this tenant. New tenants default to all features OFF.</p>
    <form method="POST" action="/admin/feature-flags">
        @csrf
        <table>
            <thead>
                <tr><th>Feature</th><th>Description</th><th>Status</th></tr>
            </thead>
            <tbody id="features-table">
                <tr><td colspan="3">Loading...</td></tr>
            </tbody>
        </table>
        <div style="margin-top:1rem;">
            <button type="submit" class="btn">Save Feature Flags</button>
        </div>
    </form>
</div>

<script>
fetch('/admin/feature-flags', { headers: { 'Accept': 'application/json' }})
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById('features-table');
        tbody.innerHTML = '';
        data.forEach(feature => {
            tbody.innerHTML += `<tr>
                <td><strong>${feature.label}</strong><br><code>${feature.key}</code></td>
                <td>Toggle this V4 feature on/off for the current tenant</td>
                <td>
                    <label>
                        <input type="checkbox" name="features[]" value="${feature.key}" ${feature.enabled ? 'checked' : ''}>
                        ${feature.enabled ? 'Enabled' : 'Disabled'}
                    </label>
                </td>
            </tr>`;
        });
    });
</script>
@endsection
