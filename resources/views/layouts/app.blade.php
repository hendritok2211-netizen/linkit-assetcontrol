<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Dashboard') · LinkIT 360</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="app-shell">
            {{-- Sidebar --}}
            <aside class="sidebar" id="app-sidebar">
                <a class="sidebar-brand" href="{{ route('dashboard') }}">
                    <span class="sidebar-brand-logo"><img src="{{ asset('images/linkit-logo.png') }}" alt="LinkIT 360"></span>
                    <span class="sidebar-brand-caption">ASSET CONTROL</span>
                </a>

                <div class="sidebar-section-label">MENU UTAMA</div>

                <nav class="sidebar-nav">
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                        Dashboard
                    </a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('laptops.index') }}" class="{{ request()->routeIs('laptops.*') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                            Data Laptop
                        </a>
                        <a href="{{ route('loans.index') }}" class="{{ request()->routeIs('loans.index') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                            Peminjaman
                        </a>
                        <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            Laporan
                        </a>
                        <a href="{{ route('laptops.scan') }}" class="{{ request()->routeIs('laptops.scan') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><line x1="7" y1="12" x2="17" y2="12"/></svg>
                            Verifikasi QR
                        </a>
                        <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            Karyawan
                        </a>
                        <a href="{{ route('divisions.index') }}" class="{{ request()->routeIs('divisions.*') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                            Peta Divisi
                        </a>
                    @else
                        <a href="{{ route('loans.mine') }}" class="{{ request()->routeIs('loans.mine') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                            Laptop Saya
                        </a>
                    @endif
                </nav>

                <div class="sidebar-footer">
                    <a href="{{ route('profile.edit') }}" class="sidebar-profile-link">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 1 0 20 12h-1"/></svg>
                        Pengaturan Akun
                    </a>
                    <div class="sidebar-user">
                        <div class="sidebar-user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                        <div class="sidebar-user-info">
                            <strong>{{ auth()->user()->name }}</strong>
                            <small>{{ auth()->user()->isAdmin() ? 'Administrator' : (auth()->user()->division?->name ?? 'Karyawan') }}</small>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="sidebar-logout-btn" type="submit" aria-label="Keluar" title="Keluar">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>
            <button class="sidebar-overlay" type="button" aria-label="Tutup menu" aria-hidden="true" tabindex="-1"></button>

            {{-- Main Panel --}}
            <main class="main-panel">
                <header class="topbar">
                    <div class="topbar-left">
                        <button class="mobile-menu-button" type="button" aria-label="Buka menu" aria-controls="app-sidebar" aria-expanded="false">
                            <span></span><span></span><span></span>
                        </button>
                        <div class="topbar-breadcrumb">
                            <span class="topbar-eyebrow">PT LINKIT · ASSET MANAGEMENT</span>
                            <h1 class="topbar-title">@yield('title', 'Dashboard')</h1>
                        </div>
                    </div>
                    <div class="topbar-date">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</div>
                </header>

                <section class="page-content">
                    @if (session('status'))
                        <div class="alert alert-success" role="status">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            {{ session('status') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-error" role="alert">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            <div>
                                <strong>Periksa kembali:</strong>
                                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        </div>
                    @endif
                    @yield('content')
                </section>
            </main>
        </div>
    </body>
</html>
