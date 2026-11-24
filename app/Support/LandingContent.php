<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Every editable text on the public landing page.
 * Defaults live here; only texts the admin changes are stored in `landing_contents`.
 * Texts may use tokens: {price} {divisions} {districts} {upazilas} {bkash} {phone}.
 */
class LandingContent
{
    public const CACHE_KEY = 'landing:content:v1';

    public const TOKENS = ['{price}', '{divisions}', '{districts}', '{upazilas}', '{bkash}', '{phone}'];

    /** section => [label, icon, fields: key => [label, default, type]] (type: line | text) */
    public static function sections(): array
    {
        $f = fn (string $label, string $default, string $type = 'line') => [$label, $default, $type];

        return [
            'seo' => ['Page & menu', [
                'seo.title' => $f('Browser tab title', 'বাংলাদেশ: প্রতিটি উপজেলা উন্মোচিত — Bangladesh: Every Upazila Unveiled'),
                'seo.description' => $f('Google / share description', 'বাংলাদেশের {districts}টি জেলা ও {upazilas}টি উপজেলার মানচিত্র এক বইয়ে। বিভাগ → জেলা → উপজেলা, দুই ক্লিকে যেকোনো উপজেলা। মূল্য মাত্র ৳{price}।', 'text'),
                'nav.brand' => $f('Logo text', 'প্রতিটি উপজেলা উন্মোচিত'),
                'nav.features' => $f('Menu link 1', 'ভেতরে কী আছে'),
                'nav.preview' => $f('Menu link 2', 'নমুনা'),
                'nav.audience' => $f('Menu link 3', 'কাদের জন্য'),
                'nav.pricing' => $f('Menu link 4', 'মূল্য'),
                'nav.faq' => $f('Menu link 5', 'প্রশ্নোত্তর'),
                'nav.button' => $f('Menu button', 'অর্ডার করুন'),
            ]],
            'hero' => ['Hero (top)', [
                'hero.badge' => $f('Small badge', 'বাংলাদেশের পূর্ণাঙ্গ উপজেলা মানচিত্র বই'),
                'hero.title' => $f('Big title (one line per row)', "Bangladesh:\nEvery Upazila", 'text'),
                'hero.highlight' => $f('Yellow title word', 'Unveiled'),
                'hero.subtitle' => $f('Bangla subtitle', 'বাংলাদেশ: প্রতিটি উপজেলা উন্মোচিত'),
                'hero.description' => $f('Description', 'বাংলাদেশের {districts}টি জেলা ও {upazilas}টি উপজেলার মানচিত্র একটি গোছানো বইয়ে। বিভাগ → জেলা → উপজেলা — ক্লিক করে যেকোনো পৃষ্ঠায় যান, জুম করে বিস্তারিত দেখুন।', 'text'),
                'hero.cta' => $f('Main button', '৳{price}-এ কিনুন'),
                'hero.cta2' => $f('Second button', 'নমুনা মানচিত্র দেখুন'),
                'hero.stat1' => $f('Stat 1 label', 'বিভাগ'),
                'hero.stat2' => $f('Stat 2 label', 'জেলার মানচিত্র'),
                'hero.stat3' => $f('Stat 3 label', 'উপজেলা/থানা'),
                'hero.stat4' => $f('Stat 4 label', 'এক দামে সব'),
                'hero.cover' => $f('Book cover small text', 'বাংলাদেশ: প্রতিটি উপজেলা উন্মোচিত'),
                'strip.1' => $f('Highlight 1', 'প্রতিটি জেলার পূর্ণ পৃষ্ঠার মানচিত্র'),
                'strip.2' => $f('Highlight 2', 'বিভাগভিত্তিক সাজানো'),
                'strip.3' => $f('Highlight 3', 'A–Z উপজেলা সূচিপত্র'),
                'strip.4' => $f('Highlight 4', 'মোবাইল, ট্যাব ও কম্পিউটারে PDF'),
            ]],
            'features' => ['Features', [
                'features.eyebrow' => $f('Small heading', 'ভেতরে কী আছে'),
                'features.title' => $f('Title', "মাঠে যারা কাজ করেন,\nতাদের জন্য তৈরি মানচিত্র বই", 'text'),
                'features.lead' => $f('Intro text', 'অনুমাননির্ভর কিছু নেই — প্রতিটি পৃষ্ঠায় একটি মানচিত্র, আপনার প্রয়োজনের জন্য সাজানো।', 'text'),
                'features.1.title' => $f('Card 1 title', 'প্রতিটি জেলা, প্রতিটি উপজেলা'),
                'features.1.text' => $f('Card 1 text', '{districts}টি জেলার মানচিত্র — প্রতিটিতে সব উপজেলা/থানার সীমানা ও নাম স্পষ্টভাবে চিহ্নিত।', 'text'),
                'features.2.title' => $f('Card 2 title', 'বিভাগভিত্তিক সাজানো'),
                'features.2.text' => $f('Card 2 text', '{divisions}টি বিভাগ ধরে অধ্যায় — বিভাগ থেকে জেলা, জেলা থেকে উপজেলায় সহজে যান।', 'text'),
                'features.3.title' => $f('Card 3 title', 'যেকোনো উপজেলা মুহূর্তেই খুঁজুন'),
                'features.3.text' => $f('Card 3 text', 'ক্লিকযোগ্য সূচিপত্র ও A–Z উপজেলা তালিকা — নাম জানলেই সরাসরি সঠিক পৃষ্ঠায়।', 'text'),
                'features.4.title' => $f('Card 4 title', 'জুম করে বিস্তারিত দেখুন'),
                'features.4.text' => $f('Card 4 text', 'উচ্চ রেজোলিউশনের মানচিত্র — জুম করলেও লেখা ও সীমানা ঝাপসা হয় না।', 'text'),
                'features.5.title' => $f('Card 5 title', 'মুদ্রণযোগ্য পৃষ্ঠা'),
                'features.5.text' => $f('Card 5 text', 'প্রয়োজনীয় পৃষ্ঠা প্রিন্ট করে মাঠে নিয়ে যান — মিটিং, জরিপ বা প্রেজেন্টেশনে।', 'text'),
                'features.6.title' => $f('Card 6 title', 'যেকোনো ডিভাইসে চলে'),
                'features.6.text' => $f('Card 6 text', 'একটি PDF ফাইল — মোবাইল, ট্যাবলেট, ল্যাপটপ সবখানে; ইন্টারনেট ছাড়াও দেখা যায়।', 'text'),
            ]],
            'preview' => ['Preview & navigation', [
                'preview.eyebrow' => $f('Small heading', 'নমুনা'),
                'preview.title' => $f('Title', 'ভেতরে একনজর'),
                'preview.lead' => $f('Intro text', 'প্রতিটি বিভাগ একটি অধ্যায়। বিভাগে ক্লিক করে দেখুন কোন কোন জেলার মানচিত্র রয়েছে।', 'text'),
                'preview.toggle' => $f('"Show districts" link', 'জেলাগুলো দেখুন'),
                'navigate.eyebrow' => $f('Two-click: small heading', 'সময় বাঁচাবে প্রতিটি পৃষ্ঠা'),
                'navigate.title' => $f('Two-click: title', 'কভার থেকে যেকোনো উপজেলায় মাত্র দুই ক্লিকে', 'text'),
                'navigate.1' => $f('Check 1', 'প্রতিটি বিভাগ ও জেলার লিংকসহ সূচিপত্র'),
                'navigate.2' => $f('Check 2', '{upazilas}টি উপজেলার A–Z তালিকা, প্রতিটির পাশে জেলার নাম'),
                'navigate.3' => $f('Check 3', '{divisions}টি বিভাগের প্রতিটিতে আলাদা অধ্যায়'),
                'navigate.4' => $f('Check 4', 'ক্লিকযোগ্য ব্রেডক্রাম্ব: বিভাগ → জেলা → উপজেলা'),
                'navigate.5' => $f('Check 5', 'স্ক্রল করে খুঁজতে হবে না — সরাসরি সঠিক পৃষ্ঠায়'),
            ]],
            'audience' => ['Who it is for', [
                'audience.eyebrow' => $f('Small heading', 'কাদের জন্য'),
                'audience.title' => $f('Title', "মাঠপর্যায়ে পরিকল্পনা করা\nপ্রতিটি টিমের জন্য একটি রেফারেন্স", 'text'),
                'audience.1' => $f('Card 1 (emoji | title | text)', '🏢 | এনজিও ও উন্নয়নকর্মী | প্রকল্প এলাকা ও কর্মী নিয়োগের পরিকল্পনা।'),
                'audience.2' => $f('Card 2', '📊 | গবেষক ও জরিপকারী | স্যাম্পলিং এলাকা ও মাঠ জরিপের রুট ঠিক করুন।'),
                'audience.3' => $f('Card 3', '🚚 | সেলস ও ডিস্ট্রিবিউশন | টেরিটরি ভাগ, রুট প্ল্যান ও কভারেজ বিশ্লেষণ।'),
                'audience.4' => $f('Card 4', '📰 | সাংবাদিক | রিপোর্টে সঠিক এলাকা ও প্রেক্ষাপট দ্রুত যাচাই।'),
                'audience.5' => $f('Card 5', '🎓 | শিক্ষক ও শিক্ষার্থী | ভূগোল, ক্লাস ও প্রতিযোগিতার প্রস্তুতি।'),
                'audience.6' => $f('Card 6', '🏛️ | সরকারি কর্মকর্তা | প্রশাসনিক এলাকা ও সমন্বয়ের কাজে।'),
                'audience.7' => $f('Card 7', '🧭 | প্রকল্প পরিকল্পনাকারী | সাইট নির্বাচন ও লজিস্টিক পরিকল্পনা।'),
                'audience.8' => $f('Card 8', '🏞️ | ভ্রমণপ্রেমী | নতুন জেলা-উপজেলা ঘুরে দেখার আগে চিনে নিন।'),
            ]],
            'pricing' => ['Price', [
                'pricing.eyebrow' => $f('Small heading', 'মূল্য'),
                'pricing.title' => $f('Title', 'এক দামে পুরো বাংলাদেশ'),
                'pricing.lead' => $f('Intro text', 'একবার কিনুন, সারাজীবন ব্যবহার করুন — কোনো সাবস্ক্রিপশন নেই।', 'text'),
                'pricing.ribbon' => $f('Card ribbon', 'সম্পূর্ণ সংস্করণ'),
                'pricing.name' => $f('Product name', 'Bangladesh: Every Upazila Unveiled'),
                'pricing.sub' => $f('Product sub-line', '{divisions}টি বিভাগ একটি বইয়ে'),
                'pricing.once' => $f('Under the price', 'এককালীন মূল্য'),
                'pricing.includes' => $f('What is included (one per line)', "{districts}টি জেলার পূর্ণ পৃষ্ঠার মানচিত্র\n{upazilas}টি উপজেলা/থানা চিহ্নিত\nক্লিকযোগ্য সূচিপত্র ও A–Z উপজেলা তালিকা\nবিভাগভিত্তিক অধ্যায়\nএকটি PDF — যেকোনো ডিভাইসে", 'text'),
                'pricing.button' => $f('Button', 'এখনই অর্ডার করুন'),
                'pricing.table_title' => $f('Table title', 'যা যা আছে'),
                'pricing.table_sub' => $f('Table sub-title', 'বিভাগ অনুযায়ী জেলা ও উপজেলার সংখ্যা'),
            ]],
            'order' => ['Order form', [
                'order.eyebrow' => $f('Small heading', 'কীভাবে অর্ডার করবেন'),
                'order.title' => $f('Title', 'তিনটি সহজ ধাপ'),
                'order.step1' => $f('Step 1 (title | text)', 'অর্ডার পাঠান | নিচের ফর্মে নাম ও মোবাইল নম্বর দিয়ে অর্ডার পাঠান।'),
                'order.step2' => $f('Step 2 (title | text)', '৳{price} পরিশোধ করুন | আমরা ফোন করে নিশ্চিত করব। বিকাশে পেমেন্ট করুন।'),
                'order.step3' => $f('Step 3 (title | text)', 'বইটি পেয়ে যান | পেমেন্ট নিশ্চিত হলেই বইটি আপনার কাছে পাঠিয়ে দেওয়া হবে।'),
                'order.button' => $f('Form button', 'অর্ডার কনফার্ম করুন — ৳{price}'),
                'order.privacy' => $f('Text next to button', '🔒 আপনার তথ্য শুধু অর্ডারের জন্য ব্যবহার হবে।'),
                'order.success_title' => $f('After order: title', 'ধন্যবাদ! আপনার অর্ডার পেয়েছি'),
                'order.success_text' => $f('After order: text', 'খুব শিগগিরই আমরা আপনার নম্বরে যোগাযোগ করব।', 'text'),
                'order.closed' => $f('When orders are closed', 'এই মুহূর্তে অর্ডার নেওয়া বন্ধ আছে'),
                'order.contact_label' => $f('Phone box label', 'সরাসরি অর্ডার বা প্রশ্নের জন্য'),
                'order.call' => $f('Call button', '📞 এখনই কল করুন'),
                'order.assure1' => $f('Promise 1 (title | text)', 'ফোনে নিশ্চিতকরণ | অর্ডারের পর আমরা ফোন করে কনফার্ম করি।'),
                'order.assure2' => $f('Promise 2 (title | text)', 'বিকাশে পেমেন্ট | সহজ ও নিরাপদ পেমেন্ট।'),
                'order.assure3' => $f('Promise 3 (title | text)', 'দ্রুত ডেলিভারি | পেমেন্টের পরপরই বই পাঠানো হয়।'),
            ]],
            'faq' => ['FAQ', [
                'faq.eyebrow' => $f('Small heading', 'প্রশ্নোত্তর'),
                'faq.title' => $f('Title', 'আপনার প্রশ্নের উত্তর'),
                'faq.1.q' => $f('Question 1', 'ই-বুকটি কোন ফরম্যাটে?'),
                'faq.1.a' => $f('Answer 1', 'বইটি একটি PDF ফাইল। মোবাইল, ট্যাবলেট বা কম্পিউটার — যেকোনো ডিভাইসে খুলতে পারবেন, ডাউনলোডের পর ইন্টারনেট ছাড়াও দেখা যায়।', 'text'),
                'faq.2.q' => $f('Question 2', 'কতগুলো মানচিত্র আছে?'),
                'faq.2.a' => $f('Answer 2', '{divisions}টি বিভাগের অধীনে {districts}টি জেলার মানচিত্র রয়েছে, যেখানে মোট {upazilas}টি উপজেলা/থানা চিহ্নিত।', 'text'),
                'faq.3.q' => $f('Question 3', 'কোন কোন উপজেলা আছে?'),
                'faq.3.a' => $f('Answer 3', 'বাংলাদেশের সব উপজেলা ও থানা। "নমুনা" অংশে প্রতিটি বিভাগের জেলার তালিকা দেখতে পারবেন, আর বইয়ের ভেতরে A–Z উপজেলা সূচিপত্র রয়েছে।', 'text'),
                'faq.4.q' => $f('Question 4', 'কীভাবে পেমেন্ট করব?'),
                'faq.4.a' => $f('Answer 4', 'অর্ডার পাঠানোর পর আমরা ফোন করে নিশ্চিত করব। তারপর বিকাশে ৳{price} পাঠালেই হবে।', 'text'),
                'faq.5.q' => $f('Question 5', 'বইটি কখন পাব?'),
                'faq.5.a' => $f('Answer 5', 'পেমেন্ট নিশ্চিত হওয়ার পরপরই বইটি আপনার কাছে পাঠিয়ে দেওয়া হয়।', 'text'),
                'faq.6.q' => $f('Question 6', 'প্রিন্ট করা যাবে?'),
                'faq.6.a' => $f('Answer 6', 'হ্যাঁ, প্রয়োজনীয় পৃষ্ঠাগুলো ব্যক্তিগত ও অফিসের কাজে প্রিন্ট করতে পারবেন।', 'text'),
            ]],
            'cta' => ['Bottom & footer', [
                'cta.title' => $f('Bottom box title', 'পরবর্তী পরিকল্পনার আগে জানুন প্রতিটি উপজেলা'),
                'cta.text' => $f('Bottom box text', '{divisions}টি বিভাগ, {districts}টি জেলা, {upazilas}টি উপজেলা — একটি বইয়ে।', 'text'),
                'cta.button' => $f('Bottom box button', 'অর্ডার করুন — ৳{price} →'),
                'footer.text' => $f('Footer text', 'GeoNexa · Bangladesh: Every Upazila Unveiled'),
                'mbar.text' => $f('Mobile buy bar text', '{upazilas} উপজেলার মানচিত্র'),
            ]],
        ];
    }

    /** key => default */
    public static function defaults(): array
    {
        $out = [];
        foreach (self::sections() as [, $fields]) {
            foreach ($fields as $key => [, $default]) {
                $out[$key] = $default;
            }
        }

        return $out;
    }

    /** Saved overrides (cached). */
    public static function overrides(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => DB::table('landing_contents')->pluck('value', 'key')->all());
    }

    /** Final texts: defaults with admin changes on top. */
    public static function all(): array
    {
        return array_replace(self::defaults(), array_intersect_key(self::overrides(), self::defaults()));
    }

    /** Save the submitted texts; a blank or unchanged field goes back to the default. Returns number of changed keys. */
    public static function save(array $input): int
    {
        $defaults = self::defaults();
        $current = self::overrides();
        $changed = 0;
        foreach (array_intersect_key($input, $defaults) as $key => $value) {
            $value = str_replace("\r\n", "\n", trim((string) $value));
            $custom = $value !== '' && $value !== $defaults[$key];
            if ($custom && ($current[$key] ?? null) !== $value) {
                DB::table('landing_contents')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now(), 'created_at' => now()]);
                $changed++;
            } elseif (! $custom && array_key_exists($key, $current)) {
                DB::table('landing_contents')->where('key', $key)->delete();
                $changed++;
            }
        }
        Cache::forget(self::CACHE_KEY);

        return $changed;
    }
}
