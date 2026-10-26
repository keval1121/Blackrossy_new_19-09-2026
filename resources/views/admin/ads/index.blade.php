@extends('layouts.admin')
@section('title', 'Ad placements')
@section('content')
<p class="mb-2 text-sm text-stone-500">Paste <strong>Display ad unit</strong> code here (must include <code>data-ad-slot</code>). Prefer Auto / responsive units. The publisher script loads from Settings when <strong>Show ads</strong> is ON — that same script powers <strong>Auto ads</strong> from the AdSense dashboard.</p>
<p class="mb-4 text-sm text-stone-500">Until Ready: keep placements inactive and Show ads OFF. After Ready: enable Show ads, turn on Auto ads in AdSense, then optionally activate these manual slots. Ads never load on cart, checkout, order, tracking or error pages. Every slot shows an “Advertisement” label with safe spacing from product buttons.</p>
<div class="space-y-4">
    @foreach($ads as $ad)
        <form method="post" action="{{ route('admin.ads.save', $ad) }}" class="rounded-2xl bg-white p-5">
            @csrf
            <p class="font-semibold">{{ $ad->name }} <span class="text-xs text-stone-400">{{ $ad->position }}</span></p>
            <textarea class="admin-input mt-2 min-h-24" name="code" placeholder="<ins class=&quot;adsbygoogle&quot; ... data-ad-slot=&quot;...&quot;></ins> + push script">{{ $ad->code }}</textarea>
            <input type="hidden" name="name" value="{{ $ad->name }}">
            <label class="mt-2 block text-sm"><input type="checkbox" name="is_active" @checked($ad->is_active)> Active</label>
            <button class="mt-3 rounded-xl bg-stone-900 px-4 py-2 text-white">Save</button>
        </form>
    @endforeach
</div>
@endsection
