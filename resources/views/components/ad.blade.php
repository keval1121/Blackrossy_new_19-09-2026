@props(['position'])
@php($code = ad_code($position))
@if($code)
    {{-- Only render when a real unit exists — avoids empty reserved height when ads are off. --}}
    <aside {{ $attributes->class(['ad-slot']) }} aria-label="Advertisement" data-ad-position="{{ $position }}">
        <p class="ad-slot-label">Advertisement</p>
        <div class="ad-slot-unit">{!! $code !!}</div>
    </aside>
@endif
