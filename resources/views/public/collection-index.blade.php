@extends('layouts.public')

@section('content')
    <h1>{{ $collection->name }}</h1>
    <p class="meta">{{ $collection->description }}</p>

    <ul class="entry-list">
        @foreach($entries as $entry)
            <li>
                <a href="/{{ $entry->slug }}">{{ $entry->title }}</a>
                <div class="meta">{{ $entry->published_at?->format('M d, Y') }}</div>
            </li>
        @endforeach
    </ul>

    {{ $entries->links() }}
@endsection
