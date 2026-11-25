# Working on FreshNest

A guide for whoever picks this up next — a developer or an AI coding tool.

## What this is

A single-store ecommerce application. One admin panel, one storefront, one database. It is
not multi-tenant: one installation serves one shop.

Laravel 12 · PHP 8.2.35+ · MySQL 8 / MariaDB 10.11 · Blade + Alpine.js · Tailwind CSS 4 · Vite.

## How it is laid out

    app/
      Actions/          One job each, called by controllers (PlaceOrder, SaveProduct)
      Console/Commands/ store:* commands — launch-check, demo-reset, import-design-images
      Enums/            OrderStatus, PaymentStatus, FulfillmentStatus, ProductStatus
      Http/
        Controllers/
          Admin/        The admin, grouped by area (Catalog, Sales, Settings, …)
          Storefront/   The shop
        Middleware/     Locale, admin authorisation, demo mode, security headers
        Requests/       Validation, one class per form
      Models/           Eloquent models; translatable fields use a `$translatable` array
      Services/         The thinking parts: Cart, Checkout, Orders, Payments, Returns,
                        Shipping, Settings, Notifications, Catalog, Seo
      Support/          Money, Localization — small value types and helpers
    database/
      migrations/       The schema, in order
      seeders/          FreshnestSeeder (the shop) + FreshnestHomepageSeeder (adds the
                        homepage content to an older FreshNest store); FurnivoSeeder too
      schema.sql        The same schema as plain SQL
      seed.sql          The demo data as plain SQL
    resources/
      views/admin/      The admin screens
      views/emails/     Email templates
      themes/freshnest/ The storefront design: views, css, js — self-contained
      themes/furnivo/   A second design on the same platform (STORE_THEME=furnivo)
    routes/
      web.php           Storefront routes, registered once per language
      admin.php         Everything behind /admin
      console.php       Scheduled work
    tests/Feature/      Almost everything; grouped by area

## Conventions that matter

**Money is never a float.** `App\Support\Money` holds minor units. `$money->minor` is an
integer; `price()` and `price_order()` format it. Adding two Money values in different
currencies throws.

**Translatable fields** are arrays keyed by locale: `['en' => 'Sofa', 'ar' => 'أريكة']`. Read
them with `$model->name` (current locale) or `getTranslation('name', 'ar', false)`.

**Settings** live in the database, through `settings()`. `settings()->get('general.logo')`,
`settings()->trans('general.store_name')` for translatable ones. Credentials (Stripe, SMTP)
are encrypted with `APP_KEY` — changing that key makes them unreadable.

**Languages.** Storefront routes are registered once per language: `products.show` in English,
`ar.products.show` in Arabic. Use `store_route()` in storefront views, never `route()`, or
links lose the language. Which languages exist is a setting, not config.

**Lazy loading is off.** Eloquent throws on an unloaded relation rather than quietly running
extra queries. Eager load, or call `loadMissing()` explicitly.

**Alpine and `x-show`.** Never put `x-show` on an element that has an inline `display` — the
directive overwrites it and the element comes back wrong. There is a test that fails the build
if you do (`BladeTemplatesTest`).

**Scripts and the CSP.** Alpine.js is bundled into our own Vite entries (`resources/js/alpine.js`,
taken from Livewire's ES build); never add `@livewireScripts`, and keep `livewire.inject_assets`
off — there are no Livewire components. No inline `on…=` handlers and no inline `<script>` without
`nonce="{{ Vite::cspNonce() }}"` (JSON data blocks are fine): `QaReportFixesTest` checks every
template. Values a script fills in (`x-text`) should also be printed by the server, so the page
reads right before the script runs.

**The theme is replaceable.** `resources/themes/<name>/views` is searched before
`resources/views`, so a theme overrides any storefront view. `STORE_THEME` picks the theme.
Nothing in `app/` should know a theme's name.

## Rich text

Fields written in the admin's HTML editor (`<x-admin.translatable-input rich>`, Trix) are listed
in the model's `$richText` and cleaned on save by `Models\Concerns\HasRichText` →
`App\Support\RichText::clean()` (allow-list: paragraphs, headings, bold, italic, lists, links,
quotes). Print them only with `{!! \App\Support\RichText::render($value) !!}`; use
`RichText::text()` for cards, meta tags and search.

## Running the tests

    php artisan test                          # all of them, 566
    php artisan test --filter Freshnest        # the FreshNest theme

They use an in-memory SQLite database and need no setup. Some seed the full demo shop, which
is slower but realistic.

Guard tests worth knowing about:
- `BladeTemplatesTest` — the `x-show` rule above, and a few other template mistakes
- `LaunchTest` — the pre-launch checklist behaves
- `DemoModeTest` — a real shop still saves when demo mode is off

## Things that will bite you

- `Mailable` already defines `$locale`, `locale()` and `text()`. A new mailable must not
  reuse those names.
- `Money::minor` is a property, not a method.
- The admin path is configurable (`ADMIN_PATH`); never hardcode `/admin` in a link.
- A folder in `public/` named after a route makes the web server serve the folder instead —
  that is why the landing page's images live in `public/images/landing/`.
- The storefront header uses a backdrop blur, which makes it the containing block for anything
  `position: fixed` inside it. Panels that must cover the page are teleported to the body.

## Adding something

A new admin screen: route in `routes/admin.php` inside the `auth:admin` group → controller in
`app/Http/Controllers/Admin/<Area>` → form request for validation → Blade view in
`resources/views/admin/<area>` → a feature test. Follow an existing area, `Catalog\BrandController`
is a short one to copy.

## Security

`SECURITY.md` lists every security control and the file that enforces it. Keep it true when
you change routes, controllers or migrations.

## FreshNest specifics

- Theme code that is not a view lives in `app/Themes/Freshnest`: `HomeData` (everything the
  homepage and cards show), `Delivery` (free-delivery figures), `Price` ("$4.20", no space),
  `DesignImages` (the photo import).
- The design's hover styles were `style-hover="…"` attributes; they are classes `fh-xxxxxx`
  in `resources/themes/freshnest/css/hover.css`. Add new hovers the same way (a class + rule).
- Homepage sections: `ThemeSettings::THEME_SECTIONS['freshnest']`, one Blade file each in
  `storefront/home/sections`. Their words come from banners whose placements are
  `ThemeSettings::BANNER_PLACEMENTS['freshnest']` (named in `lang/*/admin.php`,
  `banners.theme_placements.freshnest`).
- Listing pages show `ThemeSettings::PER_PAGE['freshnest']` (16) products.
- Checkout keeps the platform's "Emirate" field.

## Guided tours

Admin tours: steps in `App\Services\Tours\AdminTourCatalog` (route each step runs on, and a link
to a route or a `data-tour="…"` element it points at), progress in `AdminTours` (on the admin, or
in the session on a demo), texts in `lang/{en,ar}/tours.php`, popups in
`resources/js/tour/engine.js` + `resources/js/admin/tours.js`, styles in `resources/css/tour.css`.
The shopper tour is `StorefrontTour` + `resources/js/storefront/tour.js`; a theme marks its
elements with `data-tour="store-search|store-categories|store-options|store-add|store-checkout"`
and sets the `--tour-*` colours in its `theme.css`. When you move or rename a button a tour points
at, keep its `data-tour` attribute: `GuidedToursTest` and `FreshnestShopperTourTest` fail if a
step's target disappears from its page.

## Database baseline

`database/schema.sql` is the consolidated baseline: one file that creates every table, key and
index for a new install. The migrations in `database/migrations/` are kept (not squashed) because
existing shops upgrade through them; each is idempotent. New tables go in a new migration, then
regenerate `schema.sql` from a freshly migrated database.

## Where list filters live

Admin list pages validate their query string with `App\Http\Requests\Admin\Lists\*ListRequest`
(shared rules in `ListFilterRequest`) and build their query in a service that uses
`App\Services\Concerns\AppliesFilters` (search + sort): `ProductFilter`, `CustomerQueryBuilder`.
Dashboard data: `SetupStepsBuilder` (one-off set-up tasks) and `TodosBuilder` (work waiting).
