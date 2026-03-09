# Mauriel Service Directory — Plugin Documentation

## What This Plugin Is

A production-ready WordPress plugin that powers a local service business directory — similar to Yelp, Angi, or Thumbtack. Built from scratch for the Mbusiness Branding AI project.

**Plugin folder:** `/Volumes/MainHD2T/Documents2/Claud_Code/MbusinessBranding/mauriel-service-directory/`
**Installable zip:** `/Volumes/MainHD2T/Documents2/Claud_Code/MbusinessBranding/mauriel-service-directory.zip`
**Plugin version:** 1.0.0
**Namespace/prefix:** `mauriel_` (all hooks, options, DB tables, CSS classes, meta keys)

---

## Core Features

| Feature | Details |
|---------|---------|
| Business Listings | Custom post type `mauriel_listing` with rewrite slug `/directory/` |
| Categories | Hierarchical taxonomy `mauriel_category` |
| Subscription Packages | Free / Basic / Pro / Premium via Stripe |
| Business Dashboard | 8-tab owner dashboard: listing, media, hours, analytics, leads, reviews, coupons, subscription |
| Search & Map | ZIP radius search with Haversine SQL + Google Maps JS |
| Lead Capture | Contact form, quote request, phone/website click tracking |
| Reviews | Submit, moderate, Google Places import, owner AI responses |
| Analytics | Views, impressions, clicks, leads — deduplicated via session hash |
| AI Features | OpenAI description generator + review response suggester |
| Business Hours | 7-day editor with "open now" badge using WP timezone |
| Coupons / Deals | Deal cards with reveal-code button |
| Claim Flow | Business claim with 24-hr expiring email verification token |
| Booking Widget | Calendly / Square / generic link embed |
| SEO | Schema.org LocalBusiness JSON-LD + OG tags + meta patterns |
| Emails | HTML transactional emails: approved, pending, lead, review, claim |
| Multi-step Registration | 4-step: account → business info → package → confirmation |
| Admin Panel | 8 admin pages + 6-tab settings page |

---

## File Structure (96 files total)

```
mauriel-service-directory/
├── mauriel-service-directory.php     ← Plugin bootstrap + constants
├── composer.json                     ← Stripe PHP SDK dependency
├── uninstall.php                     ← Full cleanup on plugin delete
│
├── includes/
│   ├── class-mauriel-autoloader.php  ← PSR-4 style autoloader
│   ├── class-mauriel-core.php        ← Singleton core, wires all subsystems
│   ├── class-mauriel-activator.php   ← DB tables, roles, pages, seed packages
│   ├── class-mauriel-deactivator.php
│   │
│   ├── post-types/
│   │   ├── class-mauriel-post-type-listing.php
│   │   └── class-mauriel-taxonomy-category.php
│   │
│   ├── roles/
│   │   └── class-mauriel-roles.php   ← mauriel_business_owner + mauriel_directory_admin
│   │
│   ├── db/                           ← 6 custom tables (see schema below)
│   │   ├── class-mauriel-db.php
│   │   ├── class-mauriel-db-packages.php
│   │   ├── class-mauriel-db-subscriptions.php
│   │   ├── class-mauriel-db-leads.php
│   │   ├── class-mauriel-db-analytics.php
│   │   ├── class-mauriel-db-hours.php
│   │   └── class-mauriel-db-coupons.php
│   │
│   ├── stripe/
│   │   ├── class-mauriel-stripe.php
│   │   ├── class-mauriel-stripe-checkout.php
│   │   ├── class-mauriel-stripe-webhook.php
│   │   └── class-mauriel-stripe-products.php
│   │
│   ├── ai/
│   │   ├── class-mauriel-ai.php
│   │   ├── class-mauriel-ai-description.php
│   │   └── class-mauriel-ai-review-response.php
│   │
│   ├── search/
│   │   ├── class-mauriel-search.php
│   │   ├── class-mauriel-search-filters.php
│   │   └── class-mauriel-geocoder.php
│   │
│   ├── reviews/
│   │   ├── class-mauriel-reviews.php
│   │   └── class-mauriel-google-places.php
│   │
│   ├── leads/class-mauriel-leads.php
│   ├── analytics/class-mauriel-analytics.php
│   ├── seo/class-mauriel-seo.php
│   ├── media/class-mauriel-media.php
│   ├── coupons/class-mauriel-coupons.php
│   ├── booking/class-mauriel-booking.php
│   ├── claim/class-mauriel-claim.php
│   ├── registration/
│   │   ├── class-mauriel-registration.php
│   │   └── class-mauriel-onboarding.php
│   ├── dashboard/class-mauriel-dashboard.php
│   │
│   ├── shortcodes/
│   │   ├── class-mauriel-shortcode-directory.php  ← [mauriel_directory]
│   │   ├── class-mauriel-shortcode-dashboard.php  ← [mauriel_dashboard]
│   │   └── class-mauriel-shortcode-featured.php   ← [mauriel_featured]
│   │
│   ├── rest/
│   │   ├── class-mauriel-rest-controller.php      ← Abstract base, namespace: mauriel/v1
│   │   ├── class-mauriel-rest-listings.php
│   │   ├── class-mauriel-rest-search.php
│   │   ├── class-mauriel-rest-leads.php
│   │   ├── class-mauriel-rest-reviews.php
│   │   ├── class-mauriel-rest-analytics.php
│   │   ├── class-mauriel-rest-coupons.php
│   │   └── class-mauriel-rest-ai.php              ← Also registers Stripe webhook route
│   │
│   └── admin/
│       ├── class-mauriel-admin.php                ← Menu + asset enqueue
│       ├── class-mauriel-admin-settings.php       ← 6-tab Settings API page
│       ├── class-mauriel-admin-listings.php
│       ├── class-mauriel-admin-packages.php
│       ├── class-mauriel-admin-payments.php
│       ├── class-mauriel-admin-reviews.php
│       ├── class-mauriel-admin-categories.php
│       └── class-mauriel-admin-analytics.php
│
├── templates/                         ← Theme-overridable (copy to {theme}/mauriel/)
│   ├── directory/    archive, filters, listing-card, map-view, pagination
│   ├── single/       single-listing, reviews, lead-forms, hours, gallery, map, coupons, booking
│   ├── dashboard/    wrapper + 8 tab templates
│   ├── registration/ step-account, step-business, step-package, step-confirmation
│   └── emails/       listing-approved, listing-pending, new-lead, new-review, claim-verification
│
└── assets/
    ├── css/  mauriel-public.css, mauriel-dashboard.css, mauriel-admin.css
    ├── js/   mauriel-public.js, mauriel-dashboard.js, mauriel-maps.js
    └── images/placeholder-listing.png
```

---

## Database Tables (prefix: `{wp_prefix}mauriel_`)

| Table | Purpose |
|-------|---------|
| `mauriel_packages` | Subscription tiers (Free/Basic/Pro/Premium), Stripe price IDs |
| `mauriel_subscriptions` | Per-listing subscription status, Stripe customer/subscription IDs |
| `mauriel_leads` | Contact, quote, phone click, email click events |
| `mauriel_analytics` | Views, impressions, clicks — deduplicated via UNIQUE session_hash key |
| `mauriel_business_hours` | 7-day hours per listing, upserted via ON DUPLICATE KEY UPDATE |
| `mauriel_coupons` | Deal listings with discount type, value, expiry, use count |

All listing data (address, lat/lng, social links, gallery, etc.) stored as WordPress post meta with `_mauriel_` prefix.

---

## Shortcodes

| Shortcode | Use | Key Attributes |
|-----------|-----|----------------|
| `[mauriel_directory]` | Full searchable directory with map | `category`, `per_page`, `view` (grid/list/map), `featured_only`, `show_filters` |
| `[mauriel_dashboard]` | Business owner management portal | _(no attrs — gated by login + role)_ |
| `[mauriel_featured]` | Featured listings widget | `count`, `category`, `orderby` |

---

## REST API Endpoints (namespace: `mauriel/v1`)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/search` | ZIP radius search + map markers |
| GET/POST/DELETE | `/listings/{id}` | Listing CRUD |
| POST | `/leads` | Submit a lead (rate limited: 3/hr/IP) |
| GET/POST | `/leads/{id}/mark-read` | Lead management |
| POST | `/reviews` | Submit review (rate limited: 1/24hr/IP/listing) |
| POST | `/reviews/{id}/respond` | Owner response |
| POST | `/reviews/{id}` | Approve / trash |
| POST | `/reviews/import-google` | Import from Google Places |
| GET | `/analytics/{listing_id}` | Owner analytics dashboard data |
| POST | `/analytics/record` | Record event (public, deduplicated) |
| GET/POST/DELETE | `/coupons` | Coupon CRUD |
| POST | `/ai/generate-description` | AI description (10/day/user) |
| POST | `/ai/suggest-response` | AI review response |
| POST | `/stripe/webhook` | Stripe webhook handler |

---

## Admin Settings (6 tabs)

| Tab | Key Options |
|-----|------------|
| General | Directory/dashboard/register page assignments, auto-approve toggle, listings per page, currency, distance unit |
| Stripe | Live/test mode, 4 API keys, webhook secret |
| Google | Maps JS key, Geocoding key, Places key, default map center/zoom |
| AI | Enable toggle, OpenAI key, model, max tokens, prompt prefix |
| SEO | Schema toggle, title/meta patterns, OG tags, noindex pending |
| Email | From name/address, email logo, notification toggles |

---

## Known Bugs Fixed

### Bug 1 — Settings page blank (fixed 2026-03-09)
**Symptom:** Settings tabs showed but no form fields rendered.
**Root cause:** `Mauriel_Admin_Settings` was instantiated inside `page_settings()` (the menu render callback), which fires after WordPress's `admin_init` hook. So `register_settings()` — hooked to `admin_init` — never ran.
**Fix:** `class-mauriel-admin.php` now instantiates `Mauriel_Admin_Settings` in the `Mauriel_Admin` constructor (which runs on `plugins_loaded`), storing it as `$this->settings`. `page_settings()` calls `$this->settings->render()`.

---

## First-Time Setup After Install

1. **Activate** the plugin — auto-creates 6 DB tables, 2 user roles, 3 pages (Directory, Dashboard, Register)
2. **Settings → General** — assign the auto-created pages to their correct page dropdowns
3. **Settings → Stripe** — add Stripe test keys, webhook secret (get from Stripe Dashboard → Developers → Webhooks)
4. **Stripe webhook URL** to register: `https://yoursite.com/wp-json/mauriel/v1/stripe/webhook`
5. **Settings → Google** — add Maps JS + Geocoding + Places API keys (same key works for all 3 if enabled in Google Cloud Console)
6. **Settings → AI** — add OpenAI API key, select model (gpt-4o-mini recommended)
7. **Flush permalinks** — Settings → Permalinks → Save (to activate `/directory/` rewrite)
8. **Run Composer** if using Stripe payments: `cd mauriel-service-directory && composer install`

---

## API Keys You'll Need

| Service | Where to Get |
|---------|-------------|
| Stripe publishable + secret (test + live) | dashboard.stripe.com → Developers → API Keys |
| Stripe webhook signing secret | dashboard.stripe.com → Developers → Webhooks → Add endpoint |
| Google Maps JavaScript API | console.cloud.google.com → APIs & Services → Credentials |
| Google Geocoding API | Same Google Cloud project — enable Geocoding API |
| Google Places API | Same Google Cloud project — enable Places API |
| OpenAI API key | platform.openai.com → API Keys |

---

## Updating the Plugin

When making changes, always:
1. Edit files in `/Volumes/MainHD2T/Documents2/Claud_Code/MbusinessBranding/mauriel-service-directory/`
2. Re-zip from the `MbusinessBranding` folder:
   ```bash
   cd /Volumes/MainHD2T/Documents2/Claud_Code/MbusinessBranding
   rm mauriel-service-directory.zip
   zip -r mauriel-service-directory.zip mauriel-service-directory/ --exclude "mauriel-service-directory/vendor/*" --exclude "*/.DS_Store" -q
   ```
3. In WordPress: Plugins → Deactivate → Delete → Upload new zip → Activate

**Or use SFTP/SSH** to push changed files directly to the server without reinstalling (faster for small updates).

---

## Backup Suggestions

### Minimum (do all of these)

1. **Git repository** — Initialize a git repo in the plugin folder. Commit after every working change. Push to a private GitHub repo. This gives you full history and the ability to roll back any change.
   ```bash
   cd /Volumes/MainHD2T/Documents2/Claud_Code/MbusinessBranding/mauriel-service-directory
   git init
   git add -A
   git commit -m "Initial working build v1.0.0"
   ```

2. **Keep versioned zips** — After each update, copy the zip with a version in the name:
   `mauriel-service-directory-v1.0.0.zip`, `mauriel-service-directory-v1.0.1.zip`
   Store in a `/Versions/` subfolder in this directory.

3. **WordPress database backup** — Before activating a new version, always export the DB (WP Admin → Tools → Export, or use a plugin like UpdraftPlus). The 6 custom tables hold real business data.

### Recommended (for production)

4. **UpdraftPlus** (free WordPress plugin) — Scheduled daily backups of files + DB → Google Drive or Dropbox automatically.

5. **Server-level snapshots** — If on DigitalOcean/Vultr/AWS, enable daily droplet/server snapshots. Independent of WordPress.

6. **Staging environment** — Test all plugin updates on a staging site (wp-staging.com plugin makes this easy) before pushing to production. Never test on live with real customer data.

---

## Security Audit

Security is a real concern for this plugin — it handles Stripe payments, user accounts, file uploads, and stores business/customer data.

### Step 1 — Automated Static Analysis (Free, Do This First)

**PHP_CodeSniffer + WordPress Security ruleset** catches ~80% of WordPress-specific issues automatically:
```bash
cd /Volumes/MainHD2T/Documents2/Claud_Code/MbusinessBranding/mauriel-service-directory
composer require --dev squizlabs/php_codesniffer wp-coding-standards/wpcs
./vendor/bin/phpcs --standard=WordPress-Security includes/ templates/
```
Catches: missing nonce checks, unescaped output, direct DB queries, raw `$_GET`/`$_POST` usage.

Also consider **Psalm** or **PHPStan** for type errors and undefined variables that can become exploits.

---

### Step 2 — Manual Checklist (High-Risk Areas in This Plugin)

| Area | Risk | What to Verify |
|------|------|----------------|
| REST endpoints | Unauthorized access | Every endpoint has `permission_callback` — not just `__return_true` |
| Stripe webhook | Replay attacks | `constructEvent()` with signing secret is in place |
| File uploads | Malicious file execution | `wp_check_filetype()` + MIME whitelist in `class-mauriel-media.php` |
| Lead/review forms | Spam / rate limit bypass | Transient rate limiting uses IP + nonce together |
| AI endpoints | API key abuse | Per-user daily quota transient is enforced |
| DB queries | SQL injection | Every query uses `$wpdb->prepare()` — zero raw interpolation |
| Output | XSS | Every `echo` uses `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_kses_post()` |
| Claim flow | Token forgery | `hash_equals()` used (not `==`) + expiry check |
| Shortcodes | Privilege escalation | Dashboard shortcode checks role before rendering |

---

### Step 3 — Paid / Professional Tools

| Tool | Cost | Purpose |
|------|------|---------|
| **Patchstack** | ~$99/yr | Real-time vulnerability monitoring — alerts when Stripe SDK or any dependency has a known CVE |
| **WPScan** | Free tier available | Scans live site: `wpscan --url yoursite.com --api-token YOUR_TOKEN` |
| **Wordfence** | Free WP plugin | Firewall + malware scanner for production |

---

### Step 4 — Pre-Launch Security Checklist

Run through all of these before accepting real payments or real customer data:

```
[ ] Enable WP_DEBUG in staging — confirm zero PHP warnings/notices
[ ] Test all REST endpoints with no auth — confirm they return 401/403
[ ] Test Stripe webhook without signing secret — confirm it rejects the request
[ ] Submit a lead as a guest — confirm rate limiting kicks in after 3 attempts/hr
[ ] Upload a .php file as a gallery image — confirm it is rejected
[ ] Check that pending listings return noindex meta in page source
[ ] Confirm uninstall.php actually drops all tables and options on plugin delete
[ ] Verify Stripe webhook URL is registered in Stripe Dashboard
[ ] Confirm all API keys are stored in WP options (never hardcoded in files)
```

---

### Recommended Security Stack for Production

1. **Now (free):** Run PHP_CodeSniffer — fix anything flagged
2. **At first paying client:** Add Patchstack for ongoing CVE monitoring
3. **On live server:** Install Wordfence (free tier) as a firewall layer
4. **Ongoing:** Never update the live plugin without testing on a staging site first

---

## Planned Future Updates

- [ ] WooCommerce integration option for package payments
- [ ] SMS lead notifications via Twilio
- [ ] Bulk listing import via CSV
- [ ] Advanced analytics exports (CSV download)
- [ ] White-label branding option (rename prefix from `mauriel_` to client name)
- [ ] Listing comparison feature (side-by-side)
- [ ] Mobile app API extensions

---

## Contact / Context

Built by Claude Code (Anthropic) for Mbusiness Branding AI.
Plugin handle: `mauriel-service-directory`
All support and updates handled through this Claude Code project.
