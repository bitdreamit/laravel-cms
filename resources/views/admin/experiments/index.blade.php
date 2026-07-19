@extends('admin.layouts.app')
@section('page-title', 'Experiments')

@section('content')
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2>A/B Experiments</h2>
        <a href="/admin/experiments/create" class="btn">+ New Experiment</a>
    </div>
    <table>
        <thead>
            <tr><th>Name</th><th>Type</th><th>Status</th><th>Traffic</th><th>Assignments</th><th>Actions</th></tr>
        </thead>
        <tbody id="experiments-table">
            <tr><td colspan="6">Loading...</td></tr>
        </tbody>
    </table>
</div>

<script>
fetch('/admin/experiments', { headers: { 'Accept': 'application/json' }})
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById('experiments-table');
        tbody.innerHTML = '';
        (data.data || []).forEach(e => {
            const statusClass = e.status === 'running' ? 'success' : (e.status === 'completed' ? 'info' : 'warning');
            tbody.innerHTML += `<tr>
                <td>${e.name}</td>
                <td><code>${e.experiment_type}</code></td>
                <td><span class="badge badge-${statusClass}">${e.status}</span></td>
                <td>${e.traffic_allocation}%</td>
                <td>${e.assignments_count || 0}</td>
                <td><a href="/admin/experiments/${e.id}" class="btn btn-sm">View</a></td>
            </tr>`;
        });
    });
</script>
@endsection
