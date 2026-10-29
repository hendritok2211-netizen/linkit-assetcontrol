<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Dashboard') · LinkIT 360 Asset Control</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="app-shell">
            <aside class="sidebar" id="app-sidebar">
                <a class="brand" href="{{ route('dashboard') }}">
                    <span class="brand-logo-frame"><img class="brand-logo" src="{{ asset('images/linkit-logo.png') }}" alt="LinkIT 360"></span>
                    <span class="brand-caption">ASSET CONTROL</span>
                </a>

                <div class="nav-caption">MENU UTAMA</div>
                <nav class="side-nav">
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><span>▦</span> Dashboard</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('laptops.index') }}" class="{{ request()->routeIs('laptops.*') ? 'active' : '' }}"><span>▣</span> Data Laptop</a>
                        <a href="{{ route('loans.index') }}" class="{{ request()->routeIs('loans.index') ? 'active' : '' }}"><span>⇄</span> Peminjaman</a>
                        <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}"><span>▤</span> Laporan</a>
                        <a href="{{ route('laptops.scan') }}" class="{{ request()->routeIs('laptops.scan') ? 'active' : '' }}"><span>▧</span> Verifikasi QR</a>
                        <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}"><span>♙</span> Karyawan</a>
                        <a href="{{ route('divisions.index') }}" class="{{ request()->routeIs('divisions.*') ? 'active' : '' }}"><span>▤</span> Divisi</a>
                    @else
                        <a href="{{ route('loans.mine') }}" class="{{ request()->routeIs('loans.mine') ? 'active' : '' }}"><span>▣</span> Laptop Saya</a>
                    @endif
                </nav>

                <div class="sidebar-bottom">
                    <a href="{{ route('profile.edit') }}" class="profile-link">⚙ &nbsp; Akun Saya</a>
                    <div class="sidebar-user">
                        <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="user-details"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->isAdmin() ? 'Administrator' : (auth()->user()->division?->name ?? 'Karyawan') }}</small></span>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-button" type="submit" aria-label="Keluar">↗</button></form>
                    </div>
                </div>
            </aside>
            <button class="sidebar-overlay" type="button" aria-label="Tutup menu navigasi" aria-hidden="true" tabindex="-1"></button>

            <main class="main-panel">
                <header class="topbar">
                    <div class="topbar-heading">
                        <button class="mobile-menu-button" type="button" aria-label="Buka menu navigasi" aria-controls="app-sidebar" aria-expanded="false">
                            <span></span><span></span><span></span>
                        </button>
                        <div>
                            <span class="eyebrow">PT LINKIT · ASSET MANAGEMENT</span>
                            <h1>@yield('title', 'Dashboard')</h1>
                        </div>
                    </div>
                    <div class="topbar-date">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</div>
                </header>

                <section class="page-content">
                    @if (session('status'))
                        <div class="alert alert-success" role="status">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-error" role="alert">
                            <strong>Periksa kembali:</strong>
                            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif
                    @yield('content')
                </section>
            </main>
        </div>
    </body>
</html>
