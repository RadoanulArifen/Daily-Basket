# Stripe & Aramex in the admin, and the Online Store area

## Updating your local copy

Copy the files over your folder (overwrite when asked), start MySQL in XAMPP, then:

```powershell
composer dump-autoload
php artisan migrate
npm run build
php artisan optimize:clear
```

`migrate` adds the history table for Custom code versions.

## Stripe (Settings → Payments → Stripe account)

- Paste the **publishable key**, **secret key** and **webhook signing secret**, then click **Save details**.
- **Security:**
  - **Encrypted:** keys are encrypted in the database with your `APP_KEY`, so a database backup alone doesn't reveal them.
  - **Never shown again:** you only ever see the last 4 characters (`••••4f2a`). To change a key, paste a new one; empty fields keep what's saved.
  - **Checked when saved:** each key must look right (`pk_…`, `sk_…`, `whsec_…`), and test and live keys can't be mixed.
  - **Owner only,** and the activity log records *that* a key changed, never the key.
  - **`.env` wins:** if a key is set there, the admin shows "Set on the server" and the field is locked.
- **Test connection** checks the secret key with Stripe and tells you whether you're in test or live mode.
- **Remove details** deletes the saved keys and switches card payments off.
- **If you ever change `APP_KEY`,** saved keys can't be decrypted any more; enter them again.

## Aramex (Settings → Shipping and delivery → Aramex)

- Enter the **username, password, account number, PIN, entity and country** from your Aramex account.
- **Choose:**
  - **Test** (Aramex sandbox) or **Live** mode
  - which emirate you **ship from**
  - the **product type** (`ONP` unless Aramex says otherwise)
  - the delivery time shown to customers, in English and Arabic
- **Test connection** asks Aramex for a real price (1 kg within Dubai). Only after it succeeds can you switch on **Show Aramex rates at checkout**.
- **At checkout** customers see "Aramex courier" with its live price next to your standard delivery. Prices are cached for 30 minutes per emirate and weight.
- **If Aramex is slow or down,** Aramex is left out and your own delivery options still show. Customers never see an error.
- **Changing** any detail, the mode or the origin switches Aramex off until you test again.

**Note:** the Aramex rate request follows Aramex's standard Rate Calculator API. It has been tested against a simulated Aramex, but not the real service (that needs your account). Run **Test connection** with your real details first. If Aramex returns an error, the message shows exactly what it said, and I can adjust.

## Online Store (sidebar)

The sidebar item now opens the **Online Store** area. The ↗ icon beside it still opens your live store.

**Themes → Customize**
- **Settings:**
  - **Colours:** text and buttons, accent, background, panels
  - **Typography:** IBM Plex Sans, system font, serif or rounded
  - **Announcement bar:** on/off and message, in English and Arabic
  - **Homepage sections:** show or hide each one, and reorder them with the arrows
- **Save** keeps your changes as a draft. The **preview on the right is your real store** with the draft applied, on desktop or phone. **Publish** makes it live, and **Discard changes** throws the draft away.
- **Only admins who are previewing see the draft,** with a banner and **Exit preview**. Customers always see the published version.
- **Open to the Owner, Manager and Content roles.**

**Custom code** (Owner only)
- **Custom CSS,** added after the theme's styles.
- **Code in `<head>`** (Meta Pixel, Google Tag Manager, verification tags) and **code before `</body>`** (chat widgets).
- **Trusted script domains,** one per line (e.g. `connect.facebook.net`). The store's security policy blocks outside scripts, so external scripts, images and frames may only load from the domains listed here. The policy itself stays on.
- **Scripts you paste are adjusted automatically:** each `<script>` gets the page's security token (nonce), so it runs under the security policy.
- **Admin stays clean:** custom code only runs on the storefront, never in the admin.
- **Version history:** the last 30 saves are kept, with one-click **Restore**.

**Preferences** (Owner only)
- **Password protection:** close the store to visitors (e.g. before launch) with a password and a message in English and Arabic.
- **Signed-in admins** still see the store normally.
- **Search engines** are told not to index anything while it's on.
- **Stripe's webhook** keeps working.

**Theme files** are deliberately not editable in the admin. The templates run as code on the server, so editing them from the admin would let anyone who gets into an admin account take over the website. Use Customize and Custom code, and make layout changes in the project files.

## Checked

- **213 automated tests** (12 new), on SQLite and MariaDB. They cover:
  - **Keys:** encrypted at rest, decrypted on the next request, masked on the page, empty fields keeping the saved value, `.env` winning, format and test/live checks, Owner-only, and never logged
  - **Stripe:** Test connection, for good and bad keys
  - **Aramex (simulated):** an error and then success, required before switching on, the right city/product/account sent, and checkout still working when Aramex is down
  - **Customize:** the draft visible only to previewing admins, section order and hiding, publishing, and refusing unsafe colours
  - **Custom code:** it can't break out of `<style>`, scripts get nonces, trusted domains reach the security policy (junk ignored), nothing runs in the admin, and restore works
  - **Store password:** refuses to turn on without a password, the lock, robots, webhooks still working, wrong/right password, and admins passing through
  - **Roles** for every part
- **In a real browser:** Stripe keys saved and shown masked (`••••4f2a`), the Aramex card, Customize with the live preview picking up a new accent colour, and Custom code. No JavaScript, security-policy or server errors.
- **One layout bug found and fixed:** the sidebar's Online Store item broke onto two lines after the ↗ link was added.
