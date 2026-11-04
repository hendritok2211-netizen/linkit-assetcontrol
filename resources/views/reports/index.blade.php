@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
    <div class="page-header">
        <div>
            <h2 class="page-header-title">Laporan Inventaris</h2>
            <p class="page-header-sub">Filter aset berdasarkan kode, status, dan divisi; unduh hasil sebagai CSV.</p>
        </div>
        <a class="button button-primary" href="{{ route('reports.export', request()->query()) }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Unduh CSV
        </a>
    </div>

    <section class="card">
        <form method="GET" action="{{ route('reports.index') }}" class="filter-row">
            <div class="filter-search">
                <svg class="filter-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari aset atau nomor seri">
            </div>
            <select name="status">
                <option value="">Semua Status</option>
                @foreach (['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'maintenance' => 'Perawatan', 'inactive' => 'Nonaktif'] as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="division_id">
                <option value="">Semua Divisi</option>
                @foreach (\App\Models\Division::query()->orderBy('name')->get() as $division)
                    <option value="{{ $division->id }}" @selected((string) request('division_id') === (string) $division->id)>{{ $division->name }}</option>
                @endforeach
            </select>
            <input type="date" name="from_date" value="{{ request('from_date') }}" aria-label="Dari tanggal">
            <input type="date" name="to_date" value="{{ request('to_date') }}" aria-label="Sampai tanggal">
            <button class="button button-dark" type="submit">Terapkan</button>
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>KODE ASET</th>
                        <th>PERANGKAT / DIVISI</th>
                        <th>SPESIFIKASI</th>
                        <th>NOMOR SERI</th>
                        <th>HARGA</th>
                        <th>PEMEGANG</th>
                        <th>TGL PINJAM</th>
                        <th>TGL BELI</th>
                        <th>KONDISI</th>
                        <th>STATUS ASET</th>
                        <th>STATUS PINJAM</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laptops as $laptop)
                        @php($loan = $laptop->activeLoan->first())
                        <tr>
                            <td><strong>{{ $laptop->asset_code }}</strong></td>
                            <td>
                                {{ $laptop->brand }} {{ $laptop->model }}
                                <small>{{ $laptop->division?->name ?? 'Tanpa divisi' }}@if ($laptop->division_detail) · {{ $laptop->division_detail }}@endif</small>
                            </td>
                            <td>{{ $laptop->ram ?? '—' }} / {{ $laptop->storage_capacity ?? '—' }}</td>
                            <td>{{ $laptop->serial_number }}</td>
                            <td>
                                @if ($laptop->price)
                                    <strong>Rp {{ number_format($laptop->price, 0, ',', '.') }}</strong>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>{{ $loan?->user?->name ?? '—' }}</td>
                            <td>{{ $loan?->checked_out_at?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $laptop->purchase_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <span class="condition-badge condition-{{ $laptop->condition }}">
                                    {{ ['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'][$laptop->condition] ?? $laptop->condition }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ ['available' => 'badge-green', 'borrowed' => 'badge-blue', 'maintenance' => 'badge-amber', 'inactive' => 'badge-gray'][$laptop->status] ?? 'badge-gray' }}">
                                    {{ ['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'maintenance' => 'Perawatan', 'inactive' => 'Nonaktif'][$laptop->status] ?? $laptop->status }}
                                </span>
                            </td>
                            <td>{{ ['active' => 'Dipinjam', 'return_requested' => 'Menunggu kembali'][$loan?->status] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="empty-state">Tidak ada aset yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $laptops->links() }}</div>
    </section>
@endsection
