@extends('admin.layouts.app')
@section('page-title', 'Dashboard')

@section('content')
<div class="grid">
    <div class="stat-card">
        <div class="value">{{ $stats['entries'] ?? 0 }}</div>
        <div class="label">Total Entries</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ $stats['published_entries'] ?? 0 }}</div>
        <div class="label">Published</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ $stats['draft_entries'] ?? 0 }}</div>
        <div class="label">Drafts</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ $stats['collections'] ?? 0 }}</div>
        <div class="label">Collections</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ $stats['users'] ?? 0 }}</div>
        <div class="label">Users</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ $stats['forms'] ?? 0 }}</div>
        <div class="label">Forms</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ $stats['form_submissions'] ?? 0 }}</div>
        <div class="label">Form Submissions</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ $stats['assets'] ?? 0 }}</div>
        <div class="label">Assets</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ $stats['storage_mb'] ?? 0 }} MB</div>
        <div class="label">Storage Used</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ $stats['domains'] ?? 0 }}</div>
        <div class="label">Domains</div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2>Recent Entries</h2>
        <a href="/admin/entries" class="btn">View All</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($recentEntries ?? collect()) as $entry)
            <tr>
                <td>{{ $entry->title }}</td>
                <td><span class="badge badge-{{ $entry->status === 'published' ? 'success' : 'warning' }}">{{ $entry->status }}</span></td>
                <td>{{ $entry->updated_at?->diffForHumans() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(!empty($features_enabled))
<div class="card">
    <h2>V4 Features Enabled</h2>
    <div style="margin-top:0.5em;">
        @foreach($features_enabled as $feature)
            <span class="badge badge-info" style="margin-right:0.3em;">{{ $feature }}</span>
        @endforeach
    </div>
</div>
@endif
@endsection
