@extends('layouts.public')

@section('content')
    <h1>{{ config('app.name') }}</h1>
    <p>Welcome to the home page.</p>

    @if($domain = (app()->bound('current.domain') ? app('current.domain') : null))
        <p class="meta">Current domain: {{ $domain->domain }}</p>
        @if($domain->default_collection_handle)
            <p>Showing collection: <strong>{{ $domain->default_collection_handle }}</strong></p>
        @endif
    @endif

    @if((app()->bound('current.theme') ? app('current.theme') : null))
        <p class="meta">Theme: {{ (app()->bound('current.theme') ? app('current.theme') : null)->name }}</p>
    @endif

    @if((app()->bound('current.site') ? app('current.site') : null))
        <p class="meta">Site/Locale: {{ (app()->bound('current.site') ? app('current.site') : null)->locale }}</p>
    @endif
@endsection
