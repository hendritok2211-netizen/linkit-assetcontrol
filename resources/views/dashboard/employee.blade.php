@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-header">
        <div>
            <h2 class="page-header-title">Halo, {{ auth()->user()->name }} 👋</h2>
            <p class="page-header-sub">Berikut status perangkat yang sedang menjadi tanggung jawab Anda.</p>
        </div>
        <a class="button button-outline" href="{{ route('profile.edit') }}">⚙ Pengaturan Akun</a>
    </div>

    <div class="kpi-grid kpi-grid-3">
        <article class="kpi-card">
            <div class="kpi-icon kpi-icon-blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            </div>
            <div class="kpi-body">
                <span class="kpi-label">LAPTOP AKTIF</span>
                <strong class="kpi-value">{{ $activeLoans->count() }}</strong>
                <span class="kpi-sub">Perangkat yang sedang Anda gunakan</span>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon kpi-icon-emerald">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="kpi-body">
                <span class="kpi-label">SUDAH DIKEMBALIKAN</span>
                <strong class="kpi-value">{{ $loanHistory->count() }}</strong>
                <span class="kpi-sub">Transaksi terakhir tercatat</span>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon kpi-icon-slate">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <div class="kpi-body">
                <span class="kpi-label">DIVISI</span>
                <strong class="kpi-value kpi-value-sm">{{ auth()->user()->division?->name ?? 'Belum diatur' }}</strong>
                <span class="kpi-sub">Divisi akun Anda</span>
            </div>
        </article>
    </div>

    <section class="card mt-5">
        <div class="card-header">
            <div>
                <h3 class="card-title">Laptop yang Sedang Dipinjam</h3>
                <p class="card-sub">Ajukan pengembalian kepada admin saat perangkat siap diserahkan.</p>
            </div>
            <a href="{{ route('loans.mine') }}" class="text-link">Riwayat lengkap →</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>KODE ASET</th>
                        <th>PERANGKAT</th>
                        <th>TANGGAL PINJAM</th>
                        <th>STATUS</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activeLoans as $loan)
                        <tr>
                            <td><strong>{{ $loan->laptop->asset_code }}</strong></td>
                            <td>
                                {{ $loan->laptop->brand }} {{ $loan->laptop->model }}
                                <small>SN: {{ $loan->laptop->serial_number }}</small>
                            </td>
                            <td>{{ $loan->checked_out_at->format('d M Y') }}</td>
                            <td>
                                <span class="badge {{ $loan->status === 'return_requested' ? 'badge-amber' : 'badge-blue' }}">
                                    {{ $loan->status === 'return_requested' ? 'Menunggu pemeriksaan' : 'Dipinjam' }}
                                </span>
                            </td>
                            <td>
                                @if ($loan->status === 'active')
                                    <form method="POST" action="{{ route('loans.request-return', $loan) }}" class="inline-form">
                                        @csrf
                                        <input type="text" name="return_notes" placeholder="Catatan kondisi (opsional)" aria-label="Catatan pengembalian">
                                        <button class="button button-small button-primary" type="submit">Ajukan kembali</button>
                                    </form>
                                @else
                                    <span class="muted">Menunggu admin</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">Tidak ada laptop yang sedang dipinjam.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card mt-5">
        <div class="card-header">
            <div>
                <h3 class="card-title">Riwayat Pengembalian</h3>
                <p class="card-sub">Transaksi yang sudah selesai.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>KODE ASET</th>
                        <th>PERANGKAT</th>
                        <th>TANGGAL KEMBALI</th>
                        <th>KONDISI KEMBALI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loanHistory as $loan)
                        <tr>
                            <td>{{ $loan->laptop->asset_code }}</td>
                            <td>{{ $loan->laptop->brand }} {{ $loan->laptop->model }}</td>
                            <td>{{ $loan->returned_at?->format('d M Y') }}</td>
                            <td>{{ ['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'][$loan->condition_in] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state">Belum ada riwayat pengembalian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
