# How to set FreshNest up

Two routes: **A — a VPS or shared host**, and **B — Docker**. Either gives you a working shop
with demo data in about fifteen minutes.

**You need (check before you start):**

| | Version | Check with |
|---|---|---|
| PHP | 8.2.35 or higher (8.4 recommended — older builds lack the PHP2Shell fix), with mbstring, pdo_mysql, gd or imagick, zip, intl, curl, fileinfo, openssl, tokenizer, xml | `php -v` · `php -m` |
| Database | MySQL 8.0+ or MariaDB 10.11+ | `mysql --version` |
| Composer | 2.7.0 or higher | `composer -V` |
| Node.js / npm | Node.js 20.19.0 or higher (or 22 LTS), npm 10.0.0 or higher (also in `package.json` → `engines`) | `node -v` · `npm -v` |

Verify the PHP extensions before `composer install`:

    php -m | grep -E "mbstring|pdo_mysql|gd|zip|intl|curl"    # all six must appear

The Node/npm versions in `package.json` → `engines` are advisory; to make npm refuse an older
Node, run `npm config set engine-strict true` first.

Create the database first (utf8mb4):

    CREATE DATABASE freshnest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER 'freshnest'@'localhost' IDENTIFIED BY '<REPLACE_WITH_STRONG_PASSWORD>';   -- openssl rand -base64 24
    GRANT ALL PRIVILEGES ON freshnest.* TO 'freshnest'@'localhost';

---

## A. VPS or shared hosting — seven steps

### 1. Upload the files

Unzip them on the server. **The web server's document root must point at the `public` folder**,
not the project root. On cPanel: set the domain's document root to `/home/USER/yoursite/public`.

### 2. Install PHP packages

    composer install --no-dev --optimize-autoloader

`composer.lock` is included, so this installs exactly the versions the shop was tested with.

### 3. Install and build the front-end — do not skip this

    node --version    # must be 20.19.0 or higher (package.json → engines)
    npm --version     # must be 10.0.0 or higher
    npm ci
    npm run build

Without `npm run build` there is no CSS or JavaScript and **the site will look broken**.

### 4. Create the environment file

    cp .env.example .env
    php artisan key:generate

Then edit `.env`:

    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://yourdomain.com

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1          # Docker users: use "db", the service name
    DB_PORT=3306
    DB_DATABASE=your_database
    DB_USERNAME=your_user
    DB_PASSWORD=your_password

    QUEUE_CONNECTION=sync
    SESSION_SECURE_COOKIE=true

    ADMIN_NAME="Your Name"
    ADMIN_EMAIL=you@yourdomain.com
    ADMIN_PASSWORD=             # see step 5

**About `ADMIN_PASSWORD`:** set a strong one (8+ characters with a letter, a number and a
symbol) before seeding. If you leave it empty, seeding prints a random password **once** —
copy it immediately, it is not stored anywhere.

**About `DEMO_MODE`:** leave it `false` for a real shop. It exists for a public demo where
anyone may sign into the admin: every screen opens, nothing saves. `DEMO_EMAIL` and
`DEMO_PASSWORD` are only read when it is on.

### 5. Create the database

Make an empty database in utf8mb4, then **both** of these:

    php artisan migrate --force     # creates the tables
    php artisan db:seed --force     # fills the shop AND creates your admin account
    # IMPORTANT: copy the admin password printed in the terminal now — it is shown only once
    # (unless you set ADMIN_PASSWORD in .env). Lost it? php artisan store:admin

> **Demo data or a real shop?** `--seed` (and `db:seed`) loads the FreshNest demo: products,
> categories, banners, pages and a demo customer. For a real shop you can still seed (then edit
> or delete the demo products in the admin), or run `php artisan migrate --force` only and
> create your admin with `php artisan store:admin`, then add your own products.


**Seeding is not optional on a first install.** Without it you have no categories, no content
pages and no admin account to sign in with.

*Prefer plain SQL?* Some hosts only offer phpMyAdmin:

    mysql -u USER -p YOUR_DATABASE < database/schema.sql    # tables
    mysql -u USER -p YOUR_DATABASE < database/seed.sql      # the demo shop
    php artisan db:seed --class=FreshnestHomepageSeeder --force   # the logo file and product-page extras

Use `db:seed` for a fresh install (it reads `ADMIN_*` from your `.env`); use the SQL files to
reproduce the exact demo state. The SQL files contain **no admin account**: right after the
import run `php artisan store:admin` to create yours (it asks for the email and a strong
password), then `php artisan db:seed --class=FreshnestHomepageSeeder --force`.

### 6. Storage, images and permissions

    php artisan storage:link                  # without this, uploaded images 404
    php artisan store:import-design-images    # downloads the demo photographs
    chmod -R 775 storage bootstrap/cache

On cPanel the owner is usually your own user: `chown -R $USER:$USER storage bootstrap/cache`.

### 7. Cache, cron, and the pre-launch check

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

Visitor map (Analytics → Traffic): download the free IP-to-country file once (it then
updates itself every month):

    php artisan store:geoip-update

One cron line (abandoned-bag reminders, unpaid order clean-up, expired token clean-up,
automatic backups and clean-up, the monthly IP-file update):

    * * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1

Then:

    php artisan store:launch-check

It reports anything not ready: debug mode, mail settings, missing HTTPS, demo data left behind,
an unpatched PHP version. After the site is live, `php artisan store:web-check` loads it like a
browser and lists anything that does not load.

---

## B. Docker

    cp .env.example .env          # required — compose reads this file
    # Set DB_PASSWORD in .env first (generate one with: openssl rand -base64 24) — required:
    # docker-compose.yml uses
    # ${DB_PASSWORD:?…}, so "docker compose up" stops with an error until it is set
    docker compose up -d --build
    docker compose exec app php artisan key:generate
    docker compose exec app php artisan migrate --force
    docker compose exec app php artisan db:seed --force   # copy the admin password it prints — shown only once
    docker compose exec app php artisan storage:link

Shop: **http://localhost:8080** · Admin: **http://localhost:8080/admin**

In `.env`, Docker needs `DB_HOST=db` (the compose service name); a local install without
Docker uses `127.0.0.1`. The image builds the front-end itself, so there is nothing to compile
first.

---

## First sign-in

`/admin`, with the `ADMIN_EMAIL` and `ADMIN_PASSWORD` from step 4 (or the demo pair if you
loaded `seed.sql`). Change the password under Settings → Your account, then delete the demo
customer under Customers.

---

## Production notes

**Email.** `QUEUE_CONNECTION=sync` sends mail as part of the request and needs nothing else.
Only use `database` if you actually run a worker:

    php artisan queue:work

as a service — `deploy/vibe-queue.service` is a systemd example. With `database` and no
worker, **no email ever leaves**.

**HTTPS.** Set `SESSION_SECURE_COOKIE=true` and serve over TLS. Some browser features
(clipboard, the share sheet, Apple Pay) are switched off without it.

---

## Making it yours

| What | Where |
| --- | --- |
| Store name, email, phone, address, tax number | Settings → Store details |
| Logos (dark for light backgrounds, white for dark) | Settings → Store details → Logo |
| Languages — English always on, Arabic optional | Settings → Store details → Languages |
| Colours, font, homepage sections, announcement bar | Online Store → Customize |
| Trust badges, footer headings, storefront wording | Online Store → Customize |
| Footer menus | Content → Menus |
| Content pages | Content → Pages |
| Delivery rates and zones | Settings → Shipping and delivery |
| Payments (Stripe, cash on delivery) | Settings → Payments |
| Email templates, with previews | Settings → Notifications |
| SMTP and reCAPTCHA | Settings → Mail server & security |

The design lives in `resources/themes/freshnest`. Copy that folder, point `STORE_THEME` at the
copy, and you can change the design without touching the application.

---

## Updating from an earlier version

Upload the new files over the old ones (keep your `.env` and `storage/`), then:

```bash
composer install --no-dev --optimize-autoloader
node --version   # Node.js 20.19+ required (npm 10+)
npm ci           # install first …
npm run build    # … then build
php artisan migrate --force
php artisan optimize
```

`migrate` adds new tables and columns and converts existing descriptions and page text to the
HTML editor's format; nothing is lost. Version notes are in `UPGRADE-<version>.md`.

### Page and product texts

- Product page: Products → a product → Short description (under the price), Description (a
  heading starting "Storage" and the list after it become the green "Storage & use" box),
  Dimensions & materials (FreshNest shows it as the Nutrition table: one line per item,
  e.g. `Energy — 160 kcal`), Reassurances ("Title|Text", one per line).
- Used when a product has none of its own: Online Store → Preferences → Product page defaults.
- Pages: Content → Pages — small label, headline, introduction, text, footnote and (About)
  three pictures. On FreshNest the text's first list of **bold figure** + label becomes the
  figure tiles and each heading becomes a row (FAQs: an opening question).
- Homepage: every section's words and pictures are banners — Content → Banners, "Where" =
  one of the "Home: …" places; order and on/off in Online Store → Customize.
- Landing page: put `index.html` and its files in `resources/landing/<theme>/` (FreshNest's is included); it is served at `/landing/`.

## If something is wrong

| Symptom | Cause |
| --- | --- |
| Blank page or 500 | `storage` not writable, or `APP_KEY` not generated |
| No styles at all | `npm run build` not run, or `public/build` not uploaded |
| Images 404 | `php artisan storage:link` not run |
| Photos missing / placeholder drawings | `php artisan store:import-design-images` not run, or the server has no internet access |
| No email arrives | `QUEUE_CONNECTION=database` with no worker — set it to `sync` |
| Empty shop, cannot sign in | `php artisan db:seed --force` not run |
| `.env` changes ignored | run `php artisan config:cache` again |


---

## How sign-in works (two separate guards)

The shop has two completely separate sign-in systems:

- **`web`** — customers (storefront account, checkout, orders). Table `users`, password
  resets in `password_reset_tokens`.
- **`admin`** — store staff (`/admin`). Table `admins`, password resets in
  `admin_password_reset_tokens`, roles (Owner, Manager, …) checked on every admin page by
  `AuthorizeAdminArea` against `AdminRole::AREAS`. A deactivated admin is signed out on
  the next request and can neither sign in nor reset a password.

Sessions of the two never mix: signing in as a customer gives no access to `/admin`. Every
admin controller extends `AdminController`, which declares `auth:admin` itself, on top of the
`auth:admin` route group in `routes/admin.php`. Customer data is always looked up for the
signed-in customer (addresses, account orders) or by a 48-character random order token.
Password-reset links are single use and expire (customers 60 min, admins 30 min).

## Migrations

`php artisan migrate --force` is safe to run on every deployment: it only adds what is
missing and never touches existing data. If a migration stops half-way (for example a lost
connection), fix the cause and run the same command again — the migrations check what is
already there before changing anything.

## Admin password and the --force flag

- **ADMIN_PASSWORD empty:** `php artisan db:seed` generates a random 16-character password
  (letters, numbers and symbols) and prints it **once** in the terminal. Copy it immediately
  and store it safely — it cannot be shown again; reset it later with `php artisan store:admin`.
- **Before going live:** change the admin password and delete the demo customer
  `customer@example.com` (Admin → Customers).
- **`--force`** on `migrate` and `db:seed` skips Laravel's "are you sure?" question, which is
  asked when `APP_ENV=production`. Use it only when `.env` points at the right database.
- **`database/database.sqlite`:** Composer creates this empty file for the automated tests
  (in-memory SQLite). The shop itself uses MySQL/MariaDB as set in `.env`.

## Pre-deployment checklist

Before the shop takes real orders, check each line (all are also tested by
`php artisan store:launch-check`):

- [ ] `APP_KEY` set (`php artisan key:generate`), `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `DB_PASSWORD` set to a strong value (`openssl rand -base64 24`) — never empty on a server
- [ ] Admin password changed from the one printed by `db:seed` (or set your own with
      `php artisan store:admin`); demo customer `customer@example.com` deleted
- [ ] `DEMO_MODE=false` (or, for a public demo, a strong `DEMO_PASSWORD`)
- [ ] HTTPS on (`APP_URL=https://…`), Stripe live keys and webhook secret entered in
      Admin → Settings → Credentials
- [ ] Cron entry for `php artisan schedule:run` added

## A year of demo data (demo and test copies)

`php artisan store:demo-history` adds a year of customers, orders, payments, deliveries,
analytics and reviews so the dashboard and reports look like a running shop. Run it again with
`--fresh` to replace it. It refuses a live shop unless `DEMO_MODE=true`; the hourly demo reset
runs it automatically.
