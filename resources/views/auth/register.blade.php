<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Daftar · LinkIT 360 Asset Control</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="auth-page auth-register">
        <main class="auth-card">
            <a class="brand auth-brand" href="{{ route('login') }}"><span class="brand-logo-frame"><img class="brand-logo" src="{{ asset('images/linkit-logo.png') }}" alt="LinkIT 360"></span><span class="brand-caption">ASSET CONTROL</span></a>
            <div class="auth-intro"><span class="eyebrow">AKUN KARYAWAN</span><h1>Buat akun</h1><p>Akun baru perlu disetujui admin sebelum dapat digunakan.</p></div>
            @if ($errors->any())<div class="alert alert-error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route('register.store') }}" class="form-stack">
                @csrf
                <label>Nama lengkap<input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required></label>
                <label>Email kerja<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
                <label>Divisi<select name="division_id" required><option value="">Pilih divisi</option>@foreach ($divisions as $division)<option value="{{ $division->id }}" @selected(old('division_id') == $division->id)>{{ $division->name }}</option>@endforeach</select></label>
                <label>Kata sandi<input type="password" name="password" autocomplete="new-password" minlength="8" required><small>Minimal 8 karakter.</small></label>
                <label>Ulangi kata sandi<input type="password" name="password_confirmation" autocomplete="new-password" required></label>
                <button class="button button-primary button-full" type="submit">Ajukan pendaftaran <span>→</span></button>
            </form>
            <p class="auth-foot">Sudah punya akun? <a href="{{ route('login') }}">Kembali masuk</a></p>
        </main>
        <div class="auth-decoration"><span>PT LINKIT</span><strong>Setiap aset<br>punya cerita.</strong><p>Mulai kelola perangkat kerja dengan pencatatan yang jelas.</p></div>
    </body>
</html>
