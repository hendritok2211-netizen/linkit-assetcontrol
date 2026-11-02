@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h2 class="page-header-title">Ringkasan Inventaris</h2>
            <p class="page-header-sub">Pantau kondisi dan pergerakan perangkat PT LinkIT.</p>
        </div>
        <a class="button button-primary" href="{{ route('loans.index') }}">＋ Catat Peminjaman</a>
    </div>

    {{-- Stat Cards --}}
    <div class="kpi-grid">
        <article class="kpi-card">
            <div class="kpi-icon kpi-icon-slate">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            </div>
            <div class="kpi-body">
                <span class="kpi-label">TOTAL LAPTOP</span>
                <strong class="kpi-value">{{ $totalLaptops }}</strong>
                <span class="kpi-sub">Seluruh aset terdaftar</span>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon kpi-icon-emerald">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="kpi-body">
                <span class="kpi-label">TERSEDIA</span>
                <strong class="kpi-value">{{ $availableLaptops }}</strong>
                <span class="kpi-sub">Siap dipinjam</span>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon kpi-icon-blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="kpi-body">
                <span class="kpi-label">SEDANG DIPAKAI</span>
                <strong class="kpi-value">{{ $borrowedLaptops }}</strong>
                <span class="kpi-sub">Aktif dipinjam karyawan</span>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon kpi-icon-amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </div>
            <div class="kpi-body">
                <span class="kpi-label">PERAWATAN</span>
                <strong class="kpi-value">{{ $maintenanceLaptops }}</strong>
                <span class="kpi-sub">Perlu pengecekan teknis</span>
            </div>
        </article>
    </div>

    {{-- Attention Banner --}}
    @if ($pendingReturns > 0 || $pendingEmployees > 0)
        <div class="attention-banner">
            <div class="attention-banner-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            </div>
            <div class="attention-banner-body">
                <strong>Perlu Tindakan</strong>
                <span>
                    @if ($pendingReturns > 0)
                        <a href="{{ route('loans.index') }}">{{ $pendingReturns }} pengembalian menunggu verifikasi</a>
                    @endif
                    @if ($pendingReturns > 0 && $pendingEmployees > 0) · @endif
                    @if ($pendingEmployees > 0)
                        <a href="{{ route('employees.index') }}">{{ $pendingEmployees }} akun karyawan belum disetujui</a>
                    @endif
                </span>
            </div>
        </div>
    @endif

    {{-- Bottom Grid --}}
    <div class="dash-grid">
        {{-- Status Chart --}}
        <section class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Status Perangkat</h3>
                    <p class="card-sub">Sebaran kondisi inventaris saat ini</p>
                </div>
            </div>
            <div class="status-chart">
                @php
                    $bars = [
                        ['label' => 'Tersedia', 'count' => $availableLaptops, 'color' => 'bg-emerald-500', 'badge' => 'badge-green'],
                        ['label' => 'Dipinjam', 'count' => $borrowedLaptops, 'color' => 'bg-blue-500', 'badge' => 'badge-blue'],
                        ['label' => 'Perawatan', 'count' => $maintenanceLaptops, 'color' => 'bg-amber-500', 'badge' => 'badge-amber'],
                    ];
                @endphp
                @foreach ($bars as $bar)
                    <div class="status-bar-row">
                        <div class="status-bar-meta">
                            <span class="status-bar-label">{{ $bar['label'] }}</span>
                            <span class="status-bar-count">{{ $bar['count'] }}</span>
                        </div>
                        <div class="status-bar-track">
                            <div class="status-bar-fill {{ $bar['color'] }}"
                                 style="width: {{ $totalLaptops ? round($bar['count'] / $totalLaptops * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Recent Activity --}}
        <section class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Aktivitas Terbaru</h3>
                    <p class="card-sub">Catatan perubahan terakhir dalam sistem</p>
                </div>
            </div>
            <div class="activity-feed">
                @forelse ($recentActivity as $activity)
                    <div class="activity-row">
                        <div class="activity-dot"></div>
                        <div class="activity-content">
                            <p class="activity-desc">{{ $activity->description }}</p>
                            <span class="activity-meta">{{ $activity->user?->name ?? 'Sistem' }} · {{ $activity->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <p class="empty-hint">Aktivitas akan muncul saat aset mulai dikelola.</p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Recent Loans Table --}}
    <section class="card mt-5">
        <div class="card-header">
            <div>
                <h3 class="card-title">Peminjaman Terbaru</h3>
                <p class="card-sub">Perangkat yang terakhir diserahkan</p>
            </div>
            <a class="text-link" href="{{ route('loans.index') }}">Lihat semua →</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>KODE ASET</th>
                        <th>PEMEGANG</th>
                        <th>TANGGAL PINJAM</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentLoans as $loan)
                        <tr>
                            <td>
                                <strong>{{ $loan->laptop->asset_code }}</strong>
                                <small>{{ $loan->laptop->brand }} {{ $loan->laptop->model }}</small>
                            </td>
                            <td>{{ $loan->user->name }}</td>
                            <td>{{ $loan->checked_out_at->format('d M Y') }}</td>
                            <td>
                                <span class="badge {{ $loan->status === 'return_requested' ? 'badge-amber' : ($loan->status === 'returned' ? 'badge-green' : 'badge-blue') }}">
                                    {{ ['active' => 'Dipinjam', 'return_requested' => 'Menunggu kembali', 'returned' => 'Dikembalikan'][$loan->status] ?? $loan->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state">Belum ada transaksi peminjaman.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
