# Project Terupdate

Aplikasi monitoring inventaris laptop PT LinkIT menggunakan Laravel 13, PHP 8.3, dan MySQL/MariaDB dari XAMPP.

## Persiapan

- XAMPP dengan Apache dan MySQL (atau MariaDB); pastikan port MySQL sesuai nilai `DB_PORT` di `.env` (default `3306`).
- PHP 8.3 atau versi 8.x lain yang didukung Laravel 13, serta Composer.
- Node.js dan npm untuk memasang serta membangun aset frontend Laravel.

## Menjalankan di komputer lokal

1. Jalankan **MySQL** di XAMPP.
2. Buat database bernama `project_terupdate` melalui phpMyAdmin (`http://localhost/phpmyadmin`) atau jalankan:

   ```sql
   CREATE DATABASE project_terupdate;
   ```

3. Pastikan pengaturan koneksi di `.env` sesuai dengan XAMPP. Nilai awal yang disiapkan:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=project_terupdate
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` jika kredensial MySQL Anda berbeda.

4. Dari folder proyek, jalankan:

   ```powershell
   composer install
   php artisan migrate --seed
   npm install
   npm run build
   php artisan serve
   ```

5. Buka `http://127.0.0.1:8000` di browser.

`php artisan serve` menjalankan server pengembangan Laravel; XAMPP menyediakan MySQL. Jika ingin mengakses lewat Apache XAMPP di `http://localhost`, arahkan document root/vhost Apache ke folder `public` proyek, bukan ke root proyek. Jangan letakkan file `.env` di document root publik.

## Struktur penting

- `app/Models` — model dan relasi data.
- `app/Http/Controllers` — logika permintaan aplikasi.
- `routes/web.php` — route halaman web.
- `database/migrations` — definisi perubahan struktur database.
- `database/seeders` — data awal untuk development.
- `resources/views` — template halaman Blade.
- `public` — document root untuk Apache dan berkas publik.
- `.env` — konfigurasi lokal dan kredensial database; jangan dibagikan atau dimasukkan ke Git.

Untuk menambah fitur, umumnya siapkan migration dan model, controller, route, lalu view; gunakan seeder bila fitur memerlukan data awal. Jalankan `php artisan migrate` setelah menambah migration.

## Fitur yang tersedia

- Gambar latar halaman login dapat diganti melalui `public/images/login-bg.jpg`; lapisan gelap transparan pada CSS menjaga kontras teks.
- Logo transparan LinkIT ada di `public/images/linkit-logo.png` dan dapat diganti dengan memperbarui file tersebut. Palet utama menggunakan abu-abu biru logo dengan latar arang.
- Login dan pendaftaran karyawan dengan persetujuan admin.
- CRUD divisi untuk admin; divisi yang masih dipakai karyawan atau laptop tidak dapat dihapus.
- Data laptop, nomor seri, kondisi, status, divisi, label QR, catatan audit, dan alur perawatan.
- Serah terima peminjaman, pengajuan pengembalian, serta persetujuan admin dengan pencocokan nomor seri.
- Pemindaian QR melalui kamera browser atau pencarian kode aset manual.
- Dashboard admin/karyawan, pengingat keterlambatan, riwayat aktivitas, dan ekspor laporan inventaris ke CSV.
- Layout responsif untuk desktop, tablet, dan ponsel; navigasi layar kecil menggunakan menu samping yang dapat dibuka dan ditutup.

Untuk membuat akun awal dan data contoh di lingkungan lokal, jalankan `php artisan db:seed` setelah migrasi. Akun admin lokal bawaan: `admin@linkit.local` dengan kata sandi `password`. Ganti kata sandi setelah masuk. Seeder admin hanya berjalan pada lingkungan `local` dan `testing`; atur `LOCAL_ADMIN_PASSWORD` sebelum menjalankan seeder untuk memilih kata sandi lokal lain.
