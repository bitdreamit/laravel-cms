@extends('theme::layouts.app')

@section('title', $collection->name ?? 'Content')

@section('content')
<div class="container">
    <h1>{{ $collection->name ?? 'Content' }}</h1>
    @if(isset($collection->description) && $collection->description)
    <p>{{ $collection->description }}</p>
    @endif

    <div class="entries-grid">
        @foreach($entries as $entry)
        <article class="entry-card">
            <h3><a href="/{{ $entry->slug }}">{{ $entry->title }}</a></h3>
            <div class="meta">{{ $entry->published_at?->format('M d, Y') }}</div>
            <p>{{ isset($entry->data['body']) ? \Illuminate\Support\Str::limit(strip_tags($entry->data['body']), 120) : '' }}</p>
        </article>
        @endforeach
    </div>

    @if(method_exists($entries, 'links'))
    <div class="pagination">
        {{ $entries->links() }}
    </div>
    @endif
</div>
@endsection
