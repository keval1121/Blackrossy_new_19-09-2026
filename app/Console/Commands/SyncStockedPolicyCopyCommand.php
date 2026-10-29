<?php

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('shop:sync-stocked-policy-copy {--dry-run : Preview changes without writing}')]
#[Description('Rewrite Terms/Return/FAQ CMS copy to clothing and bags only — removes jewellery/footwear sell claims')]
class SyncStockedPolicyCopyCommand extends Command
{
    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        foreach ($this->pages() as $slug => $content) {
            $page = Page::query()->where('slug', $slug)->first();
            if ($page === null) {
                $this->warn("Missing CMS page: {$slug}");

                continue;
            }

            if ($dry) {
                $this->line("Would update {$slug} (".strlen($content).' chars)');

                continue;
            }

            $page->update(['content' => $content, 'is_active' => true]);
            $this->info("Updated {$slug}");
        }

        if (! $dry) {
            Cache::forget('shop.settings');
        }

        $this->info($dry ? 'Dry run complete.' : 'Stocked-policy CMS copy synced.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function pages(): array
    {
        return [
            'terms' => <<<'TXT'
Last updated: 22 September 2026
These Terms & Conditions (“Terms”) govern your use of the Black Rossy website and any order you place with us. By browsing or placing an order you agree to these Terms, our Privacy Policy, Cookie Policy, Shipping Policy, Return Policy, Refund Policy and Cancellation Policy.
1. About Black Rossy
Black Rossy is an online retail store based in India selling clothing and bags. Business contact details appear on the Contact page and footer.
2. Eligibility
You must be able to form a binding contract under Indian law and provide accurate delivery details. If you order for someone else, you confirm you are authorised to do so.
3. Products and pricing
• Product photos and descriptions represent the item offered for sale. Minor differences in colour or finish can occur due to screen settings and lighting.
• Prices are shown in Indian Rupees (₹) and include applicable taxes unless stated otherwise.
• MRP and selling price may both be shown. Offers and coupons are subject to their own rules and may be withdrawn.
• We may correct obvious pricing or stock errors. If an error affects your order we will contact you; you may cancel with no charge for COD.
4. Orders and Cash on Delivery (COD)
• Placing an order is an offer to buy. Acceptance occurs when we confirm and pack the order.
• COD customers must pay the exact payable amount in cash (or as accepted by the courier) to the delivery partner on delivery.
• We may call or send OTP/SMS to verify mobile numbers before dispatch.
• We may refuse or cancel orders that fail verification, appear fraudulent, exceed quantity limits, or cannot be fulfilled.
5. Coupons and promotions
Coupons must be entered at checkout, are non-transferable, and cannot usually be combined unless stated. Misuse may lead to cancellation.
6. Shipping and risk
Delivery timelines are estimates (see Shipping Policy). Title and risk in goods pass when you (or someone at the address) accept delivery, except where law requires otherwise. Keep packaging until you are satisfied with the product.
7. Returns, refunds and cancellations
Returns, refunds and cancellations are governed by the Return Policy, Refund Policy and Cancellation Policy. Opened personal-care or hygiene-sensitive items are generally non-returnable.
8. User conduct
You agree not to misuse the site (scraping that harms the service, fake orders, abusive messages, or attempts to breach security). We may block access or cancel orders for misuse.
9. Intellectual property
Store branding, text, product photography we create, layout and logos are owned by Black Rossy or our licensors. You may not copy catalogue content for commercial use without permission. Third-party trademarks in photos remain their owners’ property.
10. Reviews and user content
If you submit reviews or messages, you grant us a non-exclusive licence to use them on the site for legitimate business purposes, and you confirm the content is lawful and not misleading.
11. Third-party services and ads
The site may use hosting, analytics, messaging and advertising services (including Google AdSense when enabled). Their terms and privacy practices apply to their processing. Ads are disclosed in the Privacy Policy and Cookie Policy.
12. Disclaimer
The site is provided on an “as available” basis. We do not guarantee uninterrupted access. Product availability may change.
13. Limitation of liability
To the fullest extent permitted by Indian law, Black Rossy is not liable for indirect, incidental or consequential losses (including lost profits). Our total liability related to any order is limited to the amount you paid (or were to pay on COD) for that order. Nothing in these Terms limits liability that cannot be limited by law (including for fraud or personal injury caused by negligence where such limits are not allowed).
14. Indemnity
You agree to indemnify Black Rossy against reasonable losses arising from your breach of these Terms or misuse of the site, to the extent permitted by law.
15. Governing law and disputes
These Terms are governed by the laws of India. Courts in Gujarat, India shall have exclusive jurisdiction, subject to any mandatory consumer protections that apply to you.
16. Changes
We may update these Terms. The “Last updated” date will change when we do. Continued use after changes constitutes acceptance. For material changes affecting existing orders, the version in force when you ordered will usually apply to that order.
17. Contact
Questions about these Terms: Contact page, or the email / phone / WhatsApp listed there and in the footer.
TXT,
            'return-policy' => <<<'TXT'
Last updated: 22 September 2026
1. Return window
Unused products with original tags and packaging may be returned within 7 days of delivery.
2. Eligible
Clothing and bags in original condition with tags and packaging intact. Other lines, if stocked later, follow the same unused-item rules unless the product page says otherwise.
3. Not eligible
• Opened personal-care or hygiene-sensitive products
• Used innerwear or products marked non-returnable on the product page
• Items damaged by misuse, washing against care instructions, or missing parts/tags
• Free gifts or samples
4. How to request
Email or WhatsApp us (details on the Contact page) with: order number, registered mobile, product name, reason, and clear photos. After we approve, follow pickup or self-ship instructions we share.
5. Inspection
Returned items are checked for condition, tags and authenticity. Items that fail inspection may be sent back to you without refund.
6. Replacement vs refund
Where stock allows you may choose replacement of the same item; otherwise an approved return follows the Refund Policy.
7. Reverse pickup
Reverse pickup availability depends on pin code and courier. If pickup is unavailable, we may ask you to self-ship and share reimbursement rules for reasonable courier cost on approved returns.
Related: Refund Policy · Shipping Policy · Cancellation Policy
TXT,
            'faq' => <<<'TXT'
Last updated: 3 October 2026
Ordering and payment
What payment methods do you accept?
Black Rossy currently offers Cash on Delivery (COD) on eligible pin codes across India. You pay when the order is delivered to your address. No prepaid card or UPI is required at checkout.
Do I need an account to order?
No. Guest checkout is available. Enter your name, mobile number and delivery address to place an order.
Can I modify or cancel my order?
Contact us as soon as possible with your order number through the Contact page or WhatsApp. If the order is not yet packed or shipped, we will try to modify or cancel it. Once shipped, normal return rules apply.
Shipping and delivery
How long does delivery take?
Most orders are packed within 24–48 business hours and delivered in about 2–5 days depending on your pin code. Exact timing can vary by courier and location.
Do you offer free shipping?
Yes. Shipping is free on every order across India, with no minimum order value.
How do I track my order?
Use Track Order on the website with your order number and mobile number.
Returns and refunds
What is your return policy?
Eligible unused products can be returned within 7 days of delivery when they meet the conditions in our Return Policy. A few personal or hygiene-sensitive items may have special rules when listed.
How do refunds work for COD orders?
Approved returns are handled as described in our Refund Policy. For COD orders we share the refund method after the return is received and checked.
Product questions
Are product photos and descriptions accurate?
We aim for clear photos and honest descriptions of fit, material and occasion. Colours can look slightly different on different screens. Size guidance is included on clothing product pages where available.
Need more help?
Visit Contact, or message us on WhatsApp from the footer. Also see About Us, Shipping Policy, Return Policy and Privacy Policy.
TXT,
        ];
    }
}
