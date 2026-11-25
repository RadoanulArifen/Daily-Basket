# FreshNest — security controls (where each one is enforced)

A map for reviewers and buyers' developers. Every line names the file that enforces the control;
`tests/Feature/Security/` proves the most important ones with real requests.

## Admin panel (authentication + roles)
| Control | Where |
|---|---|
| Every admin page requires a signed-in admin **at route level** | `routes/admin.php` → `Route::middleware(['auth:admin', AuthorizeAdminArea::class])->group(...)` (only sign-in / password-reset pages are outside it) |
| Second layer on every admin controller | `app/Http/Controllers/Admin/AdminController.php` (`HasMiddleware`: `auth:admin`, `AuthorizeAdminArea`) |
| Role check per area, fail closed | `app/Http/Middleware/AuthorizeAdminArea.php` → `if ($area === null \|\| ! $admin->mayAccess($area)) abort(403)`; areas in `app/Enums/AdminRole.php` |
| Deactivated admins are signed out on their next request and the event is written to the audit log | `app/Http/Middleware/AuthorizeAdminArea.php` |
| Deactivated admins cannot sign in | `app/Http/Requests/Admin/Auth/LoginRequest.php` → `extraCredentials()` returns `['is_active' => true]` |
| Every admin route is mapped to a role area and guarded (tests fail if a new route is not) | `tests/Feature/Admin/AdminRouteAreaMapTest.php`, `tests/Feature/Security/AccessMapTest.php` |
| Admin actions also check the role in the action itself, through one helper `AdminController::authorizeArea($area)` | `ProductController` store/update/destroy/bulk → `mayAccess('catalog')`; `DraftOrderController` → `mayAccess('orders')`; customers (list, page, note, block) → `orders`; inventory (list, adjust, threshold) → `inventory`; product import/export → `catalog`; `AdminUserController` → `mayAccess('admins')`; `CredentialsController` → store owner only (`AdminRole::Owner`) |
| Import/export and e-mail preview inherit `auth:admin` + `AuthorizeAdminArea` from `AdminController` and check again per action | `Admin/Catalog/ProductTransferController.php`, `Admin/Settings/EmailPreviewController.php` |
| Store password page is public by design (pre-launch gate for shoppers), throttled, never grants admin access | `app/Http/Controllers/StorePasswordController.php`, `app/Http/Middleware/StorefrontPassword.php` |

## Customers' own records (ownership)

Every `/account/*` route is inside `Route::middleware('auth:web')->group(...)` in `routes/storefront.php`
(loaded once per language from `routes/web.php`).

| Record | Where |
|---|---|
| Orders | `Storefront/OrderController::find()` — the query itself is scoped: signed in → `where('user_id', auth id)` (or a guest order by token); visitor → guest orders only (`whereNull('user_id')`) by 48-char random token; another customer's order → 403 |
| Returns | `Storefront/ReturnController::order()` — same scoped query; there is no return-by-id page |
| Addresses | `Storefront/Account/AddressController::ownedAddress()` — `$request->user('web')->addresses()->findOrFail($id)` + `abort_unless` |
| Wishlist | `app/Services/Wishlist/WishlistService.php` — `where('user_id', $user->id)`; guests: own session only |
| Cart and checkout | `CartController::update()/destroy()` — `abort_unless($item->cart_id === $cart->current()?->id, 404)`; `CheckoutController::store()` — cart must be the visitor's own; `app/Services/Cart/CartService.php` — signed in: `where('user_id', …)`; guests: encrypted, signed cookie token + `whereNull('user_id')` |
| Stock alerts | cancel link needs the alert's long random token; route throttled |
| Custom order requests | guests may send them by design (enquiry form); throttled; nothing is shown back |

## Passwords and sign-in
| Control | Where |
|---|---|
| Throttling: 5 failures per email + IP, then 5-minute lockout | `app/Http/Requests/Concerns/AuthenticatesWithThrottle.php` |
| Reset links single use, atomic: password saved, then `Password::broker()->deleteToken($account)` and all token rows for the email deleted, in one transaction | `app/Http/Controllers/Concerns/ResetsPasswordsSafely.php` |
| Reset links expire: customers after 60 minutes, admins after 15 minutes | `config/auth.php` (`passwords.users.expire`, `passwords.admins.expire`) |
| No account probing (same answers for unknown / inactive) | `Admin/Auth/ForgotPasswordController.php`, `Admin/Auth/ResetPasswordController.php` |
| Seeded admin password: random 16 chars, printed once, stored hashed | `database/seeders/AdminSeeder.php` (refuses an empty password when `APP_ENV=production`) |

## Guided tours

`POST /admin/tours` (`admin.tours.update`) is open to every signed-in admin (AdminRole::OPEN_TO_ALL)
but only changes that admin's own tour progress; starting a tour checks the admin's role may open
the tour's pages (403 otherwise). It is allowed in demo mode, where progress is kept in the
visitor's session. The popups build their content with DOM methods and `textContent` (no HTML from
data) and read their data from a `<script type="application/json">` block, so the CSP is unchanged.
The shopper tour stores nothing on the server.

## Database
| Control | Where |
|---|---|
| Queries parameterised (Eloquent / bindings); no user input concatenated into SQL | e.g. `app/Services/Catalog/ProductSearch.php` (`orderByRaw(..., [$binding])`), `app/Services/Currency/BaseCurrencySwitch.php` (`DB::update(..., [$rate])`) |
| Scoped updates/deletes, counted, in transactions | `Admin/Catalog/BrandController::destroy()`, `Admin/Catalog/CategoryController::destroy()` |
| Every foreign key has an index, defined in the table's own migration | `database/migrations/*` (`foreignId(...)->index()`), `database/schema.sql` |

## Passwords at rest

`User` and `Admin` cast `'password' => 'hashed'` (bcrypt) and hide it from arrays.

## Secrets
- No real credentials in the archive; `.env.example` leaves every secret empty (fails closed).
- An empty or weak `DB_PASSWORD` / `DEMO_PASSWORD` is refused by `php artisan store:launch-check` and
  `php artisan store:demo-admin`; Docker refuses to start without `DB_PASSWORD`.
- Stripe / SMTP / Aramex keys entered in the admin are stored encrypted.
- Strict Content-Security-Policy with nonces: `app/Http/Middleware/SecurityHeaders.php`. All
  scripts (Alpine included) are served from `/build`; no inline handlers anywhere (tested).
  `php artisan store:web-check` confirms on the live server that only one policy arrives.

## Public demo (DEMO_MODE=true)

The demo account (`php artisan store:demo-admin`) has the role in `DEMO_ROLE` (default owner),
so visitors can open every screen. `App\Http\Middleware\DemoMode` refuses every change (only
sign-in, sign-out, language and preview are allowed), saved keys are shown as `••••••••` with no
characters revealed, and other admins' email addresses are masked.
