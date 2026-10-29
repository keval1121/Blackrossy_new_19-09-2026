@extends('layouts.storefront')

@push('head')
<style>
    .br-policy { border-top: 1px solid var(--color-line, #e7e0d6); }
    .br-policy-wrap { width: min(760px, calc(100% - 2rem)); margin: 0 auto; padding: 3.5rem 0 4.5rem; }
    .br-policy-title {
        margin: 0;
        text-align: center;
        font-size: clamp(1.75rem, 3vw, 2.25rem);
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #1c1917;
    }
    .br-policy-lead {
        margin: .85rem auto 0;
        max-width: 36rem;
        text-align: center;
        color: #78716c;
        font-size: .98rem;
        line-height: 1.7;
    }
    .br-policy-toc {
        margin: 2rem auto 0;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: .55rem;
    }
    .br-policy-toc a {
        border: 1px solid #e7e0d6;
        background: #fffdf9;
        border-radius: 999px;
        padding: .45rem .9rem;
        font-size: .88rem;
        color: #1c1917;
        text-decoration: none;
    }
    .br-policy-toc a:hover { border-color: #b0894f; color: #b0894f; }
    .br-policy-section {
        margin-top: 3.25rem;
        padding-top: 2.25rem;
        border-top: 1px solid #e5e5e5;
    }
    .br-policy-section:first-of-type { border-top: 0; padding-top: 0; }
    .br-policy-heading {
        margin: 0 0 1rem;
        text-align: center;
        font-size: clamp(1.4rem, 2.2vw, 1.85rem);
        font-weight: 700;
        color: #1c1917;
    }
    .br-policy-intro,
    .br-policy-text {
        margin: 0 auto;
        max-width: 40rem;
        color: #57534e;
        font-size: 1rem;
        line-height: 1.8;
        text-align: left;
    }
    .br-policy-intro { margin-bottom: 1.35rem; }
    .br-policy-sub {
        margin: 1.5rem 0 .55rem;
        font-size: 1.05rem;
        font-weight: 700;
        color: #1c1917;
    }
    .br-policy-text + .br-policy-sub { margin-top: 1.5rem; }
    .br-policy-text a {
        color: #2563eb;
        text-decoration: underline;
        text-underline-offset: 2px;
    }
    .br-policy-note {
        margin: 2.5rem auto 0;
        max-width: 40rem;
        padding: 1.25rem 1.35rem;
        border: 1px solid #e7e0d6;
        border-radius: 1.25rem;
        background: #fffdf9;
        color: #78716c;
        font-size: .95rem;
        line-height: 1.7;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div class="br-policy">
    <div class="br-policy-wrap">
        <h1 class="br-policy-title">Our Policies</h1>
        <p class="br-policy-lead">
            These policies explain how Black Rossy handles privacy, shipping, returns and website use when you shop Rossy Apparel, Lustre, Stride and Carry.
        </p>

        <nav class="br-policy-toc" aria-label="Policy sections">
            <a href="#privacy">Privacy</a>
            <a href="#shipping">Shipping</a>
            <a href="#returns">Returns</a>
            <a href="#terms">Terms</a>
        </nav>

        <section id="privacy" class="br-policy-section scroll-mt-28">
            <h2 class="br-policy-heading">Privacy Policy</h2>
            <p class="br-policy-intro">
                We value your privacy and protect the details you share while browsing or placing a Cash on Delivery order. This section explains what we collect and why.
            </p>
            <h3 class="br-policy-sub">1. Information we collect</h3>
            <p class="br-policy-text">
                When you place an order, we collect your name, mobile number, delivery address and order notes. If you use Contact or WhatsApp, we also keep the message details needed to reply. Technical data such as browser type and basic visit logs may be collected to keep the website secure and working.
            </p>
            <h3 class="br-policy-sub">2. How we use your information</h3>
            <p class="br-policy-text">
                We use order information to confirm purchases, arrange packing and delivery, share shipping updates, handle returns, and answer support requests. We do not sell your personal information. Service partners such as couriers receive only what they need to deliver your parcel.
            </p>
            <h3 class="br-policy-sub">3. Your choices</h3>
            <p class="br-policy-text">
                For privacy questions or correction requests, email us using the details on our <a href="{{ route('contact') }}">Contact</a> page, with your order number and registered mobile number. The full privacy text is also available on our <a href="{{ url('/privacy-policy') }}">Privacy Policy</a> page.
            </p>
        </section>

        <section id="shipping" class="br-policy-section scroll-mt-28">
            <h2 class="br-policy-heading">Shipping Policy</h2>
            <p class="br-policy-intro">
                Black Rossy delivers across eligible pin codes in India with Cash on Delivery where courier partners support it.
            </p>
            <h3 class="br-policy-sub">1. Dispatch timeline</h3>
            <p class="br-policy-text">
                Most orders are packed within 1–2 working days after confirmation. Orders placed on Sundays or public holidays may move on the next working day. Delivery usually takes about 2–5 days depending on your pin code and courier route.
            </p>
            <h3 class="br-policy-sub">2. Delivery area and COD</h3>
            <p class="br-policy-text">
                We ship to serviceable Indian pin codes. If COD is not available for your area, our team will contact you after the order is received. Shipping is free on every order, with no minimum order value. Track your parcel anytime from <a href="{{ route('track') }}">Track Order</a>.
            </p>
        </section>

        <section id="returns" class="br-policy-section scroll-mt-28">
            <h2 class="br-policy-heading">Return Policy</h2>
            <p class="br-policy-intro">
                If an item is not right for you, we keep returns simple for eligible unused products.
            </p>
            <h3 class="br-policy-sub">1. Eligibility for returns</h3>
            <p class="br-policy-text">
                Unused products with original tags and packaging may be returned within 7 days of delivery. A few personal or hygiene-sensitive items can have extra limits when listed. Used, washed, damaged or incomplete items are not eligible.
            </p>
            <h3 class="br-policy-sub">2. How to start a return</h3>
            <p class="br-policy-text">
                Email or WhatsApp us (details on our <a href="{{ route('contact') }}">Contact</a> page) with your order number, product name and reason for return. After approval, we share pickup or drop instructions. Approved COD returns follow the process explained in our <a href="{{ url('/refund-policy') }}">Refund Policy</a>.
            </p>
        </section>

        <section id="terms" class="br-policy-section scroll-mt-28">
            <h2 class="br-policy-heading">Terms and Conditions</h2>
            <p class="br-policy-intro">
                By using the Black Rossy website or placing an order, you agree to the shopping rules below.
            </p>
            <h3 class="br-policy-sub">1. Website and product information</h3>
            <p class="br-policy-text">
                Product photos and descriptions are prepared carefully, but colours can look slightly different on different screens. Stock, prices and offers may change without notice until an order is confirmed.
            </p>
            <h3 class="br-policy-sub">2. Orders and liability</h3>
            <p class="br-policy-text">
                We may verify mobile numbers before confirming COD orders. Black Rossy is not responsible for delays caused by courier partners, incorrect addresses, or events outside our control. Full legal terms are published on our <a href="{{ url('/terms') }}">Terms &amp; Conditions</a> page.
            </p>
        </section>

        <p class="br-policy-note">
            Need a specific legal page?
            <a href="{{ url('/privacy-policy') }}">Privacy</a>,
            <a href="{{ url('/shipping-policy') }}">Shipping</a>,
            <a href="{{ url('/return-policy') }}">Returns</a>,
            <a href="{{ url('/refund-policy') }}">Refunds</a>,
            <a href="{{ url('/cancellation-policy') }}">Cancellation</a>,
            <a href="{{ url('/terms') }}">Terms</a>
            and
            <a href="{{ url('/cookie-policy') }}">Cookies</a>
            remain available as full pages.
        </p>
    </div>
</div>
@endsection
