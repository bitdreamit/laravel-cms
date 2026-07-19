@extends('theme::layouts.app')

@section('title', $entry->title)

@section('meta_description', isset($entry->data['excerpt']) ? \Illuminate\Support\Str::limit($entry->data['excerpt'], 160) : '')

@section('content')
<div class="container">
    <article class="entry-detail">
        <h1>{{ $entry->title }}</h1>
        <div class="meta">
            Published: {{ $entry->published_at?->format('F d, Y') }}
        </div>

        @if(isset($entry->data['body']))
        <div class="content">
            {!! nl2br(e($entry->data['body'])) !!}
        </div>
        @endif

        <p style="margin-top: 2rem;"><a href="/">&larr; Back to Home</a></p>
    </article>
</div>
@endsection
