@extends('layouts.public')

@section('content')
    <article class="entry-content">
        <h1>{{ $entry->title }}</h1>
        <p class="meta">Published: {{ $entry->published_at?->format('M d, Y') }}</p>

        <div class="content">
            @isset($entry->data['body'])
                {!! nl2br(e($entry->data['body'])) !!}
            @endisset
        </div>

        <p class="meta"><a href="/">&larr; Back</a></p>
    </article>
@endsection
