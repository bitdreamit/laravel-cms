@extends('admin.layouts.app')
@section('page-title', 'Domains')

@section('content')
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2>Domains</h2>
        <a href="/admin/domains/create" class="btn">+ Add Domain</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Domain</th>
                <th>Primary</th>
                <th>SSL</th>
                <th>DNS</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="domains-table">
            <tr><td colspan="6">Loading...</td></tr>
        </tbody>
    </table>
</div>

<script>
fetch('/admin/domains', { headers: { 'Accept': 'application/json' }})
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById('domains-table');
        tbody.innerHTML = '';
        (data.data || []).forEach(d => {
            const sslClass = d.ssl_status === 'active' ? 'success' : (d.ssl_status === 'pending' ? 'warning' : 'danger');
            const dnsClass = d.dns_verification_status === 'verified' ? 'success' : 'warning';
            tbody.innerHTML += `<tr>
                <td>${d.domain}${d.is_wildcard ? ' <span class="badge badge-info">WILDCARD</span>' : ''}</td>
                <td>${d.is_primary ? '<span class="badge badge-success">PRIMARY</span>' : ''}</td>
                <td><span class="badge badge-${sslClass}">${d.ssl_status}</span></td>
                <td><span class="badge badge-${dnsClass}">${d.dns_verification_status}</span></td>
                <td><span class="badge badge-info">${d.status}</span></td>
                <td>
                    <a href="/admin/domains/${d.id}" class="btn btn-sm">Manage</a>
                </td>
            </tr>`;
        });
    });
</script>
@endsection
