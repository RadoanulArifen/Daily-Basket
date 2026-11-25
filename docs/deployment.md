# Deploying to a Linux server

A step-by-step guide for **Ubuntu 24.04 LTS** with **Nginx, PHP 8.3, MySQL 8 and Let's Encrypt**.
Commands are run as a user with `sudo`. Replace `example.ae` with your domain.

> **PHP 8.3 on the server, 8.2 on your PC?** That's fine. `composer.json` pins packages to ones that work
> on both, and the whole test suite passes on PHP 8.3. Ubuntu 24.04 ships 8.3, which gets security
> updates until the end of 2027 (8.2's end on 31 December 2026).

## 1. Server basics

A server with 2 GB RAM and 2 CPUs is plenty to start. Point your domain's **A record** (and `www`) at the server's IP address first, because HTTPS needs it.

```bash
sudo apt update && sudo apt upgrade -y
sudo timedatectl set-timezone Asia/Dubai        # nightly jobs run at UAE times
sudo apt install -y nginx mysql-server unzip git curl \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-intl php8.3-gd php8.3-zip php8.3-bcmath php8.3-opcache

# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer

# Node.js 20 LTS (to build the CSS/JS)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt install -y nodejs

# Firewall: SSH + web only
sudo ufw allow OpenSSH && sudo ufw allow 'Nginx Full' && sudo ufw enable
```

Check that GD has WebP support (needed for image uploads): `php -r 'var_dump(function_exists("imagewebp"));'` should print `bool(true)`.

**PHP settings** for uploads and speed: create `/etc/php/8.3/fpm/conf.d/99-vibe.ini`:

```ini
upload_max_filesize = 12M
post_max_size = 14M
memory_limit = 256M
expose_php = Off
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 1
```

Then run `sudo systemctl restart php8.3-fpm`.

## 2. Database

```bash
sudo mysql_secure_installation          # answer yes to the security questions
sudo mysql
```

```sql
CREATE DATABASE vibe_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'vibe'@'localhost' IDENTIFIED BY 'a-long-random-password';
GRANT ALL PRIVILEGES ON vibe_ecommerce.* TO 'vibe'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Upload the code

Since the project isn't in Git yet, upload the latest zip. From your Windows PC in PowerShell (or use WinSCP/FileZilla):

```powershell
scp vibe-ecommerce-phase-7.zip youruser@your-server-ip:/tmp/
```

On the server:

```bash
sudo mkdir -p /var/www && cd /var/www
sudo unzip /tmp/vibe-ecommerce-phase-7.zip       # creates /var/www/vibe-ecommerce
sudo chown -R www-data:www-data /var/www/vibe-ecommerce
cd /var/www/vibe-ecommerce
```

## 4. Configure `.env`

```bash
sudo -u www-data cp deploy/.env.production.example .env
sudo -u www-data nano .env        # fill in APP_URL, DB_PASSWORD, Gmail, Stripe…
sudo chmod 640 .env
```

Section 8 explains the Gmail values.

## 5. First install

```bash
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm ci && sudo -u www-data npm run build
sudo -u www-data php artisan key:generate --force
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --force      # settings, currencies, menus, pages — no demo data in production
sudo -u www-data php artisan storage:link
sudo -u www-data php artisan optimize
```

The seed prints a **random admin password**. Copy it now. You can also create or reset the admin at any time with:

```bash
sudo -u www-data php artisan store:admin
```

**Folder permissions.** Only `storage` and `bootstrap/cache` must be writable:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
```

## 6. Nginx and HTTPS

```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/vibe-ecommerce
sudo sed -i 's/example.ae/yourdomain.ae/g' /etc/nginx/sites-available/vibe-ecommerce
sudo ln -s /etc/nginx/sites-available/vibe-ecommerce /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.ae -d www.yourdomain.ae --redirect
```

Certbot adds the HTTPS settings and renews the certificate automatically.

## 7. Background worker, scheduler and backups

```bash
# Queue worker (sends emails in the background)
sudo cp deploy/vibe-queue.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now vibe-queue
sudo systemctl status vibe-queue      # should say "active (running)"

# Scheduler + nightly backup
sudo chmod +x deploy/backup.sh deploy/deploy.sh
sudo crontab -u www-data deploy/crontab
```

What runs automatically:

| When | What |
|---|---|
| Every minute | Scheduler heartbeat (lets the launch check confirm cron works) |
| Every 10 minutes | Cancel card orders unpaid after 60 minutes and return their stock |
| Daily | Remove abandoned carts, activity-log entries over a year old, and expired password-reset links |
| 02:30 daily | Back up the database and uploaded images, keeping 14 days |

**Keep a copy of backups off the server.** A backup on the same disk won't survive a disk failure. The simplest option is to download `storage/backups/` weekly with WinSCP. For automatic copies, use `rclone` to Google Drive, Dropbox or S3, or turn on your hosting provider's snapshot backups.

**Restoring**, if you ever need to:

```bash
cd /var/www/vibe-ecommerce
sudo -u www-data php artisan down
zcat storage/backups/db-YYYYMMDD-HHMM.sql.gz | mysql -u vibe -p vibe_ecommerce
sudo tar -xzf storage/backups/media-YYYYMMDD-HHMM.tar.gz -C storage/app
sudo -u www-data php artisan optimize:clear && sudo -u www-data php artisan optimize
sudo -u www-data php artisan up
```

## 8. Email with Gmail

Gmail sends through your Google account, using an **App Password** instead of your normal password:

1. In your Google Account, go to **Security** and turn on **2-Step Verification**.
2. Go to **Security → App passwords** (or search "App passwords"), create one named "Store", and copy the 16-character password.
3. In `.env`:

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_USERNAME=yourstore@gmail.com
MAIL_PASSWORD=abcdefghijklmnop      # the App Password, without spaces
MAIL_FROM_ADDRESS=yourstore@gmail.com
MAIL_FROM_NAME="Sadu Home"
```

4. Run `sudo -u www-data php artisan optimize` and `sudo systemctl restart vibe-queue`.
5. Test it: place a test order, or use the contact form on the Contact page.

**Good to know about Gmail**
- The **from address must be the Gmail account itself** (or an alias added in Gmail settings). Other addresses get rewritten or rejected.
- **Daily limits:** about 500 emails a day for free Gmail, and 2,000 with Google Workspace. That's enough for a new store. If you outgrow it, change the six `MAIL_` lines to a sending service (Postmark, Mailgun, Amazon SES) with no code changes.
- **Using a domain address with Google Workspace** (e.g. `hello@saduhome.ae`)? Add Google's **SPF and DKIM** records to your domain's DNS, so order emails don't land in spam.
- **To try Gmail from your Windows PC**, put the same lines in your local `.env`. Local installs use `QUEUE_CONNECTION=sync`, so emails go out immediately without a worker.

If emails don't arrive, check `php artisan queue:failed` and `storage/logs/laravel-*.log`. "Username and Password not accepted" means the App Password is wrong or 2-Step Verification is off.

## 9. Stripe on the live site

1. Switch the Stripe Dashboard to **live mode** and copy the live keys into `.env` (`pk_live_…`, `sk_live_…`).
2. Go to Developers → Webhooks and add the endpoint `https://yourdomain.ae/webhooks/stripe` with the events `checkout.session.completed`, `checkout.session.async_payment_succeeded` and `checkout.session.expired`. Copy the signing secret to `STRIPE_WEBHOOK_SECRET`.
3. Run `sudo -u www-data php artisan optimize`, then in the admin go to Settings → Payments and switch card payments on.

## 10. Check everything

```bash
sudo -u www-data php artisan store:launch-check
```

Fix every **FAIL**, and read every **WARN**. Then work through `docs/launch-checklist.md`.

Then load the site the way a browser does:

```bash
sudo -u www-data php artisan store:web-check            # or: … store:web-check https://your-domain
```

It opens the home page, a product, the cart and the admin sign-in and reports any script, style or
image that does not load (403/404), a second `Content-Security-Policy` header added by the web
server or hosting panel, and HTML served from a cache (stale script nonce). Each of these stops
buttons such as "Continue to delivery" from working.

## Updating the store later

Upload the new zip, then:

```bash
cd /var/www
sudo unzip -o /tmp/vibe-ecommerce-new.zip          # overwrites code; keeps .env, storage, uploads
sudo chown -R www-data:www-data vibe-ecommerce
cd vibe-ecommerce && sudo -u www-data bash deploy/deploy.sh
```

`deploy.sh` puts the site in maintenance mode for a few seconds. It then installs packages, builds assets, runs database updates, rebuilds caches, restarts the email worker, brings the site back, and runs the launch check.

**Moving to Git later** makes this simpler: `git pull` replaces the zip upload, and the rest is the same.

## If something goes wrong

- **White page or error 500:** run `tail -50 storage/logs/laravel-$(date +%F).log`. Never switch `APP_DEBUG=true` on the live site.
- **Styles missing, or `/build/assets/…` files answer 404:** upload the whole `public/build` folder from the zip (it is prebuilt), or run `npm ci && npm run build`, then `php artisan optimize`. `store:launch-check` lists any built file that is missing.
- **Buttons do nothing (checkout steps, pack size, admin forms):** run `php artisan store:web-check`. A FAIL for a second Content-Security-Policy means the web server, hosting panel or CDN adds its own policy header — remove it there (the store sends its own). A FAIL for the nonce means HTML is being cached by a CDN or hosting cache — turn HTML caching off for this site.
- **Images missing:** run `php artisan storage:link` and check that `APP_URL` matches your domain exactly.
- **Emails not sending:** check `systemctl status vibe-queue` and `php artisan queue:failed`.
- **"419 Page expired" on forms:** the domain in `APP_URL` doesn't match the one in the browser, or cookies are blocked.
- **Behind Cloudflare or a load balancer:** tell Laravel to trust the proxy (ask me and I'll add the setting).
