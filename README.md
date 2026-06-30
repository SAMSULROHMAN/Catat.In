<div align="center">
  <h1>Catat.In</h1>
  <p><strong>Catat Setiap Rupiah</strong></p>
  <p>Aplikasi pencatatan keuangan pribadi berbasis web untuk membantu Anda mengelola pemasukan dan pengeluaran dengan mudah.</p>
</div>

## Fitur

- **Catat Transaksi** — Catat pemasukan dan pengeluaran harian dengan kategori, jumlah, dan catatan.
- **Kategori** — Kelola kategori transaksi dengan ikon emoji, favorit, dan saran kategori otomatis berdasarkan catatan.
- **Anggaran (Budget)** — Tetapkan batas pengeluaran per kategori tiap bulan dengan notifikasi saat mendekati atau melampaui batas.
- **Dashboard Interaktif** — Lihat ringkasan keuangan, grafik pie pengeluaran, perbandingan bulanan, arus kas, dan ringkasan mingguan.
- **Ekspor Laporan** — Ekspor transaksi ke Excel (.xlsx) atau CSV untuk analisis lebih lanjut.
- **Keamanan** — Mendukung autentikasi dua faktor (2FA) dan passkeys (WebAuthn) untuk login tanpa kata sandi.

## Tech Stack

| Teknologi | Keterangan |
|-----------|------------|
| **Backend** | Laravel 13.x (PHP 8.3+) |
| **Frontend** | Blade + JavaScript Vanilla |
| **CSS** | Tailwind CSS v4 |
| **Build Tool** | Vite + laravel-vite-plugin |
| **Auth** | Laravel Fortify (2FA, Passkeys) |
| **Database** | MySQL |
| **Export** | PhpSpreadsheet |
| **Charts** | Chart.js 4.x |

## Persyaratan Sistem

- PHP 8.3+
- Composer
- Node.js & npm
- MySQL
- Web server (Laragon / Apache / Nginx)

## Instalasi

```bash
# Clone repositori
git clone https://github.com/username/catat-in.git
cd catat-in

# Install dependensi PHP
composer install

# Copy environment dan generate key
cp .env.example .env
php artisan key:generate

# Konfigurasi database di file .env lalu jalankan migrasi
php artisan migrate --force

# Install dependensi frontend dan build
npm install --ignore-scripts
npm run build
```

## Data Demo

Jalankan seeder untuk membuat data contoh:

```bash
php artisan db:seed --force
```

Akun demo:
- **Email:** `admin@admin.com`
- **Password:** `Admin123`

## Development

```bash
npm run dev
```

Perintah di atas akan menjalankan tiga proses secara paralel:
1. Laravel development server (`php artisan serve`)
2. Queue worker (`php artisan queue:listen`)
3. Vite dev server untuk hot-reload frontend

## Struktur Proyek

```
app/
├── Actions/Fortify/       # Aksi autentikasi (registrasi, update profil, dll)
├── Http/Controllers/      # Controller aplikasi
├── Models/                # Model Eloquent (User, Category, Transaction, Budget)
├── Policies/              # Policy otorisasi
├── Services/              # Service layer (Budget, Dashboard, CategorySuggestion)
database/
├── migrations/            # Migrasi database
├── seeders/               # Seeder data demo
resources/views/           # Template Blade
routes/web.php             # Semua route aplikasi
```

## Lisensi

MIT
