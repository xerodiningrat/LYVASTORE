<p align="center">
  <img src="./public/brand/lyva-mascot-mark.png" alt="Lyva Indonesia" width="96">
</p>

<h1 align="center">LYVASTORE</h1>

<p align="center">
  Platform top up game, voucher digital, pulsa, e-wallet, dan entertainment premium milik <strong>Lyva Indonesia</strong>.
</p>

<p align="center">
  <a href="https://lyvaindonesia.com">Website</a> ·
  <a href="./ABOUT.md">About</a> ·
  <a href="./NOTICE">Notice</a> ·
  <a href="./TRADEMARK.md">Trademark</a> ·
  <a href="./LICENSE">License</a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-red?style=flat-square" alt="Laravel 12">
  <img src="https://img.shields.io/badge/Inertia.js-Vue-blue?style=flat-square" alt="Inertia Vue">
  <img src="https://img.shields.io/badge/Vue-3-42b883?style=flat-square" alt="Vue 3">
  <img src="https://img.shields.io/badge/TypeScript-Enabled-3178c6?style=flat-square" alt="TypeScript">
  <img src="https://img.shields.io/badge/Vite-Frontend-646cff?style=flat-square" alt="Vite">
  <img src="https://img.shields.io/badge/License-Proprietary-black?style=flat-square" alt="Proprietary License">
</p>

## About

LYVASTORE adalah storefront dan admin panel untuk operasional `lyvaindonesia.com`.
Project ini dipakai untuk mengelola katalog produk digital, proses checkout,
monitoring transaksi, promo, keuangan, affiliate, hingga tooling pendukung
internal.

## Platform Highlights

- katalog publik untuk top up game, voucher digital, pulsa, e-wallet, dan produk entertainment
- checkout dan preview transaksi dengan integrasi VIPayment dan Duitku
- dashboard admin untuk produk, promo, margin, stok manual, transaksi, keamanan, dan finance
- sistem affiliate dan coin program untuk loyalty / reward
- mobile endpoints untuk katalog, auth, checkout, dan account history
- background remover dan tooling internal lain untuk operasional harian

## Tech Stack

- Backend: Laravel 12
- Frontend: Inertia.js, Vue 3, TypeScript
- Build Tool: Vite
- Database lokal default: SQLite
- Additional services: VIPayment, Duitku, WhatsApp flow, Google Sheets sync

## Repository Structure

```text
app/               Laravel controllers, services, models, middleware
bootstrap/         Laravel bootstrap files
chatbot-backend/   Lightweight Python chatbot companion service
config/            App and integration configuration
database/          Migrations, factories, seeders
docs/              Supporting integration docs and snippets
public/            Public assets, product artwork, icons, brand files
requirements/      Python dependency files
resources/         Vue pages, layouts, components, Blade views
routes/            Web, auth, settings, and console routes
scripts/           Utility scripts and background remover helpers
storage/           Runtime files and generated app data
```

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

Optional quality checks:

```bash
npm run typecheck
php artisan optimize:clear
```

## Operational Notes

- Runtime files in `storage/`, built assets in `public/build/`, and local secrets like `.env` are intentionally ignored from Git.
- Large release artifacts seperti APK sebaiknya tidak disimpan permanen di history Git.
- Repo ini memuat aset brand dan artwork produksi, jadi perubahan visual sebaiknya tetap mengikuti identitas Lyva Indonesia.

## Legal

Copyright (c) 2026 Lyva Indonesia. All rights reserved.

This repository is proprietary. No permission is granted to reuse, redistribute,
modify, or commercialize the contents without prior written approval from Lyva
Indonesia.

Additional repository notices:

- [ABOUT.md](./ABOUT.md)
- [NOTICE](./NOTICE)
- [TRADEMARK.md](./TRADEMARK.md)
- [LICENSE](./LICENSE)
