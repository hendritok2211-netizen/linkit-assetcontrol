<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Masuk · LinkIT 360 Asset Control</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="auth-page" style="--login-background: url('{{ asset('images/login-bg.jpg') }}')">
        <main class="auth-card">
            <a class="brand auth-brand" href="{{ route('login') }}"><span class="brand-logo-frame"><img class="brand-logo" src="{{ asset('images/linkit-logo.png') }}" alt="LinkIT 360"></span><span class="brand-caption">ASSET CONTROL</span></a>
            <div class="auth-intro"><span class="eyebrow">PT LINKIT</span><h1>Selamat datang</h1><p>Masuk untuk memantau inventaris dan peminjaman laptop.</p></div>
            @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('login.store') }}" class="form-stack">
                @csrf
                <label>Email kerja<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
                <label>Kata sandi<input type="password" name="password" autocomplete="current-password" required></label>
                <label class="check-row"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                <button class="button button-primary button-full" type="submit">Masuk ke aplikasi <span>→</span></button>
            </form>
            <p class="auth-foot">Belum punya akun? <a href="{{ route('register') }}">Daftar sebagai karyawan</a></p>
        </main>
        <div class="auth-decoration"><span>INVENTARIS</span><strong>Terjaga.<br>Tercatat.<br>Terkendali.</strong><p>Monitoring aset laptop perusahaan dalam satu sistem.</p></div>
    </body>
</html>
