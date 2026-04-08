# LYVASTORE

Laravel + Inertia + Vue storefront and admin panel for `lyvaindonesia.com`.

## Stack

- Laravel 12
- Inertia.js
- Vue 3 + TypeScript
- Vite
- SQLite by default for local setup

## Main Features

- Public catalog for game top up, vouchers, entertainment, pulsa, and e-wallet
- Checkout flow with VIPayment and Duitku integrations
- Admin dashboard for products, promos, finance, manual stock, affiliates, and security
- Background remover service
- Mobile API endpoints for catalog, auth, checkout, and account history

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

For frontend development:

```bash
npm run dev
```

## Notes

- Runtime files in `storage/`, built assets in `public/build/`, and local secrets like `.env` are ignored from Git.
- Large release artifacts such as APK files should be distributed outside Git history when possible.

## Copyright and License

Copyright (c) 2026 Lyva Indonesia. All rights reserved.

This repository is proprietary. No permission is granted to reuse, redistribute,
modify, or commercialize the contents without prior written approval from Lyva
Indonesia.

See [LICENSE](./LICENSE) for the full copyright and usage terms.
