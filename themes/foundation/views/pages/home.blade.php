@extends('theme::layouts.app')

@section('title', 'Welcome to ' . (tenant()?->name ?? config('cms.name')))

@section('content')
<section class="hero">
    <div class="container">
        <h1>Welcome to {{ tenant()?->name ?? config('cms.name') }}</h1>
        <p>A modern, multi-tenant content management system built with Laravel 12, V4 features including multi-domain connectivity, external Laravel connector, workflow engine, A/B testing, AI RAG, and more.</p>
        <a href="/blog" class="btn">Read Our Blog</a>
    </div>
</section>

<div class="container">
    <h2>Latest Content</h2>
    <div class="entries-grid">
        @foreach($entries ?? [] as $entry)
        <article class="entry-card">
            <h3><a href="/{{ $entry->slug }}">{{ $entry->title }}</a></h3>
            <div class="meta">{{ $entry->published_at?->format('M d, Y') }}</div>
            <p>{{ isset($entry->data['body']) ? \Illuminate\Support\Str::limit(strip_tags($entry->data['body']), 120) : '' }}</p>
        </article>
        @endforeach
    </div>
</div>
@endsection
