# Access control

## The admin

Every admin route sits behind `auth:admin` and `AuthorizeAdminArea`, which checks the signed-in
admin has permission for that area. `RouteProtectionTest` walks the whole route table on every
test run and fails the build if any admin route that writes is reachable without signing in.

Roles and permissions are managed in Settings → Users and permissions. An admin without the
"orders" permission cannot open or change orders, not merely cannot see the menu item.

Three admin routes are deliberately open, and none of them writes shop data:

| Route | Why |
| --- | --- |
| `POST /admin/login` | Signing in |
| `POST /admin/forgot-password`, `POST /admin/reset-password` | Password recovery, rate limited |
| `POST /admin/locale` | The language switch on the login screen. Writes to the session; it only touches the admin's own record when one is already signed in |

## The storefront

Some storefront routes write without a sign-in, because the shop supports guest checkout —
this is deliberate, and each is protected in its own way:

| Route | Protection |
| --- | --- |
| `POST /cart/items`, `PATCH`/`DELETE` on cart lines | CSRF token; the cart is bound to a signed cookie, so one visitor cannot touch another's |
| `POST /checkout` | CSRF, rate limited, and the order is created server-side from the cart — prices and totals are never read from the request |
| `POST /orders/{token}/pay`, `/return` | The token is a long random string on the order; rate limited |
| `POST /products/{slug}/reviews` | Requires a signed-in customer, and a verified purchase before it is published |
| `POST /pages/{slug}/contact`, `/custom-order`, `/newsletter` | CSRF, rate limited, reCAPTCHA v3 when keys are set |
| `POST /wishlist/{product}` | Session-bound; no customer data is written |
| Account routes (`/account/...`) | Require a signed-in customer, and every record is scoped to that customer |

## Data access

- Queries go through Eloquent and the query builder, so values are always bound, never
  concatenated into SQL.
- Deletes are scoped to the owning record — an address can only be removed by the customer it
  belongs to; an order line only through its order.
- Admin credentials for Stripe, SMTP and reCAPTCHA are encrypted at rest with `APP_KEY` and
  are shown masked in the admin.
- Sessions are encrypted; `SESSION_SECURE_COOKIE=true` is expected in production.
- Admin responses carry a strict content-security policy with per-request nonces.

## Searching

Admin screens search case-insensitively, which needs `lower(column)` in the SQL. Every such
query goes through `App\Support\Search`, which is the only place in the codebase that writes
a column name into SQL:

- the **term the visitor typed is always a bound parameter**, never concatenated;
- the **column name is checked against a strict pattern** (`^[a-z_][a-z0-9_]*(->[a-z0-9_]+)*$`)
  and rejected otherwise, so it can only ever be a real column or JSON path.

Outside `App\Support\Search`, the few remaining raw fragments are fixed expressions with no
visitor input: aggregates (`count(*)`, `sum(quantity)`, `min(price)`), the funnel counters
(`count + 1`), a relevance sort whose search term is a bound parameter, and the base-currency
switch, whose column names come from a constant list and whose rate is a validated number.

## Editor content

Product, category, brand and page texts come from the admin's HTML editor. They are cleaned
when saved and again when shown by `App\Support\RichText`, an allow-list sanitiser built on
`DOMDocument`: only formatting tags survive (paragraphs, headings, bold, italic, lists, links,
quotes). Scripts, styles, iframes, images, event-handler attributes and `javascript:` links are
removed; external links get `rel="noopener"`.

## Deleting

- Cart rows are loaded by their cookie token **and** `user_id is null` before removal; a
  signed-in customer's bag is matched on `user_id`.
- Wishlist rows are removed by `user_id` + `product_id`, both from the signed-in customer.
- Unpaid orders are **cancelled, never deleted**, and only when they have no captured payment
  and no shipment against them.
- Customer addresses are looked up scoped to the signed-in customer, then checked again
  before any write.

## Tokens and passwords

- Guest order links carry a 48-character random token, and a token shorter than 40 characters
  is refused outright. If the order belongs to a customer account, a *different* signed-in
  customer is refused even with the token.
- Password reset uses Laravel's broker, which stores a hash of the token, not the token.
  Expired rows are cleared hourly by `auth:clear-resets` on the scheduler.
- Passwords must be at least 8 characters with a letter, a number and a symbol in production,
  and are checked against known-breached lists. The command-line admin tool uses the same
  policy as the web form.
- Sign-in attempts are rate limited per email and IP, with the email **hashed** into the
  throttle key so two addresses can never share a limit.

## Schema notes

Foreign keys carry explicit cascade rules rather than being left to the database default:

| Relationship | On delete |
| --- | --- |
| `categories.parent_id` → `categories.id` | set null — deleting a parent lifts its children to the top level rather than destroying them |
| `orders.user_id` → `users.id` | set null — an order survives the customer's account being closed, because the books must still balance |
| `cart_items.cart_id`, `order_items.order_id`, `return_items.return_request_id` | cascade — a line has no meaning without its parent |
| `products.brand_id` → `brands.id` | set null — a brand can be removed without taking its products with it; the products simply have no brand |

Every foreign key column is indexed. The ones that were missing their own index are added in
`2026_10_10_000100_add_indexes_to_foreign_keys.php`, which checks before adding so it is safe
to re-run.
