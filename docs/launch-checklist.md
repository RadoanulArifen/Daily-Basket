# Launch checklist

Work through this before sharing the store's address. Most items take minutes.

## Server (see docs/deployment.md)
- [ ] `php artisan store:launch-check` shows **no FAIL** lines
- [ ] Site opens on `https://` and `http://` redirects to it
- [ ] `systemctl status vibe-queue` shows *active (running)*
- [ ] `sudo crontab -u www-data -l` shows the scheduler and backup lines
- [ ] A backup file appeared in `storage/backups` the next morning, and a copy is kept off the server
- [ ] You've tried restoring a backup once (on a test database)

## Store details (Admin → Settings)
- [ ] **General:** store name (English and Arabic), email, phone, address, **TRN**, logo, social links
- [ ] **Tax:** VAT on, 5%, prices include VAT (or as your accountant advises)
- [ ] **Checkout:** guest checkout, phone required, order notes
- [ ] **Shipping:** delivery price and free-delivery threshold
- [ ] **Payments:** cash-on-delivery fee; Stripe **live** keys and webhook; card payments switched on
- [ ] **Currencies:** exchange rates reviewed, and only the currencies you want active
- [ ] **Notifications:** email wording reviewed in both languages
- [ ] Admin panel address changed from `/admin` (optional: set `ADMIN_PATH` in `.env`)

## Content
- [ ] Demo products, categories and images replaced with real ones (the launch check warns if demo SKUs remain)
- [ ] Every product has a price, stock, at least one photo, and English and Arabic names
- [ ] Menus (Content → Menus) point to real categories and pages
- [ ] **Policy pages reviewed and approved:** Delivery & returns, Privacy, Terms
- [ ] About and Contact pages say what you want; the contact form reaches your inbox
- [ ] Home page banner (optional) added in Content → Banners
- [ ] Demo discount WELCOME10 switched off, or kept on purpose

## Test orders, on the live site
- [ ] Cash on delivery: order, confirmation email received, appears in Admin → Orders; mark shipped, then delivered, and both emails arrive
- [ ] Card: a real small payment with your own card, then refund it in Stripe and mark it refunded
- [ ] Try it in **Arabic on a phone**: browse, add to bag, check out
- [ ] Discount code applied at checkout
- [ ] Invoice prints with your TRN
- [ ] Cancel one test order: stock goes back

## Search engines and tracking
- [ ] Add the site to **Google Search Console** and submit `https://yourdomain.ae/sitemap.xml`
- [ ] Test a product page with Google's **Rich Results Test**
- [ ] (Optional) Google Analytics, for visitor numbers and conversion rates — ask me to add the tag

## After launch
- [ ] Check `php artisan store:launch-check` and Admin → Orders daily for the first week
- [ ] Plan the PHP/Laravel upgrade before 2027 (the README explains how)
