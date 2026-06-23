# Azre International — E-commerce site

Modern PHP + MySQL e-commerce site for **Azre International** — auto parts, lubricants and chemicals for trucks, autos, bus, marine and industry. Glassmorphism design, full admin panel, cart with guest + persisted carts, user accounts.

- **Stack:** PHP 8+, MySQL 5.7+/MariaDB 10+, vanilla JS, hand-rolled CSS (no build step)
- **Hosting:** Works on Hostinger shared / cloud / VPS (anything with PHP + MySQL)
- **Catalog seed:** 304 products across 12 categories from the supplier price list
- **No framework lock-in:** single front controller (`public/index.php`), procedural views, easy to extend

---

## Features

### Storefront
- Home with hero, featured products, latest arrivals, category tiles, trust strip
- Shop with category/brand/form filters, search, sort, pagination
- Category pages with breadcrumbs
- Product detail with related products, quantity selector, stock status
- Search across name / brand / SKU
- Cart (guest via session, persisted to DB on login), quantity updates, remove
- User registration / login / logout / account dashboard
- About / Contact pages
- Fully responsive (mobile, tablet, desktop)
- Glassmorphism design with navy/amber palette, hover lift, transparency

### Admin (gated)
- Dashboard with stats (active products, orders, revenue, low stock, messages)
- Product CRUD with image upload, featured/active toggles
- Category CRUD
- Recent orders, low stock alerts
- Contact message inbox (via DB)
- CSRF + role-based access control

---

## Local development

### Option A — Docker (recommended)

```bash
docker run -d --name azre-mysql \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=azre_international \
  -p 3306:3306 mysql:8

# in project root
cp includes/config.local.php.example includes/config.local.php
# (defaults already point at the docker MySQL)

# create schema + seed catalog
mysql -h127.0.0.1 -P3306 -uroot -proot azre_international < database/schema.sql
php database/seed.php

# run PHP dev server
php -S localhost:8000 -t public
```

Visit http://localhost:8000

Admin login: `admin@azreinternationalgy.com` / `admin1234` (change immediately).

### Option B — Hostinger (production)

1. In Hostinger hPanel, open **Databases → MySQL** and create a database (e.g. `azre_intl`).
2. Open **phpMyAdmin** and import `database/schema.sql`.
3. Connect this GitHub repo to your `public_html/` via **hPanel → GIT**. The web root is the repo root.
4. SSH in or use File Manager and create `includes/config.local.php`:
   ```php
   <?php
   define('AZRE_DB_HOST', 'localhost');
   define('AZRE_DB_PORT', '3306');
   define('AZRE_DB_NAME', 'your_hostinger_db');
   define('AZRE_DB_USER', 'your_user');
   define('AZRE_DB_PASS', 'your_pass');
   ```
5. SSH in: `cd public_html && rm -rf public && git pull origin main && php database/seed.php`
6. Sign in to admin at `https://www.azreinternationalgy.com/admin` and **change the default password**.

---

## Project layout

```
azre-international/
├── public/                          # Apache / web root
│   ├── index.php                    # Front controller & router
│   ├── .htaccess                    # Rewrite + security headers
│   ├── assets/
│   │   ├── css/app.css              # Design system
│   │   ├── js/app.js                # Interactions
│   │   └── images/                  # Logo, placeholder, favicon
│   └── uploads/                     # Product images (writable)
├── includes/                        # Server-side code
│   ├── config.php                   # Base config (DB host fallback, paths)
│   ├── config.local.php.example     # Template for real credentials
│   ├── db.php                       # PDO connection
│   ├── helpers.php                  # HTML/money/csrf/etc helpers
│   ├── auth.php                     # Login / register / session
│   ├── cart.php                     # Cart (session + DB)
│   └── views/                       # Page templates
│       ├── header.php / footer.php
│       ├── home.php / shop.php / category.php / product.php / search.php
│       ├── cart.php / cart_add.php / cart_update.php / cart_remove.php
│       ├── login.php / register.php / logout.php / account.php
│       ├── about.php / contact.php
│       ├── admin/  (login, dashboard, products, product_form, product_delete, categories, logout)
│       └── partials/product_card.php
├── database/
│   ├── schema.sql                   # MySQL schema
│   ├── catalog.json                 # Parsed product catalog
│   └── seed.php                     # One-shot seeder
├── parse_pdf.py                     # PDF → catalog.json (run once)
├── enrich_catalog.py                # Add derived fields to catalog
└── README.md
```

---

## URL routes

| Route | View |
|---|---|
| `/` | Home |
| `/shop` | Product listing with filters |
| `/category/{slug}` | Category page |
| `/product/{slug}` | Product detail |
| `/search?q=...` | Search |
| `/cart` | Cart |
| `/cart/add` (POST) | Add to cart |
| `/cart/update` (POST) | Update qty |
| `/cart/remove` (POST) | Remove from cart |
| `/login`, `/register`, `/logout` | Auth |
| `/account` | Account dashboard |
| `/about`, `/contact` | Static pages |
| `/admin` | Dashboard (admin only) |
| `/admin/products` | Product list |
| `/admin/product/new`, `/admin/product/{id}/edit` | Product form |
| `/admin/product/{id}/delete` (POST) | Delete product |
| `/admin/categories` | Category CRUD |

---

## Adding products via admin

1. Sign in as admin → **Products → + New product**
2. Fill in SKU (e.g. `MY-SKU-001`), name, brand, category, price, stock
3. Optionally upload an image (≤ 5 MB; jpg/png/webp/gif)
4. Toggle **Active** to make visible, **Featured** for home-page slot
5. Save

To bulk-load from a new supplier price sheet:

1. Drop the new PDF into `attachments/`
2. Update `parse_pdf.py` SECTION_PATTERNS to match any new section banners
3. `python3 parse_pdf.py && python3 enrich_catalog.py`
4. `php database/seed.php` (skips SKUs that already exist)

---

## Security checklist

- All POST endpoints require CSRF token (`csrf_field()` + `csrf_check()`)
- Passwords hashed with `password_hash(..., PASSWORD_BCRYPT)`
- Sessions: HttpOnly + SameSite=Lax cookies
- Role-based admin gate (`auth_require_admin`)
- SQL: all queries via PDO prepared statements
- HTML escape helper `e()` used everywhere user data is rendered
- `uploads/` directory forbids PHP execution (see `.htaccess`)
- Security headers (X-Frame-Options, CSP, X-Content-Type-Options, Referrer-Policy)

---

## Customizing

- **Brand colors:** edit CSS variables in `public/assets/css/app.css` (top of file)
- **Currency / locale:** `includes/config.php` (`AZRE_CURRENCY`, `AZRE_CURRENCY_SYMBOL`)
- **Store email/phone/address:** settings table (`SELECT * FROM settings`) or admin
- **Home hero:** edit `includes/views/home.php`
- **Email sender:** wire `mail()` or a transactional provider in `includes/views/contact.php`

---

## Roadmap

- [ ] Real checkout (Stripe/PayPal) and order confirmation emails
- [ ] Customer order history with detail page
- [ ] Wishlist / save-for-later
- [ ] Product image gallery (multiple per product)
- [ ] Reviews / ratings
- [ ] Bulk CSV import for supplier price lists
- [ ] Multi-currency / multi-language
- [ ] Server-side image resize / WebP conversion

---

Built for Azre International · Georgetown, Guyana
