@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
    <div class="welcome-row"><div><h2>Laporan inventaris</h2><p>Filter aset berdasarkan kode, status, dan divisi; unduh hasil sebagai CSV.</p></div><a class="button button-primary" href="{{ route('reports.export', request()->query()) }}">↓ Unduh CSV</a></div>
    <section class="panel">
        <form method="GET" action="{{ route('reports.index') }}" class="filter-bar">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari aset atau nomor seri">
            <select name="status"><option value="">Semua status</option>@foreach (['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'maintenance' => 'Perawatan', 'inactive' => 'Nonaktif'] as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select>
            <select name="division_id"><option value="">Semua divisi</option>@foreach (\App\Models\Division::query()->orderBy('name')->get() as $division)<option value="{{ $division->id }}" @selected((string) request('division_id') === (string) $division->id)>{{ $division->name }}</option>@endforeach</select>
            <input type="date" name="from_date" value="{{ request('from_date') }}" aria-label="Tanggal pembelian dari">
            <input type="date" name="to_date" value="{{ request('to_date') }}" aria-label="Tanggal pembelian sampai">
            <button class="button button-dark" type="submit">Terapkan filter</button>
        </form>
        <div class="table-wrap"><table><thead><tr><th>KODE ASET</th><th>PERANGKAT / DIVISI</th><th>RAM</th><th>PENYIMPANAN</th><th>NOMOR SERI</th><th>PEMEGANG TERAKHIR</th><th>TANGGAL PINJAM</th><th>TANGGAL BELI</th><th>KONDISI ASET</th><th>STATUS ASET</th><th>STATUS PINJAM</th><th>CATATAN ASET</th><th>CATATAN SERAH TERIMA</th></tr></thead><tbody>
            @forelse ($laptops as $laptop)
                @php($loan = $laptop->activeLoan->first())
                <tr>
                    <td><strong>{{ $laptop->asset_code }}</strong></td>
                    <td>{{ $laptop->brand }} {{ $laptop->model }}<small>{{ $laptop->division?->name ?? 'Tanpa divisi' }}@if ($laptop->division_detail) · {{ $laptop->division_detail }}@endif</small></td>
                    <td>{{ $laptop->ram ?? '—' }}</td>
                    <td>{{ $laptop->storage_capacity ?? '—' }}</td>
                    <td>{{ $laptop->serial_number }}</td>
                    <td>{{ $loan?->user?->name ?? '—' }}</td>
                    <td>{{ $loan?->checked_out_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td>{{ $laptop->purchase_date?->format('d M Y') ?? '—' }}</td>
                    <td>{{ ['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'][$laptop->condition] ?? $laptop->condition }}</td>
                    <td>{{ ['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'maintenance' => 'Perawatan', 'inactive' => 'Nonaktif'][$laptop->status] ?? $laptop->status }}</td>
                    <td>{{ ['active' => 'Dipinjam', 'return_requested' => 'Menunggu kembali'][$loan?->status] ?? '—' }}</td>
                    <td>{{ $laptop->notes ?? '—' }}</td>
                    <td>{{ $loan?->handover_notes ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="13" class="empty-state">Tidak ada aset yang cocok dengan filter.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="pagination-wrap">{{ $laptops->links() }}</div>
    </section>
@endsection
