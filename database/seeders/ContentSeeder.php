<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * The pages linked from the footer menu, in English and Arabic. The wording is a
 * starting point: the store owner should review it (especially the policies).
 * Existing pages are never overwritten.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $slug => $page) {
            Page::query()->firstOrCreate(['slug' => $slug], $page);
        }

        if (! app()->isProduction()) {
            Discount::query()->firstOrCreate(['code' => 'WELCOME10'], [
                'type' => 'percentage', 'value' => '10.00', 'usage_limit_per_customer' => 1, 'is_active' => true,
            ]);
        }
    }

    private function pages(): array
    {
        return [
            'about' => [
                'title' => ['en' => 'About us', 'ar' => 'من نحن'],
                'body' => [
                    'en' => "Demo Store brings together homeware and gifts inspired by the Emirates — from hand-woven cushions to brass dallahs for serving gahwa.\n\n## What we believe\n\n- **Made to last.** We choose pieces that are made well and age beautifully.\n- **Made with care.** Many of our textiles are woven by artisans in the UAE.\n- **Made to share.** Homes here are for guests, so everything is chosen with hospitality in mind.\n\nWe are based in Dubai and deliver across the UAE.",
                    'ar' => "يجمع المتجر قطعاً منزلية وهدايا مستوحاة من الإمارات — من الوسائد المنسوجة يدوياً إلى الدلال النحاسية لتقديم القهوة العربية.\n\n## ما نؤمن به\n\n- **قطع تدوم.** نختار قطعاً مصنوعة بإتقان وتزداد جمالاً مع الوقت.\n- **صُنعت بعناية.** تُنسج كثير من منسوجاتنا على أيدي حرفيين في الإمارات.\n- **للمشاركة.** البيوت هنا للضيوف، لذلك نختار كل شيء بروح الضيافة.\n\nمقرّنا في دبي ونوصّل إلى جميع أنحاء الإمارات.",
                ],
            ],
            'contact' => [
                'title' => ['en' => 'Contact us', 'ar' => 'تواصل معنا'],
                'show_contact_form' => true,
                'body' => [
                    'en' => "We're happy to help with orders, delivery or choosing a gift.\n\n**Email:** hello@example.com  \n**Phone / WhatsApp:** +971 50 000 0000  \n**Hours:** Sunday to Thursday, 9am – 6pm\n\nOr send us a message below and we'll reply within one working day.",
                    'ar' => "يسعدنا مساعدتك في الطلبات أو التوصيل أو اختيار هدية.\n\n**البريد الإلكتروني:** hello@example.com  \n**الهاتف / واتساب:** ‎+971 50 000 0000  \n**ساعات العمل:** من الأحد إلى الخميس، 9 صباحاً – 6 مساءً\n\nأو أرسل لنا رسالة أدناه وسنرد خلال يوم عمل واحد.",
                ],
            ],
            'delivery-returns' => [
                'title' => ['en' => 'Delivery & returns', 'ar' => 'التوصيل والإرجاع'],
                'body' => [
                    'en' => "## Delivery\n\nWe deliver across the UAE in 1–3 working days. Delivery is AED 25, and free on orders over AED 200.\n\nYou can pay by card when you order, or in cash when your order arrives.\n\n## Returns\n\nChanged your mind? You can return unused items in their original packaging within 14 days of delivery. Contact us with your order number and we'll arrange a collection.\n\nRefunds are made to the original payment method within 7 working days of the item reaching us. Items that are damaged on arrival are replaced free of charge — please send us a photo within 48 hours.",
                    'ar' => "## التوصيل\n\nنوصّل إلى جميع أنحاء الإمارات خلال 1–3 أيام عمل. رسوم التوصيل 25 درهماً، ومجاناً للطلبات فوق 200 درهم.\n\nيمكنك الدفع بالبطاقة عند الطلب، أو نقداً عند الاستلام.\n\n## الإرجاع\n\nغيّرت رأيك؟ يمكنك إرجاع المنتجات غير المستخدمة في تغليفها الأصلي خلال 14 يوماً من التوصيل. تواصل معنا مع رقم طلبك وسنرتب الاستلام.\n\nيُعاد المبلغ إلى وسيلة الدفع الأصلية خلال 7 أيام عمل من وصول المنتج إلينا. تُستبدل المنتجات التالفة عند الوصول مجاناً — يرجى إرسال صورة خلال 48 ساعة.",
                ],
            ],
            'privacy' => [
                'title' => ['en' => 'Privacy policy', 'ar' => 'سياسة الخصوصية'],
                'body' => [
                    'en' => "This page explains what personal information we collect and how we use it.\n\n## What we collect\n\nYour name, email, phone number and delivery address when you order or create an account, and your order history.\n\n## How we use it\n\nTo deliver your orders, contact you about them, and — only if you agree — to email you about new products and offers.\n\n## Payments\n\nCard payments are handled by Stripe. We never see or store your card details.\n\n## Your choices\n\nYou can ask us to update or delete your information at any time by contacting us.",
                    'ar' => "توضح هذه الصفحة المعلومات الشخصية التي نجمعها وكيف نستخدمها.\n\n## ما نجمعه\n\nاسمك وبريدك الإلكتروني ورقم هاتفك وعنوان التوصيل عند الطلب أو إنشاء حساب، وسجل طلباتك.\n\n## كيف نستخدمها\n\nلتوصيل طلباتك والتواصل معك بشأنها، و— فقط إذا وافقت — لمراسلتك بالمنتجات الجديدة والعروض.\n\n## المدفوعات\n\nتتم المدفوعات بالبطاقة عبر Stripe. لا نطّلع على بيانات بطاقتك ولا نخزّنها أبداً.\n\n## خياراتك\n\nيمكنك طلب تحديث معلوماتك أو حذفها في أي وقت بالتواصل معنا.",
                ],
            ],
            'terms' => [
                'title' => ['en' => 'Terms & conditions', 'ar' => 'الشروط والأحكام'],
                'body' => [
                    'en' => "By placing an order with Demo Store you agree to these terms.\n\n## Prices\n\nPrices are in UAE dirhams (AED) and include VAT. Prices shown in other currencies are for reference; you are charged in AED.\n\n## Orders\n\nAn order is confirmed once we send the confirmation email. If an item becomes unavailable we will contact you and refund any payment for it.\n\n## Delivery and returns\n\nSee our [delivery and returns policy](/pages/delivery-returns).\n\n## Contact\n\nQuestions about these terms? [Contact us](/pages/contact).",
                    'ar' => "بتقديم طلب لدى المتجر فإنك توافق على هذه الشروط.\n\n## الأسعار\n\nالأسعار بالدرهم الإماراتي وتشمل ضريبة القيمة المضافة. الأسعار المعروضة بعملات أخرى للاسترشاد فقط، ويتم الدفع بالدرهم.\n\n## الطلبات\n\nيُعد الطلب مؤكداً عند إرسال رسالة التأكيد. إذا أصبح منتج غير متوفر فسنتواصل معك ونعيد أي مبلغ مدفوع مقابله.\n\n## التوصيل والإرجاع\n\nراجع [سياسة التوصيل والإرجاع](/ar/pages/delivery-returns).\n\n## التواصل\n\nلديك أسئلة حول هذه الشروط؟ [تواصل معنا](/ar/pages/contact).",
                ],
            ],
        ];
    }
}
