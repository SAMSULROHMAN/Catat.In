# AGENTS.md — Catat.In

## Tech Stack
- **Laravel 13** dengan PHP 8.3+
- **Tailwind CSS v4** (tanpa tailwind.config.* — gunakan `@import "tailwindcss"` di CSS, konfigurasi lewat `@theme`)
- **Vite** dengan plugin `@tailwindcss/vite` dan `laravel-vite-plugin` (font via `bunny()`)
- **MySQL** (lokal), **SQLite :memory:** (tes)
- **Session, cache, dan queue berbasis database** (migrasi sudah ada)
- **PHPUnit** (Pest tidak terinstal)

## Perintah
| Perintah | Kegunaan |
|---|---|
| `composer run setup` | Setup pertama kali: install, salin .env, generate key, migrate, npm install, npm build |
| `composer run dev` | Jalankan dev server: `artisan serve` + `queue:listen --tries=1` + `npm run dev` via concurrently |
| `composer run test` | `artisan config:clear` lalu `artisan test` (menjalankan PHPUnit) |
| `php artisan serve` | Dev server Laravel |
| `npm run dev` | Dev server Vite dengan HMR |
| `npm run build` | Build produksi Vite |

## Testing
- Tes menggunakan **SQLite in-memory** (lihat `phpunit.xml` — `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)
- Unit test mewarisi `PHPUnit\Framework\TestCase` (tanpa bootstrap Laravel)
- Feature test mewarisi `Tests\TestCase` (melakukan bootstrap Laravel)
- Trait `RefreshDatabase` **tidak** aktif secara default — tambahkan sendiri di feature test jika perlu
- Jalankan satu test: `php artisan test --filter=nama_test`
- Linting: `./vendor/bin/pint` (Laravel Pint)

## Struktur Proyek
- `app/` — namespace PSR-4 `App\`
- Satu-satunya model: `User` (menggunakan atribut PHP 8 `#[Fillable]`, `#[Hidden]`)
- Satu-satunya route: `GET /` mengembalikan view `welcome`
- Belum ada controller, command, atau job kustom
- `database/migrations/` — tabel users, cache, jobs, categories, transactions, budgets sudah dibuat
- Model migrated: `User` (kolom `password_hash` bukan `password`), belum ada model untuk categories/transactions/budgets

## Konvensi
- Atribut PHP 8 untuk konfigurasi model Eloquent (`#[Fillable]`, `#[Hidden]`, bukan properti `$fillable`)
- `.editorconfig`: indentasi 4 spasi, baris LF, UTF-8
- `.npmrc` mengatur `ignore-scripts=true` — menjalankan script npm manual perlu opt-in eksplisit
