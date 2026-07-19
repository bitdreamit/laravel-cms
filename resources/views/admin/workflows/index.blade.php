@extends('admin.layouts.app')
@section('page-title', 'Workflows')

@section('content')
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2>Workflows</h2>
        <a href="/admin/workflows/create" class="btn">+ New Workflow</a>
    </div>
    <table>
        <thead>
            <tr><th>Name</th><th>Trigger</th><th>Active</th><th>Running Instances</th><th>Actions</th></tr>
        </thead>
        <tbody id="workflows-table">
            <tr><td colspan="5">Loading...</td></tr>
        </tbody>
    </table>
</div>

<script>
fetch('/admin/workflows', { headers: { 'Accept': 'application/json' }})
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById('workflows-table');
        tbody.innerHTML = '';
        (data.data || []).forEach(w => {
            tbody.innerHTML += `<tr>
                <td>${w.name}</td>
                <td><code>${w.trigger_event}</code></td>
                <td>${w.is_active ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-warning">Inactive</span>'}</td>
                <td>${w.instances_count || 0}</td>
                <td>
                    <a href="/admin/workflows/${w.id}" class="btn btn-sm">Edit</a>
                </td>
            </tr>`;
        });
    });
</script>
@endsection
