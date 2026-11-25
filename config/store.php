<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    |
    | The first language is the default and has no URL prefix (/products/x).
    | Every other language is served under its code (/ar/products/x).
    |
    */

    // STORE_LOCALES picks which of these the store uses, e.g. "en" or "en,ar".
    /*
     * Every language the code can run in. Which of them a store actually uses is chosen in
     * Settings → Store details; STORE_LOCALES only sets what a brand-new store starts with.
     */
    /*
     * The designs this installation ships with. The active one is 'theme' below; this is
     * only what the admin shows on Online Store → Themes.
     */
    'themes' => [
        'furnivo' => [
            'name' => 'Furnivo',
            'preview' => 'themes/furnivo/preview.jpg',
        ],
    ],

    'all_locales' => [
        'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'dir' => 'rtl'],
    ],

    'locales' => array_intersect_key([
        'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'dir' => 'rtl'],
    ], array_flip(array_map('trim', explode(',', env('STORE_LOCALES', 'en,ar'))))),

    /*
    |--------------------------------------------------------------------------
    | Storefront theme
    |--------------------------------------------------------------------------
    |
    | Each store's design lives in resources/themes/<name>/views. Any page a
    | theme doesn't provide falls back to resources/views (the base theme).
    |
    */

    'theme' => env('STORE_THEME'),

    /*
     * Read-only admin for a public demo. With this on, admin screens open normally but
     * nothing can be changed. Leave it off for a real shop.
     */
    // The /landing marketing page. Off by default: a buyer's shop should not have the
    // product's own sales page in its search results. Turn it on if you are the one selling.
    'landing_indexable' => (bool) env('LANDING_INDEXABLE', false),
    // Other sites allowed to show the landing page and storefront in an iframe (e.g. a marketplace
    // demo frame): "https://vibe96.com https://www.vibe96.com". Empty = this site only.
    'allow_framing_from' => env('ALLOW_FRAMING_FROM', ''),

    'demo' => (bool) env('DEMO_MODE', false),
    'demo_role_login' => (bool) env('DEMO_ROLE_LOGIN', false),

    // Shown on the admin sign-in page while demo mode is on, so visitors can get in.
    'demo_email' => env('DEMO_EMAIL'),        // no default: set it (and DEMO_PASSWORD) to enable the demo sign-in
    'demo_password' => env('DEMO_PASSWORD'),
    // Role of the demo account: owner (sees every screen, incl. settings — all read-only in demo
    // mode, secrets and staff emails hidden) or manager / orders / content for a narrower demo.
    'demo_role' => env('DEMO_ROLE', 'owner'),

    'default_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Money
    |--------------------------------------------------------------------------
    |
    | All prices are stored in the base currency. Other currencies are
    | display/checkout conversions using exchange rates set in the admin.
    |
    */

    'base_currency' => env('STORE_CURRENCY', 'AED'),

    // Timezone used when showing dates to the store owner and customers.
    // Dates are always stored in UTC.
    'display_timezone' => env('STORE_TIMEZONE', 'Asia/Dubai'),

    /*
    |--------------------------------------------------------------------------
    | Admin panel
    |--------------------------------------------------------------------------
    */

    'admin_path' => env('ADMIN_PATH', 'admin'),

    'uploads' => [
        'disk' => env('STORE_UPLOAD_DISK', 'public'),
        'max_image_kb' => 2048,
    ],

    // CSV product import: where the uploaded file waits between preview and import, size limit.
    'imports' => [
        'disk' => env('STORE_IMPORT_DISK', 'local'),
        'max_size_kb' => 5120,
    ],

    // Page sizes of admin lists.
    'pagination' => [
        'admin_list' => 50,
        'admin_relations' => 25,
        'inventory' => 30,
        'customer_orders' => 25,
    ],

    // Length of the throw-away password given to an invited admin (they set their own by link).
    'admin_invite_password_length' => 32,

    // Admin dashboard: how many days the sales report covers.
    'dashboard' => [
        'report_days' => 30,
    ],

    // Draft orders: most lines one draft may hold (keeps a draft a normal-sized order).
    'draft_order_max_lines' => 100,

    // Low-stock warning level used when Settings → Inventory has none yet.
    'low_stock_threshold' => 5,


    /*
    | Where the store delivers. Keys are stored on orders; names come from lang files.
    */
    // The store's own country (ISO code): pre-selected on address forms until Settings → Shipping
    // names one. AE for a UAE shop, US for FreshNest's demo …
    'default_country' => env('STORE_COUNTRY', 'AE'),

    'emirates' => ['abu_dhabi', 'dubai', 'sharjah', 'ajman', 'umm_al_quwain', 'ras_al_khaimah', 'fujairah'],

    /*
    | Orders paid by card that aren't paid within this many minutes are cancelled
    | and their stock released (php artisan orders:release-unpaid, every 10 minutes).
    */
    'unpaid_order_minutes' => (int) env('UNPAID_ORDER_MINUTES', 60),

    'order_number_prefix' => env('ORDER_NUMBER_PREFIX', 'SH-'),
];
