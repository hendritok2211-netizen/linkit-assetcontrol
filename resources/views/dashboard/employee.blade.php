@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="welcome-row"><div><h2>Halo, {{ auth()->user()->name }} 👋</h2><p>Berikut status perangkat yang sedang menjadi tanggung jawab Anda.</p></div><a class="button button-outline" href="{{ route('profile.edit') }}">Pengaturan akun</a></div>
    <div class="stat-grid stat-grid-employee">
        <article class="stat-card"><span class="stat-icon icon-brand">▣</span><span class="stat-label">LAPTOP AKTIF</span><strong>{{ $activeLoans->count() }}</strong><small>Perangkat yang sedang Anda gunakan</small></article>
        <article class="stat-card"><span class="stat-icon icon-green">✓</span><span class="stat-label">SUDAH DIKEMBALIKAN</span><strong>{{ $loanHistory->count() }}</strong><small>Transaksi terakhir tercatat</small></article>
        <article class="stat-card"><span class="stat-icon icon-blue">⌂</span><span class="stat-label">DIVISI</span><strong class="stat-text">{{ auth()->user()->division?->name ?? 'Belum diatur' }}</strong><small>Divisi akun Anda</small></article>
    </div>
    <section class="panel">
        <div class="panel-heading"><div><h2>Laptop yang sedang dipinjam</h2><p>Ajukan pengembalian kepada admin saat perangkat siap diserahkan.</p></div><a href="{{ route('loans.mine') }}" class="text-link">Riwayat lengkap →</a></div>
        <div class="table-wrap"><table><thead><tr><th>KODE ASET</th><th>PERANGKAT</th><th>TANGGAL PINJAM</th><th>STATUS</th><th>AKSI</th></tr></thead><tbody>
            @forelse ($activeLoans as $loan)
                <tr><td><strong>{{ $loan->laptop->asset_code }}</strong></td><td>{{ $loan->laptop->brand }} {{ $loan->laptop->model }}<small>SN: {{ $loan->laptop->serial_number }}</small></td><td>{{ $loan->checked_out_at->format('d M Y') }}</td><td><span class="badge {{ $loan->status === 'return_requested' ? 'badge-amber' : 'badge-blue' }}">{{ $loan->status === 'return_requested' ? 'Menunggu pemeriksaan' : 'Dipinjam' }}</span></td><td>
                    @if ($loan->status === 'active')
                        <form method="POST" action="{{ route('loans.request-return', $loan) }}" class="inline-form">@csrf<input type="text" name="return_notes" placeholder="Catatan kondisi (opsional)" aria-label="Catatan pengembalian"><button class="button button-small button-primary" type="submit">Ajukan kembali</button></form>
                    @else
                        <span class="muted">Menunggu admin</span>
                    @endif
                </td></tr>
            @empty
                <tr><td colspan="5" class="empty-state">Tidak ada laptop yang sedang dipinjam.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
    <section class="panel">
        <div class="panel-heading"><div><h2>Riwayat pengembalian</h2><p>Transaksi yang sudah selesai.</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>KODE ASET</th><th>PERANGKAT</th><th>TANGGAL KEMBALI</th><th>KONDISI KEMBALI</th></tr></thead><tbody>
            @forelse ($loanHistory as $loan)
                <tr><td>{{ $loan->laptop->asset_code }}</td><td>{{ $loan->laptop->brand }} {{ $loan->laptop->model }}</td><td>{{ $loan->returned_at?->format('d M Y') }}</td><td>{{ ['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'][$loan->condition_in] ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="4" class="empty-state">Belum ada riwayat pengembalian.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
@endsection
