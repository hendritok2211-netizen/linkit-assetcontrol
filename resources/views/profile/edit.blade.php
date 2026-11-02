@extends('layouts.app')

@section('title', 'Akun Saya')

@section('content')
    <div class="page-header">
        <div>
            <h2 class="page-header-title">Pengaturan Akun</h2>
            <p class="page-header-sub">Informasi akun dan keamanan akses Anda.</p>
        </div>
    </div>

    <div class="two-column">
        <section class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Informasi Karyawan</h3>
                    <p class="card-sub">Hubungi admin jika data perlu diperbarui.</p>
                </div>
            </div>
            <div class="profile-avatar-row">
                <div class="profile-avatar-large">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <div>
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->isAdmin() ? 'Administrator' : 'Karyawan' }}</span>
                </div>
            </div>
            <dl class="profile-details">
                <dt>Nama</dt><dd>{{ $user->name }}</dd>
                <dt>Email kerja</dt><dd>{{ $user->email }}</dd>
                <dt>Divisi</dt><dd>{{ $user->division?->name ?? '—' }}</dd>
                <dt>Peran</dt><dd>{{ $user->isAdmin() ? 'Administrator' : 'Karyawan' }}</dd>
            </dl>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Ganti Kata Sandi</h3>
                    <p class="card-sub">Gunakan minimal 8 karakter.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('profile.password') }}" class="form-stack">
                @csrf
                @method('PUT')
                <label>Kata sandi saat ini
                    <input type="password" name="current_password" autocomplete="current-password" required>
                </label>
                <label>Kata sandi baru
                    <input type="password" name="password" autocomplete="new-password" minlength="8" required>
                </label>
                <label>Ulangi kata sandi baru
                    <input type="password" name="password_confirmation" autocomplete="new-password" required>
                </label>
                <div class="form-actions">
                    <button class="button button-primary" type="submit">Simpan Kata Sandi</button>
                </div>
            </form>
        </section>
    </div>
@endsection
