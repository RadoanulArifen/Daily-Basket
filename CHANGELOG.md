# Changelog — FreshNest

## 1.1.0 — 2026-10-23

Everything since 1.0.57 in one release (1.0.58 – 1.0.61 below), plus four new admin features.

### New
- **Test email** (Settings → Mail server & security): send a test to any address. If it fails,
  the reason is explained in plain words with the fix (wrong password, blocked port, Gmail app
  password, TLS…), and the technical message is one click away. Every store email now carries
  the logo inside the message, so mail apps that block outside pictures still show it.
- **HTML editor: TinyMCE 7** (GPL, bundled — nothing loads from a CDN) replaces the basic editor
  in every rich text field (product texts, categories, brands, pages, preferences): headings,
  lists, links, quotes, alignment, pictures from the media library, YouTube / Vimeo / .mp4
  videos, tables, and a **Source code** button to edit the HTML directly. The server cleaner
  (`App\Support\RichText`) keeps exactly what the editor allows; the storefront shows
  pictures, videos and tables in the FreshNest style, and the CSP allows the YouTube and Vimeo
  players only.
- **Backup & clean-up** (Settings, Owner only): "Back up now" (a gzipped SQL file that restores
  every row), download / delete, automatic daily backups with how many to keep, and a clean-up
  of abandoned guest carts, expired sessions, failed jobs, statistics older than a year, old
  draft orders and unused image folders — a backup is always made first. Also `store:backup`
  and `store:cleanup`. Downloads are refused on a public demo.
- **Visitor traffic** (Analytics → Traffic): world map by country, who is on the shop now
  (refreshes every 10 s), visitors, visits, pages per visit, top pages and countries, for today,
  7 / 30 / 90 days or 12 months. No IP address is stored (a one-way code per visitor); admins,
  previews, prefetches and bots are not counted as visits, and bots are tallied apart (search,
  AI, fake browsers, other). Countries come from the free DB-IP file
  (`php artisan store:geoip-update`, then monthly by itself) read by a built-in reader.
  New migration `create_traffic_tables`.

### Security
- `store:launch-check` warns when PHP is older than the PHP2Shell fix (CVE-2026-109618 /
  CVE-2026-109619): 8.2.35, 8.3.36, 8.4.27 or 8.5.12. **Requirement is now PHP 8.2.35+;
  PHP 8.4 recommended.**
- Build tools updated: Vite 7.3.7, concurrently 9.2.5 (`npm audit`: 0 vulnerabilities).

### Upgrading from 1.0.57
See `UPGRADE-1.1.0.md`. Tests: 566.

## 1.0.61 — 2026-10-15

Fixes from the QA report of 15 Oct 2026.

### Scripts can no longer be blocked by the server
- **Alpine.js is now bundled into the store's own scripts** (`resources/js/alpine.js`, from
  Livewire's ES build) instead of being fetched from `/livewire-…/livewire.js`. On the live site
  that URL answered 403, so Alpine never started — which is why "Continue to delivery", the
  pack-size and quantity selectors, and admin forms such as Fulfill items did nothing.
  `@livewireScripts` / `@livewireStyles` are removed and `config/livewire.php` turns Livewire's
  asset injection off (the store has no Livewire components).
- The last three inline `onsubmit=` / `onchange=` handlers (currency "Make base", Furnivo address
  delete and sort menu) are now Alpine, so nothing is refused by the Content-Security-Policy.
  A test checks every template for inline handlers and for inline scripts without the nonce.
- Admin flash messages are also printed by the server, so they show even if scripts fail.

### Checkout and cart
- **Checkout summary:** Delivery, fees, tax and Total are printed by the server (and then kept up
  to date by the script), so they are never blank. FreshNest and Furnivo.
- **Checkout validation:** "Continue" checks the step's required fields and shows a message under
  each empty or invalid one (red border in FreshNest), and focuses the first. Placing the order
  checks all steps. New shared component `checkoutForm` (`resources/js/storefront/checkout.js`).
- **Named savings:** the FreshNest cart, cart drawer and checkout list each deal by name
  ("Deal: Buy 1 Get 1 −$4.20") and a discount code as "Discount (CODE)", instead of an
  unexplained "Savings" line.

### Admin
- **Order timeline:** "Order placed" shows the order's own placed time, so it always matches the
  header; repeated event text ("Order placed — Order placed") is no longer shown.
- **Fulfill items:** each quantity box starts at what is left to send.

### Deployment
- **Prebuilt front-end assets** (`public/build`) are included in the package; `deploy.sh` rebuilds
  them when Node is installed and removes an old `public/vendor/livewire` copy.
- **`php artisan store:web-check [url]`** (new): loads the home page, a product, the cart and the
  admin sign-in over HTTP and reports scripts, styles or images that answer 403/404, a second
  Content-Security-Policy added by the server or hosting panel, and HTML served from a cache with a
  stale nonce. `deploy.sh` runs it after each update.
- `store:launch-check` now lists built files that the manifest names but the server is missing.
- Tests: 535.

## 1.0.60 — 2026-10-14

### Guided tours
- **Welcome screen** on each admin's first sign-in: pick one of four tours, "Not now", or
  "Don't show this again".
- **Four admin tours** that point at the real buttons on the real pages, carrying on across
  pages: Add your first product (6 steps), Handle a new order (7), Set up payments and delivery
  (8), Edit your storefront (7). The page dims, the element gets a ring and a popup explains it,
  with Back / Next / Skip and a progress bar; on phones the popup is a bottom sheet.
- **? menu** in the top bar: every tour with its status (done, step x of y, not started),
  Start / Continue / Restart, show the welcome screen again, reset all tours. A small bar offers
  "Continue" when the admin leaves the tour's page.
- Tours follow the admin's role (an Orders admin only sees the order tour), progress is saved on
  the admin account (`admins.tour_state`, new migration), and on a public demo in each visitor's
  session.
- **Shopper tour** on the storefront: a "Take the 1-minute tour" button on the home page and
  5 steps (search, categories, pack size, Add to cart, checkout) in the FreshNest style; progress
  in the shopper's browser. Switch it off in Online Store → Preferences.
- English and Arabic (right-to-left) texts; no outside library; works with the strict CSP.
- Tests: every step must point at an element that exists on its page; roles, demo mode, progress
  and the shopper tour are covered. 523 tests.

### Also
- `ALLOW_FRAMING_FROM` (in `.env.example`): other sites that may show the landing page and
  storefront in an iframe, e.g. a marketplace demo frame; the admin can never be framed.
- `phpunit.xml` pins the test time zone, so the suite passes whatever `STORE_TIMEZONE` is set.

## 1.0.59 — 2026-10-06

- Admin top bar: when a store logo is uploaded (Settings → Store details → Logo) only the logo is
  shown; the store name appears next to the letter badge only when there is no logo.
- `store:demo-history` writes the delivery-method name as translated JSON, like real orders
  (fixes the MariaDB/MySQL "CONSTRAINT orders.shipping_method_name" error).
- Restores the FreshNest country + state address fields and the default-country seeding that the
  1.0.58 package had accidentally reverted (checkout, address book, order page); a new test
  guards it. Tests: 512.

## 1.0.58 — 2026-10-06

- **Admin uses the whole screen on large displays** (like Shopify): every list page — products,
  orders, customers, inventory, categories, media, marketing, content, analytics, dashboard —
  is full width; detail and settings pages stay a reading width (a little wider on very large
  screens).
- **FreshNest error pages** (404, 403, 419, 429, 500, 503) in the theme's own design: header,
  footer, a friendly message, Back to home / Shop all products and a product search on 404.
  The admin keeps its plain error page.
- **`php artisan store:demo-history`** adds a year of believable history: 180 customers who
  signed up over the year, ~1,500 orders (busier at weekends and in November–December, growing
  over the year) with payments, fulfilments, deliveries, a few cancellations and refunds, the
  analytics funnel, newsletter sign-ups, more reviews and product dates. `--fresh` replaces it;
  it refuses a live shop unless DEMO_MODE is on. The hourly demo reset runs it automatically.
- Tests: 512.

## 1.0.57 — 2026-10-04

International addresses and a fuller demo.

### Countries
- **Settings → Shipping → Countries**: tick the countries customers can have orders delivered
  to (searchable list of every country in English and Arabic, "N chosen · Clear"); none ticked
  = every country. **Default country** for new address forms (else `STORE_COUNTRY`).
- Addresses have **Country + State / province / region + City** instead of Emirate — checkout,
  the account address book and admin draft orders (FreshNest and the default theme).
- New migration adds `country` / `state` (customer addresses) and `ship_country` /
  `ship_state` (orders) and fills existing rows; the emirate stays for UAE addresses (typing an
  emirate as the state stores its code), so Aramex rates and labels keep working; Aramex only
  quotes inside the UAE.
- Every address shows as "City, State, Country": order pages, address book, admin order /
  fulfil / customer pages, invoices, order e-mails, Google Maps link.
- Admin customer filter and the analytics breakdown are by country.
- Forms that still send `emirate` (older themes) are accepted and become country AE.

### Demo
- The demo account's role is `DEMO_ROLE` (default owner): visitors can open every screen,
  including Settings; demo mode still refuses every change, saved keys show as `••••••••` and
  other admins' emails are masked.

### Tests
- New `CountriesTest` and `DemoOwnerAccessTest`.

## 1.0.56 — 2026-10-02

Fixes from vibe96 audit run #130.

### Security
- `DEMO_EMAIL` has no default any more (like `DEMO_PASSWORD`); Stripe webhook secret is
  `env('STRIPE_WEBHOOK_SECRET')` with no fallback.
- Draft orders: role check (`orders`) also on the read endpoints — create form, drafts list,
  product, customer and shipping-rate look-ups.
- Owner-only checks use the `AdminRole::Owner` enum directly.

### Database
- The temporary cart used to place a draft order is removed with
  `WHERE id = ? AND token = ? AND user_id IS NULL`.
- Signing out a blocked customer only touches the sessions table when sessions are stored in
  the database.
- INDEXES.md maps the generic names used in reviews to the real indexed columns.

### Structure
- New `App\Services\Orders\DraftOrderService` (save, discard, place); the controller only
  validates and redirects.
- Duplicate `middleware()` declarations removed from the import/export and e-mail preview
  controllers (they inherit them from `AdminController`; each action still checks the role).
- `App\Support\PasswordRules` is the one place for new-password rules (customers, admins,
  `store:admin`); invited admins' throw-away password length is in `config/store.php`.
- `TokenGenerator` documents every kind of token and where each is made.

### Docs
- HowToSetup: exact minimum versions (PHP 8.2.0, Composer 2.7.0, Node.js 20.19.0, npm 10.0.0,
  MySQL 8.0 / MariaDB 10.11) and what `--seed` loads.

## 1.0.55 — 2026-10-02

Access control, database and structure (vibe96 audit after 1.0.54).

### Access control
- One helper, `AdminController::authorizeArea($area)`, for role checks inside actions (replaces
  the repeated `abort_unless(...)` lines). Now also on: customer list, page, note and block
  (orders area); inventory list, adjust and low-stock level (inventory area); product
  import/export (catalog area).

### Database
- New migration `add_search_indexes`: index on `users.name` (customer search); `sku` and
  `email` were already indexed. `database/INDEXES.md` lists the search indexes too.
- `schema.sql` regenerated.

### Structure
- `HandlesFileUploads` trait for image fields stored as paths (brand logos).
- CSV template example row lives in `ProductCsv::exampleRow()`.
- Config instead of numbers: inventory page size, customer order list size, dashboard report
  days (`config/store.php`).

### Docs
- HowToSetup: Node/npm check before `npm ci`, install-then-build order, the admin password is
  printed once by `db:seed` (also in the Docker steps), DB_PASSWORD before `docker compose up`.

## 1.0.54 — 2026-10-02

Code structure (vibe96 audit after 1.0.53).

- Form requests everywhere in the catalog: new `BrandRequest`; admin list filters validated by
  `ProductListRequest`, `CustomerListRequest`, `InventoryListRequest` on a shared
  `ListFilterRequest` base (common search and sort rules).
- Shared `App\Services\Concerns\AppliesFilters` (multi-column search through `Search::like()`,
  named sort orders) used by `ProductFilter` and `CustomerQueryBuilder`; customer search no longer
  has its own LIKE code.
- Dashboard "Things to do" moved to `App\Services\Dashboard\TodosBuilder`.
- Product status counts in one `GROUP BY` query instead of one query per status.
- CSV import: `ImportTokenManager::consume()` validates, runs the import and always deletes the
  file, in one call.
- Brand logos handled in one loop; CLAUDE.md documents the database baseline (`schema.sql`) and
  where list filters live.

## 1.0.53 — 2026-10-02

Code structure and dependency reporting (vibe96 audit after 1.0.52).

### Structure
- Admin product list: filters and sort moved to `App\Services\Catalog\ProductFilter`; the
  editor's start data to `ProductFormState`; stock rules are `ProductVariant::inStock()` /
  `lowStock()` scopes. `ProductController` is now about HTTP only.
- Brand and category deletes share `App\Services\Catalog\CatalogDeletion` (lock, re-point with
  an explicit WHERE, count, delete — one transaction).
- Customer list query: `App\Services\Insights\CustomerQueryBuilder`.
- Dashboard setup guide: `App\Services\Dashboard\SetupStepsBuilder`.
- CSV import file lifecycle: `App\Services\Catalog\ImportTokenManager` (store, validate, path,
  delete).
- Low-stock level from one place: `SettingsService::lowStockThreshold()`.
- Brand logo upload/removal in one helper method; `App\Support\Search` documents why it exists.

### Dependencies
- DEPENDENCIES.md: dated security audit (npm audit: 0 vulnerabilities), every production PHP
  package and every JavaScript package with its locked version, lock-file checksums.
- `composer security-audit` runs `composer audit` and `npm audit` together.

## 1.0.52 — 2026-10-02

Database access and structure (vibe96 audit run after 1.0.51).

### Database
- Brand and category deletes lock the affected rows first (`SELECT … FOR UPDATE`) and update
  exactly those rows with an explicit `WHERE` (`DB::table(...)->where(...)->whereIn('id', …)`),
  inside a transaction; category product links are removed with an explicit
  `DELETE FROM category_product WHERE category_id = ?`.
- New `database/INDEXES.md`: all 58 foreign keys and the index that covers each one (0 missing).
- Search, cart and draft-order code state at the top of the file how their queries are bound
  and scoped.

### Structure
- New shared helpers: `App\Support\TokenGenerator`, `PasswordGenerator`, `StorageHelper`
  (used by product import, the admin seeder and brand images).
- Hard-coded values moved to `config/store.php`: import disk and size limit, admin page sizes,
  draft-order line limit, default low-stock level.

### Dependencies
- DEPENDENCIES.md adds the SHA-256 checksums of `composer.lock` and `package-lock.json`.

## 1.0.51 — 2026-10-02

Dependency pinning (vibe96 audit run #108).

- `composer.json` pins every PHP dependency to the exact version in `composer.lock`
  (Laravel 12.69.2, Livewire 4.4.5, spatie/laravel-translatable 6.11.4, Tinker 2.11.1 …);
  `package.json` already pins every JavaScript dependency exactly.
- `composer.lock` and `package-lock.json` are committed in the archive root; `.gitattributes`
  states it and no longer excludes CHANGELOG.md from exports.
- DEPENDENCIES.md lists every pinned and locked version.

## 1.0.50 — 2026-09-29

No code changes. Re-submission of 1.0.49: the previous audit run listed every requirement as
present (10/10 must-have, 5/5 nice-to-have) but its summary line said "2/15 met".

## 1.0.49 — 2026-09-29

Access-control fixes from the latest vibe96 audit.

- Orders and returns: ownership is part of the database query (signed in →
  `where('user_id', auth id)`; visitors → guest orders only), not only a check afterwards.
- Addresses: loaded with `CustomerAddress::where('user_id', auth('web')->id())->findOrFail($id)`.
- Password reset: after success the token is removed with `Password::broker()->deleteToken()`
  as well as by email; only a Super Admin can send a password link to another owner.
- `AdminRole::SUPER_ADMIN` (= owner) used for the credentials check.
- Each security-relevant file starts with a one-paragraph `SECURITY:` note naming its guard.
- Tests extend the ownership checks (another customer → 403, visitor → sign in, unknown → 404).

## 1.0.48 — 2026-09-29

Access-control fixes from the latest vibe96 audit.

- Customer account routes: the group in `routes/storefront.php` names its guard explicitly —
  `Route::middleware('auth:web')` — and `routes/web.php` documents it.
- Role checks inside the mutating admin actions themselves (on top of the route middleware):
  products → `mayAccess('catalog')`, draft orders → `mayAccess('orders')`, admin accounts →
  `mayAccess('admins')`, credentials (Stripe, SMTP, Aramex) → store owner only.
- Addresses: explicit ownership check in `edit()` and make-default too; wishlist documents
  its per-customer scoping.
- SECURITY.md updated.

## 1.0.47 — 2026-09-29

Fixes from the vibe96 audit (run after 1.0.46).

### Secrets
- `.env.example`: `DB_PASSWORD` and `DEMO_PASSWORD` are empty (fail closed) — no password of
  any kind ships; Docker, `store:launch-check` and `store:demo-admin` refuse to run without one.

### Access control
- Explicit ownership lines in the actions themselves: order `show()`, return `create()`/`store()`,
  address `update()`/`destroy()`, cart line `update()`/`destroy()`, checkout `store()`.
- Sending an admin a password link checks `mayAccess('admins')` in the controller.
- New `tests/Feature/Admin/AdminRouteAreaMapTest.php`: fails if any admin route is not behind
  `auth:admin` and mapped to a role area.

### Dependencies and docs
- `package.json` pins every JavaScript dependency to the exact locked version.
- HowToSetup: pre-deployment checklist. Tests: 502.

## 1.0.46 — 2026-09-29

Fixes from the vibe96 audit (run #59).

### Security
- Import/export and e-mail preview controllers list `auth:admin` and `AuthorizeAdminArea` in
  their own `middleware()` (on top of the `routes/admin.php` group and `AdminController`).
- Store password page documented as the public pre-launch gate (never grants admin access);
  its throttle is declared in the controller too. `StorefrontPassword` middleware documented as
  storefront-only.
- Ownership rules sit at the top of the order, return and address controllers; addresses load
  through `$request->user('web')->addresses()->findOrFail($id)`. Wishlist and custom-order
  controllers document their scoping.
- Sign-in lockout after 5 failed attempts (5 minutes).
- A deactivated admin being signed out is written to the audit log.

### Database
- Every foreign-key column gets an explicit `$table->index('…')` line in its table's migration.

### Docs
- PHP extension check (`php -m`) and `engine-strict` note in HowToSetup and README; database
  password placeholders marked `<REPLACE_WITH_STRONG_PASSWORD>` with `openssl rand -base64 24`.
- SECURITY.md: reset-link expiry, lockout, store-password page.

## 1.0.45 — 2026-09-29

Checked against the vibe96 submission requirements and audit run #55.

### Security
- Orders and returns: an order that belongs to a customer account opens only for that
  customer, signed in (visitors are sent to sign in, other customers get 403); guest orders
  keep using their 48-character random token.
- Addresses: explicit `abort_unless($address->user_id === …, 403)` after the scoped lookup.
- New `SECURITY.md` at the archive root: every control (route guard, roles, ownership, password
  reset, throttling, parameterised queries, indexes, secrets) and the file that enforces it.

### Docs
- README and CLAUDE.md point to `SECURITY.md`. Version strings updated.

## 1.0.4 — 2026-09-29

Fixes from the vibe96 audit (run #55).

### Security
- `routes/admin.php` states the route-level guard at the top: every admin page is inside
  `Route::middleware(['auth:admin', AuthorizeAdminArea::class])->group(...)` (it always was;
  `AdminController` declares the same two middleware as a second layer).
- `AuthorizeAdminArea`: explicit fail-closed check — `if ($area === null || ! $admin->mayAccess($area)) abort(403)`.
- Password reset: the token is deleted after the new password is saved, inside one database
  transaction (a failed save keeps the link valid for a retry). Admin reset links expire after
  15 minutes; a deactivated admin gets the same message as an invalid link (no account probing).
- Sign-in throttling: 10 failed attempts per email + IP, then a 5-minute lockout.
- Raw SQL: the currency rebase now passes the rate as a bound parameter; search ordering already
  used a binding (documented).
- Brand and category deletes: updates scoped to that brand/category, counted and done in a
  transaction.
- `.env.example`: placeholder values that cannot be used as they are — `DB_PASSWORD` and
  `DEMO_PASSWORD` placeholders are refused by `store:launch-check` and `store:demo-admin`.

### Database
- Foreign-key indexes are now defined in the original table migrations; the three separate
  "add index" migrations are removed. `schema.sql` / `seed.sql` regenerated (no admin account,
  no logo row — run `store:admin` and the homepage seeder after importing).

### Dependencies and docs
- `spatie/laravel-translatable` pinned to `~6.11.0` (lock: 6.11.4). New `DEPENDENCIES.md` lists
  the exact locked version of every direct dependency.
- README contains the full install steps (prerequisites, database, `.env`, seeding, one-time
  admin password, `--force`, separate customer/admin accounts). Stripe key instructions in
  `.env.example`; clearer Docker error when `DB_PASSWORD` is missing.

## 1.0.3 — 2026-09-29

Fixes from the vibe96 audit (run #54).

### Security
- Every action of the product import/export and e-mail preview controllers checks for an
  active admin itself (on top of `AdminController` and the `auth:admin` route group). The
  e-mail preview uses a fresh random token per preview instead of a fixed one.
- One shared, atomic password-reset routine for customers and admins
  (`App\Http\Controllers\Concerns\ResetsPasswordsSafely`): inside a database transaction the
  token is deleted, then the password saved; nothing half-happens and a link works once.
- Customer addresses are always loaded through `ownedAddress()` (scoped to the signed-in
  customer); orders and returns refuse another customer's order even with its link.
- `store:demo-admin` refuses a demo password shorter than 8 characters or without a letter
  and a number.
- New tests: every admin route is behind `auth:admin` and mapped to a role area (or open to all
  admins); a signed-in customer cannot open `/admin`; another customer's order link is refused.

### Database
- Every foreign-key column now has its own index (new migration goes through all tables:
  stock alerts, custom order requests, order drafts, funnel tables and the rest).

### Docs
- `.env.example`: clearer notes for `DB_PASSWORD`, `ADMIN_PASSWORD`, `DEMO_*`.
- HowToSetup: check Node/npm versions before `npm ci`; the one-time admin password; what
  `--force` does; Docker's required `DB_PASSWORD`; what `database/database.sqlite` is for.
- Tests: 499.

## 1.0.1 — 2026-09-29

Fixes from the vibe96 audit (run #52).

### Security
- Admin import/export and e-mail preview declare `auth:admin` in the controller itself and
  check the signed-in admin again inside each action (they were already behind the
  `auth:admin` route group and `AdminController`).
- Password-reset links are single use: the token is deleted before the new password is saved
  (customers and admins). A deactivated admin can no longer request or use a reset link.
- Sending an admin a password link requires access to the admins area.
- Stock-alert "cancel" links: token format check and a rate limit (20 per minute).
- Cart cookie: documented as encrypted and signed; the token must be a long random value.
- A deactivated admin who is signed out is never redirected to the page they tried to open.
- The seeded admin password is 16 characters with symbols.

### Database
- Explicit indexes on foreign-key columns (cart and order lines, fulfilment and refund lines,
  variants, audit log, reviews); added only where no index exists yet.
- `database/seed.sql` no longer contains an admin account: create yours with
  `php artisan store:admin` after importing.

### Build and docs
- `laravel/tinker` is now a development-only package; `package.json` states the Node/npm
  versions (`engines`).
- `.env.example`: FreshNest database names, empty DB password with guidance, clearer
  `ADMIN_PASSWORD` / `DEMO_*` notes.
- HowToSetup: prerequisites table, database creation, how the two sign-in guards work,
  re-runnable migrations. README no longer shows a demo password.
- Tests: 496 (new `tests/Feature/Security/AuditHardeningTest.php`).

## 1.0.0 — 2026-09-28

First release: an organic grocery store on the same platform as Furnivo (Laravel 12).

### Storefront (the FreshNest design, pixel for pixel)
- Header with top bar, category search, account menu, wishlist and cart counts, All Categories,
  menus from Content → Menus and a "Super Discount" menu; footer with delivery strip, link
  columns that fold on phones, contact details, social links and payment marks; phone
  bottom bar; cart drawer with free-delivery progress; toasts
- Homepage: hero, Shop by Category, weekend offer with countdown, side offer, Today's Fresh
  Picks with tabs, Offers & Bundles, Deal of the Day with countdown, three offer cards,
  Top Rated / Best Sellers / New Arrivals, Daily Bakery & Dairy, newsletter, promise cards
- Shop, category and search listings with departments, price, "On offer", "In stock", sort
  (incl. Top rated) and 16 per page
- Product page: pack sizes, quantity and live total, coupon box, "Offers on this item",
  Description with Storage & use, Nutrition, Reviews with review form, You may also like
- Cart page, three-step checkout (card via Stripe, cash on delivery), order page with the
  Track Order timeline
- Account: profile figures, orders with reorder, wishlist, saved addresses; sign-in and
  register in the design's panel
- Content pages in the design's layouts: Contact (cards, map, form), FAQs, Delivery Info,
  Returns & Refunds, Our Growers (with the growers), Sustainability, Careers, legal pages
- Landing page at `/landing/`

### Platform (shared with Furnivo)
- Banners: a theme can add its own "Where" places (`ThemeSettings::BANNER_PLACEMENTS`)
- Listings: "Top rated" sort; page size per theme
- `store:import-design-images` imports the active theme's photos
- `/landing/` serves the active theme's own landing page when it has one
- `DatabaseSeeder` picks the seeder of the active theme

### Data
- 8 categories, 4 growers (brands), 16 products with three pack sizes, reviews, deals,
  discount codes FRESH5 and FIRST10, menus, pages and homepage banners
