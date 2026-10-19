@extends('layouts.storefront')

@push('head')
<style>
    .br-about { border-top: 1px solid var(--color-line, #e7e0d6); }
    .br-about-wrap { width: min(760px, calc(100% - 2rem)); margin: 0 auto; padding: 3.5rem 0 4.5rem; }
    .br-about-title {
        margin: 0;
        text-align: center;
        font-family: var(--font-sans, Outfit, sans-serif);
        font-size: clamp(1.75rem, 3vw, 2.25rem);
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #1c1917;
    }
    .br-about-lead {
        margin: .85rem auto 0;
        max-width: 36rem;
        text-align: center;
        color: #78716c;
        font-size: .98rem;
        line-height: 1.7;
    }
    .br-about-block { margin-top: 3.25rem; }
    .br-about-heading {
        margin: 0 0 1rem;
        text-align: center;
        font-size: clamp(1.45rem, 2.2vw, 1.85rem);
        font-weight: 700;
        color: #1c1917;
    }
    .br-about-text {
        margin: 0 auto;
        max-width: 40rem;
        text-align: center;
        color: #57534e;
        font-size: 1rem;
        line-height: 1.8;
    }
    .br-about-text + .br-about-text { margin-top: 1rem; }
    .br-about-list {
        margin: 1.25rem auto 0;
        max-width: 40rem;
        padding: 0;
        list-style: none;
        text-align: left;
        color: #57534e;
        font-size: 1rem;
        line-height: 1.8;
    }
    .br-about-list li {
        position: relative;
        padding-left: 1.15rem;
        margin-top: .55rem;
    }
    .br-about-list li::before {
        content: "";
        position: absolute;
        left: 0;
        top: .7em;
        width: .4rem;
        height: .4rem;
        border-radius: 999px;
        background: #a8a29e;
    }
    .br-about-values {
        margin-top: 1.75rem;
        display: grid;
        gap: 1.25rem;
    }
    @media (min-width: 768px) {
        .br-about-values { grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
    }
    .br-about-value {
        padding: 1.35rem 1.2rem;
        border: 1px solid #e7e0d6;
        border-radius: 1.25rem;
        background: #fffdf9;
        text-align: center;
    }
    .br-about-value h3 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #1c1917;
    }
    .br-about-value p {
        margin: .65rem 0 0;
        color: #78716c;
        font-size: .95rem;
        line-height: 1.7;
    }
    .br-about-cta {
        margin-top: 3.5rem;
        padding: 2rem 1.5rem;
        border-radius: 1.5rem;
        background: #1c1917;
        color: #fff;
        text-align: center;
    }
    .br-about-cta h2 {
        margin: 0;
        font-family: var(--font-serif, "Cormorant Garamond", serif);
        font-size: clamp(1.8rem, 3vw, 2.4rem);
        font-weight: 500;
    }
    .br-about-cta p {
        margin: .7rem auto 0;
        max-width: 28rem;
        color: #d6d3d1;
        font-size: .95rem;
        line-height: 1.7;
    }
    .br-about-cta-actions {
        margin-top: 1.25rem;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: .75rem;
    }
</style>
@endpush

@section('content')
<div class="br-about">
    <div class="br-about-wrap">
        <h1 class="br-about-title">About Us</h1>
        <p class="br-about-lead">
            {{ store_name() }} is an India-based online boutique for {{ storefront_collection_names() }} — with guest checkout and Cash on Delivery on eligible pin codes.
        </p>

        <section class="br-about-block">
            <h2 class="br-about-heading">Our Story</h2>
            <p class="br-about-text">
                Black Rossy started with a simple idea: make everyday and festive shopping easier for customers who want clear photos, honest product details and a checkout that does not force an account. We focus on {{ storefront_collection_names() }} pieces that feel useful for real Indian occasions — office days, family gatherings and festivals.
            </p>
            <p class="br-about-text">
                Too many online listings show only a price and a stock image. Our product pages aim to explain who an item is for, when to wear it, and how to care for it, so you can decide with confidence from your phone before you place a Cash on Delivery order.
            </p>
        </section>

        <section class="br-about-block">
            <h2 class="br-about-heading">What we sell</h2>
            <p class="br-about-text">
                Live collections centre on clothing, footwear and bags under {{ storefront_collection_names() }}. Category pages include buying guidance — not only a product grid — so you can learn how to choose before you filter by price. Empty lines stay off the public menu until they have products you can actually buy.
            </p>
            <ul class="br-about-list">
                <li>Clothing for workdays, weekends and festive plans</li>
                <li>Footwear chosen for everyday comfort and casual outings</li>
                <li>Handbags and clutches for daily carry and occasion looks</li>
            </ul>
        </section>

        <section class="br-about-block">
            <h2 class="br-about-heading">Our Mission</h2>
            <p class="br-about-text">
                Our goal is to keep shopping straightforward. Browse the collection, add what you like, place a COD order, and receive it at your doorstep. We publish shipping, return and refund rules on this website so you know what to expect before you buy.
            </p>
        </section>

        <section class="br-about-block">
            <h2 class="br-about-heading">Our Values</h2>
            <div class="br-about-values">
                <div class="br-about-value">
                    <h3>Clarity</h3>
                    <p>Product pages aim for clear photos and practical descriptions so you can choose with confidence.</p>
                </div>
                <div class="br-about-value">
                    <h3>Convenience</h3>
                    <p>Guest checkout and Cash on Delivery on eligible pin codes — pay when your order arrives.</p>
                </div>
                <div class="br-about-value">
                    <h3>Care</h3>
                    <p>Support through Contact, email, phone and WhatsApp, with simple return guidance when something is not right.</p>
                </div>
            </div>
        </section>

        <section class="br-about-block">
            <h2 class="br-about-heading">How shopping works</h2>
            <p class="br-about-text">
                Choose from {{ storefront_collection_names(' or ') }}, add items to your cart, and checkout with your name, mobile number and delivery address. Most orders are packed in 1–2 working days and delivered in about 2–5 days depending on your pin code. Shipping is free on every order.
            </p>
            <p class="br-about-text">
                Eligible unused returns follow a 7-day window when tags and packaging are intact. Full rules live on our Shipping, Return and Refund policy pages. For help after checkout, share your order number on Contact or WhatsApp.
            </p>
        </section>

        <div class="br-about-cta">
            <h2>Shop the collection</h2>
            <p>Explore curated fashion and accessories with Cash on Delivery across eligible pin codes in India.</p>
            <div class="br-about-cta-actions">
                <a href="{{ route('shop') }}" class="btn btn-gold">Shop now</a>
                <a href="{{ route('contact') }}" class="btn btn-outline border-white text-white hover:bg-white hover:text-ink">Contact us</a>
            </div>
        </div>
    </div>
</div>
@endsection
