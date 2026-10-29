@extends('layouts.storefront')

@php
    $seoTitle = $page->seo_title ?: $page->title;
    $seoDescription = $page->seo_description;
@endphp

@push('head')
<style>
    .br-legal { border-top: 1px solid var(--color-line, #e7e0d6); }
    .br-legal-wrap { width: min(760px, calc(100% - 2rem)); margin: 0 auto; padding: 3.5rem 0 4.5rem; }
    .br-legal-kicker {
        margin: 0 0 .75rem;
        text-align: center;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .18em;
        text-transform: uppercase;
        color: #b0894f;
    }
    .br-legal-title {
        margin: 0;
        text-align: center;
        font-size: clamp(1.75rem, 3vw, 2.25rem);
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #1c1917;
    }
    .br-legal-back {
        display: block;
        margin: 1rem auto 0;
        text-align: center;
        color: #78716c;
        font-size: .92rem;
        text-decoration: none;
    }
    .br-legal-back:hover { color: #b0894f; }
    .br-legal-body {
        margin-top: 2.5rem;
        color: #57534e;
        font-size: 1rem;
        line-height: 1.85;
    }
    .br-legal-body p {
        margin: 0 0 1.35rem;
    }
    .br-legal-body p:last-child {
        margin-bottom: 0;
    }
</style>
@endpush

@section('content')
<article class="br-legal">
    <div class="br-legal-wrap">
        <p class="br-legal-kicker">Black Rossy</p>
        <h1 class="br-legal-title">{{ $page->title }}</h1>
        <a class="br-legal-back" href="{{ route('policy') }}">← Back to Our Policies</a>
        <div class="br-legal-body">
            @foreach (preg_split("/\R\s*\R/u", trim((string) $page->content)) as $block)
                @if (trim($block) !== '')
                    <p>{!! nl2br(e(trim($block))) !!}</p>
                @endif
            @endforeach
        </div>
    </div>
</article>
@endsection
