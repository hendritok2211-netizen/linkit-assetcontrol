# 🚀 Panduan Deploy Laravel ke Railway

## 📋 Langkah-langkah Deploy

### 1️⃣ Buat Akun Railway

1. Buka https://railway.app
2. Klik **"Start a New Project"** atau **"Login with GitHub"**
3. Authorize Railway untuk akses GitHub Anda

---

### 2️⃣ Buat Project Baru

1. Di Dashboard Railway, klik **"New Project"**
2. Pilih **"Deploy from GitHub repo"**
3. Pilih repository: `hendritok2211-netizen/linkit-assetcontrol`
4. Klik **"Deploy Now"**

---

### 3️⃣ Tambahkan MySQL Database

1. Di project Railway, klik **"+ New"**
2. Pilih **"Database"**
3. Pilih **"Add MySQL"**
4. Railway akan otomatis membuat database dan environment variables:
   - `MYSQLHOST`
   - `MYSQLPORT`
   - `MYSQLDATABASE`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`

---

### 4️⃣ Set Environment Variables untuk Laravel

Klik service Laravel Anda → **Variables** tab, tambahkan:

```env
APP_NAME=LinkIt Asset Control
APP_KEY=base64:K73Vf0+KDQg/Iu85X5XGvJX0OPD2Jr5eZa0XXKgB42Y=
APP_ENV=production
APP_DEBUG=false
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}

# Database (sudah auto dari MySQL service)
DB_CONNECTION=mysql
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_DATABASE=${{MYSQLDATABASE}}
DB_USERNAME=${{MYSQLUSER}}
DB_PASSWORD=${{MYSQLPASSWORD}}

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Logs
LOG_CHANNEL=stderr
LOG_LEVEL=error

# Mail (opsional, set nanti jika perlu)
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@linkit.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Catatan:**
- `APP_KEY` sudah di-generate: `base64:K73Vf0+KDQg/Iu85X5XGvJX0OPD2Jr5eZa0XXKgB42Y=`
- `${{RAILWAY_PUBLIC_DOMAIN}}` akan otomatis diisi Railway
- Database variables akan otomatis tersedia setelah add MySQL

---

### 5️⃣ Generate Public Domain

1. Di service Laravel → **Settings** tab
2. Scroll ke **Networking**
3. Klik **"Generate Domain"**
4. Railway akan membuat URL: `https://your-app.up.railway.app`

---

### 6️⃣ Update APP_URL

Setelah domain di-generate:

1. Copy domain yang dihasilkan (misal: `linkit-assetcontrol-production.up.railway.app`)
2. Update variable `APP_URL` dengan domain tersebut
3. Railway akan otomatis redeploy

---

### 7️⃣ Connect Database ke Laravel Service

1. Klik MySQL service
2. Klik **"Connect"**
3. Pilih Laravel service Anda
4. Railway akan otomatis link environment variables

---

### 8️⃣ Monitoring & Logs

- **Deployment Logs:** Klik service → **Deployments** → Pilih deployment terbaru
- **Application Logs:** Klik **"View Logs"** untuk melihat real-time logs
- **Database:** Klik MySQL service → **Data** untuk melihat tables

---

## ✅ Setelah Deploy Berhasil

### Buat User Admin Pertama

1. Klik MySQL service → **Data** tab
2. Atau gunakan Railway CLI:

```bash
railway login
railway link
railway run php artisan tinker
```

Lalu jalankan:

```php
User::create([
    'name' => 'Admin',
    'email' => 'admin@linkit.com',
    'password' => bcrypt('password123'),
    'role' => 'admin',
    'nik' => 'ADM001',
    'phone' => '081234567890',
]);
```

---

## 🔄 Auto Deploy dari GitHub

Setiap kali Anda push ke branch `main`, Railway akan otomatis:
1. Pull code terbaru
2. Run `composer install`
3. Run `npm ci && npm run build`
4. Run migrations
5. Cache config, routes, views
6. Deploy aplikasi

---

## 💡 Tips

### Melihat Database
```bash
railway connect mysql
```

### Run Artisan Commands
```bash
railway run php artisan migrate
railway run php artisan db:seed
railway run php artisan cache:clear
```

### Check Environment Variables
```bash
railway variables
```

---

## 📊 Monitoring Resources

Dashboard Railway menampilkan:
- **CPU Usage**
- **Memory Usage**
- **Network Traffic**
- **Build Time**
- **Deploy History**

---

## 💰 Pricing

**Starter Plan (Gratis):**
- $5 credit/bulan
- Cukup untuk development & small apps
- Auto-sleep setelah tidak digunakan

**Developer Plan ($5/bulan):**
- $5 credit + $5/bulan usage
- No auto-sleep
- Priority support

---

## 🆘 Troubleshooting

### Build Failed
- Check **Build Logs** di Railway dashboard
- Pastikan `composer.json` dan `package.json` valid

### Migration Error
- Pastikan MySQL service sudah connected
- Check database credentials di Variables tab

### 500 Error
- Check **Application Logs**
- Set `APP_DEBUG=true` sementara untuk debugging (jangan lupa ubah ke `false` lagi)

### Asset Not Loading
- Run: `railway run php artisan storage:link`
- Pastikan `npm run build` sukses

---

## 📱 Access Your App

URL Anda: `https://[your-generated-domain].up.railway.app`

**Default Login:**
- Email: `admin@linkit.com`
- Password: `password123`

**⚠️ Jangan lupa ganti password setelah login pertama!**

---

## 🔗 Links

- [Railway Documentation](https://docs.railway.app/)
- [Laravel Deployment Best Practices](https://laravel.com/docs/deployment)
- [Railway CLI](https://docs.railway.app/develop/cli)

---

Selamat! Aplikasi Laravel Anda sudah live di Railway! 🎉
