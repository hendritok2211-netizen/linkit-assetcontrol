@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="welcome-row">
        <div><h2>Ringkasan inventaris</h2><p>Pantau kondisi dan pergerakan perangkat PT LinkIT.</p></div>
        <a class="button button-primary" href="{{ route('loans.index') }}">＋ Catat peminjaman</a>
    </div>

    <div class="stat-grid">
        <article class="stat-card"><span class="stat-icon icon-brand">▣</span><span class="stat-label">TOTAL LAPTOP</span><strong>{{ $totalLaptops }}</strong><small>Seluruh aset terdaftar</small></article>
        <article class="stat-card"><span class="stat-icon icon-green">✓</span><span class="stat-label">TERSEDIA</span><strong>{{ $availableLaptops }}</strong><small>Siap dipinjam</small></article>
        <article class="stat-card"><span class="stat-icon icon-blue">⇄</span><span class="stat-label">SEDANG DIPAKAI</span><strong>{{ $borrowedLaptops }}</strong></article>
        <article class="stat-card"><span class="stat-icon icon-amber">⚒</span><span class="stat-label">PERAWATAN</span><strong>{{ $maintenanceLaptops }}</strong><small>Perlu pengecekan teknis</small></article>
    </div>

    <div class="two-column">
        <section class="panel">
            <div class="panel-heading"><div><h2>Status perangkat</h2><p>Sebaran kondisi inventaris saat ini.</p></div></div>
            @foreach ([['Tersedia', $availableLaptops, '#27975e'], ['Sedang dipakai', $borrowedLaptops, '#4f7fd0'], ['Perawatan', $maintenanceLaptops, '#d99b2b']] as [$label, $count, $color])
                <div class="chart-row"><div><span>{{ $label }}</span><strong>{{ $count }}</strong></div><div class="chart-track"><span style="width: {{ $totalLaptops ? round($count / $totalLaptops * 100) : 0 }}%; background: {{ $color }}"></span></div></div>
            @endforeach
        </section>
        <section class="panel">
            <div class="panel-heading"><div><h2>Perlu perhatian</h2></div><span class="counter">{{ $pendingReturns }}</span></div>
            @if ($pendingReturns > 0)
                <a class="text-link" href="{{ route('loans.index') }}">{{ $pendingReturns }} pengajuan pengembalian menunggu pemeriksaan →</a>
            @endif
        </section>
        <section class="panel">
            <div class="panel-heading"><div><h2>Aktivitas terbaru</h2><p>Catatan perubahan terakhir dalam sistem.</p></div></div>
            @forelse ($recentActivity as $activity)
                <div class="activity-item"><span class="activity-mark">•</span><div><strong>{{ $activity->description }}</strong><small>{{ $activity->user?->name ?? 'Sistem' }} · {{ $activity->created_at->diffForHumans() }}</small></div></div>
            @empty
                <div class="empty-state">Aktivitas akan muncul saat aset dan transaksi mulai dikelola.</div>
            @endforelse
        </section>
    </div>

    <section class="panel">
        <div class="panel-heading"><div><h2>Peminjaman terbaru</h2><p>Perangkat yang terakhir diserahkan.</p></div><a class="text-link" href="{{ route('loans.index') }}">Lihat semua →</a></div>
        <div class="table-wrap"><table><thead><tr><th>KODE ASET</th><th>PEMEGANG</th><th>TANGGAL PINJAM</th><th>STATUS</th></tr></thead><tbody>
            @forelse ($recentLoans as $loan)
                <tr><td><strong>{{ $loan->laptop->asset_code }}</strong><small>{{ $loan->laptop->brand }} {{ $loan->laptop->model }}</small></td><td>{{ $loan->user->name }}</td><td>{{ $loan->checked_out_at->format('d M Y') }}</td><td><span class="badge {{ $loan->status === 'return_requested' ? 'badge-amber' : ($loan->status === 'returned' ? 'badge-green' : 'badge-blue') }}">{{ ['active' => 'Dipinjam', 'return_requested' => 'Menunggu kembali', 'returned' => 'Dikembalikan'][$loan->status] ?? $loan->status }}</span></td></tr>
            @empty
                <tr><td colspan="4" class="empty-state">Belum ada transaksi peminjaman.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
@endsection
