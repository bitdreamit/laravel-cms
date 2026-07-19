@extends('layouts.public')

@section('content')
    <h1>{{ config('app.name') }}</h1>
    <p>Welcome to the home page.</p>

    @if($domain = app('current.domain'))
        <p class="meta">Current domain: {{ $domain->domain }}</p>
        @if($domain->default_collection_handle)
            <p>Showing collection: <strong>{{ $domain->default_collection_handle }}</strong></p>
        @endif
    @endif

    @if(app('current.theme'))
        <p class="meta">Theme: {{ app('current.theme')->name }}</p>
    @endif

    @if(app('current.site'))
        <p class="meta">Site/Locale: {{ app('current.site')->locale }}</p>
    @endif
@endsection
