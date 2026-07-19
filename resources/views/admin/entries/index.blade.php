@extends('admin.layouts.app')
@section('page-title', 'Entries')

@section('content')
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2>Entries</h2>
        <a href="/admin/entries/create" class="btn">+ New Entry</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Collection</th>
                <th>Status</th>
                <th>Published At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="entries-table">
            <tr><td colspan="5">Loading...</td></tr>
        </tbody>
    </table>
</div>

<script>
fetch('/admin/entries', { headers: { 'Accept': 'application/json' }})
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById('entries-table');
        tbody.innerHTML = '';
        (data.data || []).forEach(entry => {
            const statusClass = entry.status === 'published' ? 'success' : 'warning';
            tbody.innerHTML += `<tr>
                <td><a href="/admin/entries/${entry.id}">${entry.title}</a></td>
                <td>${entry.collection?.name || '-'}</td>
                <td><span class="badge badge-${statusClass}">${entry.status}</span></td>
                <td>${entry.published_at ? new Date(entry.published_at).toLocaleDateString() : '-'}</td>
                <td>
                    <a href="/admin/entries/${entry.id}/edit" class="btn btn-sm">Edit</a>
                    <form method="POST" action="/admin/entries/${entry.id}" style="display:inline" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="_token" name="_token" value="{{ csrf_token() }}">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>`;
        });
    });
</script>
@endsection
