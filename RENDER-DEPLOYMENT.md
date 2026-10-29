# 🚀 Panduan Deploy Laravel ke Render.com

## 📋 Langkah-langkah Deploy (Super Mudah!)

### 1️⃣ Buat Akun Render

1. Buka https://render.com
2. Klik **"Get Started"** atau **"Sign Up"**
3. Pilih **"Sign in with GitHub"**
4. Authorize Render untuk akses GitHub Anda
5. ✅ **Akun langsung jadi!**

---

### 2️⃣ Buat Web Service Baru

1. Di Dashboard Render, klik **"New +"** (pojok kanan atas)
2. Pilih **"Web Service"**
3. **Connect** repository Anda: `hendritok2211-netizen/linkit-assetcontrol`
4. Klik **"Connect"** di sebelah repository

---

### 3️⃣ Konfigurasi Web Service

Isi form seperti ini:

#### Basic Settings:
- **Name:** `linkit-assetcontrol` (atau terserah Anda)
- **Region:** `Singapore` (paling dekat) atau `Oregon`
- **Branch:** `main`
- **Runtime:** `PHP`

#### Build & Deploy:
- **Build Command:**
```bash
composer install --no-dev --optimize-autoloader && npm ci && npm run build && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

- **Start Command:**
```bash
php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
```

#### Instance Type:
- Pilih **"Free"** (sudah cukup untuk start)

---

### 4️⃣ Tambahkan Environment Variables

Scroll ke bagian **"Environment Variables"**, klik **"Add Environment Variable"**:

```env
APP_NAME=LinkIt Asset Control
APP_KEY=base64:K73Vf0+KDQg/Iu85X5XGvJX0OPD2Jr5eZa0XXKgB42Y=
APP_ENV=production
APP_DEBUG=false
APP_URL=https://linkit-assetcontrol.onrender.com

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Logs
LOG_CHANNEL=stderr
LOG_LEVEL=error

# Mail (opsional)
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@linkit.com
MAIL_FROM_NAME=LinkIt Asset Control
```

**⚠️ PENTING:** Ganti `APP_URL` dengan nama service Anda nanti!

Klik **"Add"** untuk setiap variable, atau klik **"Add from .env"** lalu paste semua sekaligus.

---

### 5️⃣ Buat Database MySQL

1. Klik **"New +"** lagi
2. Pilih **"PostgreSQL"** (Render tidak support MySQL gratis)

**ATAU gunakan eksternal MySQL:**

#### Opsi A: PlanetScale (MySQL Gratis)
1. Buka https://planetscale.com
2. Sign up dengan GitHub
3. Create database baru
4. Dapatkan connection string
5. Tambahkan ke Render environment variables

#### Opsi B: Railway MySQL
1. Buka https://railway.app (login dulu kalau bisa)
2. Buat project baru, add MySQL saja
3. Dapatkan credentials
4. Tambahkan ke Render

#### Opsi C: FreeSQLDatabase.com
1. Buka https://www.freesqldatabase.com
2. Sign up gratis
3. Create MySQL database
4. Dapatkan credentials

---

### 6️⃣ Tambahkan Database Credentials

Setelah dapat database, tambahkan variables ini di Render:

```env
DB_CONNECTION=mysql
DB_HOST=your-db-host.com
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

---

### 7️⃣ Deploy!

1. Scroll ke bawah
2. Klik **"Create Web Service"**
3. Render akan mulai build & deploy
4. Tunggu 3-5 menit sampai status **"Live"** ✅

---

### 8️⃣ Update APP_URL

Setelah deploy selesai:

1. Copy URL yang dihasilkan (misal: `linkit-assetcontrol.onrender.com`)
2. Klik **"Environment"** di sidebar
3. Edit `APP_URL` dengan URL tersebut: `https://linkit-assetcontrol.onrender.com`
4. Klik **"Save Changes"**
5. Render akan auto-redeploy

---

## 👤 Buat Admin User Pertama

### Cara 1: Menggunakan Tinker (Manual)

1. Di Dashboard Render, buka service Anda
2. Klik **"Shell"** di menu kiri
3. Jalankan:

```bash
php artisan tinker
```

Lalu:

```php
\App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@linkit.com',
    'password' => bcrypt('password123'),
    'role' => 'admin',
    'nik' => 'ADM001',
    'phone' => '081234567890',
    'email_verified_at' => now(),
]);
```

Ketik `exit` untuk keluar.

### Cara 2: Buat Seeder (Otomatis)

Atau bisa jalankan seeder kalau ada:

```bash
php artisan db:seed
```

---

## 🔍 Monitoring & Logs

### View Logs:
- Klik service → **"Logs"** di sidebar
- Real-time logs akan muncul di sini

### Metrics:
- Dashboard menampilkan CPU, Memory, Bandwidth

### Shell Access:
- Klik **"Shell"** untuk akses command line
- Bisa run artisan commands langsung

---

## 🔄 Auto Deploy

Setiap push ke branch `main` di GitHub:
- ✅ Render otomatis detect changes
- ✅ Auto build & deploy
- ✅ Zero downtime deployment

---

## 💰 Pricing Render

### Free Tier:
- ✅ 750 jam/bulan (cukup untuk 1 app 24/7)
- ✅ Auto-sleep setelah 15 menit tidak ada traffic
- ✅ Auto-wake saat ada request (cold start ~30 detik)
- ⚠️ PostgreSQL gratis, MySQL perlu eksternal

### Starter ($7/bulan):
- ✅ No auto-sleep
- ✅ Faster
- ✅ Custom domain

---

## 🆘 Troubleshooting

### Build Failed

**Error:** `composer: command not found`
- Pastikan Runtime = **PHP**

**Error:** `npm: command not found`
- Tambahkan di build command: `apt-get install -y nodejs npm &&`

### Migration Error

**Error:** `could not find driver`
- Tambahkan environment variable: `DB_CONNECTION=mysql`

**Error:** `Access denied`
- Check database credentials di Environment Variables

### 500 Error

1. Check **Logs** di sidebar
2. Set `APP_DEBUG=true` sementara untuk debugging
3. Check `APP_KEY` sudah di-set
4. Pastikan migrations sudah jalan

### Assets Not Loading

1. Run di Shell: `php artisan storage:link`
2. Pastikan `npm run build` sukses
3. Check `APP_URL` sudah benar

### Cold Start (First Request Slow)

- Normal di Free tier
- Setelah sleep 15 menit, butuh ~30 detik untuk wake up
- Upgrade ke Starter plan untuk no-sleep

---

## 📊 Database Options Comparison

| Provider | Type | Free Tier | Speed | Setup |
|----------|------|-----------|-------|-------|
| **PlanetScale** | MySQL | ✅ 5GB | ⚡⚡⚡ | Easy |
| **Railway** | MySQL | $5 credit | ⚡⚡⚡ | Easy |
| **FreeSQLDatabase** | MySQL | ✅ 5MB | ⚡ | Easy |
| **Render PostgreSQL** | PostgreSQL | ✅ 1GB | ⚡⚡⚡ | Easiest |

**Rekomendasi:** 
- **Development:** FreeSQLDatabase atau Render PostgreSQL
- **Production:** PlanetScale atau Railway MySQL

---

## 🎯 Alternatif: Deploy dengan Docker (Advanced)

Kalau mau lebih kontrol, bisa pakai Dockerfile:

```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-dev

# Expose port
EXPOSE 8080

# Start application
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8080
```

---

## 📱 Access Your App

URL Format: `https://[your-service-name].onrender.com`

**Default Login:**
- Email: `admin@linkit.com`
- Password: `password123`

**⚠️ GANTI PASSWORD SETELAH LOGIN PERTAMA!**

---

## 🔐 Security Checklist

Before going production:

- [ ] Set `APP_DEBUG=false`
- [ ] Generate new `APP_KEY`
- [ ] Change default admin password
- [ ] Set up proper database (not free tier)
- [ ] Configure mail service
- [ ] Enable HTTPS (Render auto)
- [ ] Set up monitoring

---

## 📱 Running Artisan Commands

Via Render Shell:

```bash
# Clear cache
php artisan cache:clear

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Create user
php artisan tinker

# View routes
php artisan route:list

# View config
php artisan config:show
```

---

## 🔗 Useful Links

- [Render Documentation](https://render.com/docs)
- [Laravel Deployment Guide](https://laravel.com/docs/deployment)
- [PlanetScale MySQL](https://planetscale.com)
- [Render Community Forum](https://community.render.com)

---

## 💡 Tips & Tricks

### Speed Up Build
Tambahkan di `.renderignore`:
```
tests/
.git/
.github/
*.md
.kiro/
.claude/
node_modules/
```

### Persistent Storage
Untuk file uploads, gunakan:
- S3 (AWS)
- Cloudinary
- DigitalOcean Spaces

### Background Jobs
Tambah worker service terpisah untuk queue processing

### Scheduled Tasks
Gunakan Render Cron Jobs untuk `php artisan schedule:run`

---

Selamat! Aplikasi Laravel Anda siap live di Render! 🎉

Jika ada error, cek Logs dulu, 90% solusi ada di sana! 🔍
