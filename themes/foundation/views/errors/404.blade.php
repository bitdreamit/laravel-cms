@extends('theme::layouts.app')

@section('title', '404 - Not Found')

@section('content')
<div class="container" style="text-align:center; padding: 4rem 0;">
    <h1 style="font-size: 6em; color: var(--brand-color);">404</h1>
    <h2>Page Not Found</h2>
    <p style="margin: 1rem 0;">The page you're looking for doesn't exist or has been moved.</p>
    <a href="/" class="btn">Back to Home</a>
</div>
@endsection
