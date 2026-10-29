@extends('layouts.app')

@section('title', 'Akun Saya')

@section('content')
    <div class="welcome-row"><div><h2>Pengaturan akun</h2><p>Informasi akun dan keamanan akses Anda.</p></div></div>
    <div class="two-column">
        <section class="panel"><div class="panel-heading"><div><h2>Informasi karyawan</h2><p>Hubungi admin jika data perlu diperbarui.</p></div></div>
            <dl class="profile-details"><dt>Nama</dt><dd>{{ $user->name }}</dd><dt>Email kerja</dt><dd>{{ $user->email }}</dd><dt>Divisi</dt><dd>{{ $user->division?->name ?? '—' }}</dd><dt>Peran</dt><dd>{{ $user->isAdmin() ? 'Administrator' : 'Karyawan' }}</dd></dl>
        </section>
        <section class="panel"><div class="panel-heading"><div><h2>Ganti kata sandi</h2><p>Gunakan minimal 8 karakter.</p></div></div>
            <form method="POST" action="{{ route('profile.password') }}" class="form-stack">@csrf @method('PUT')
                <label>Kata sandi saat ini<input type="password" name="current_password" autocomplete="current-password" required></label>
                <label>Kata sandi baru<input type="password" name="password" autocomplete="new-password" minlength="8" required></label>
                <label>Ulangi kata sandi baru<input type="password" name="password_confirmation" autocomplete="new-password" required></label>
                <button class="button button-primary" type="submit">Simpan kata sandi</button>
            </form>
        </section>
    </div>
@endsection
