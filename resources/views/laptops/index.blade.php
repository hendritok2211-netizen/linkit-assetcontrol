@extends('layouts.app')

@section('title', 'Data Laptop')

@section('content')
    <div class="welcome-row"><div><h2>Inventaris perangkat</h2><p>Kelola aset, kondisi, dan divisi penanggung jawab.</p></div><a class="button button-primary" href="{{ route('laptops.create') }}">＋ Tambah laptop</a></div>
    <section class="panel">
        <form method="GET" class="filter-bar">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode, nomor seri, merek...">
            <select name="status"><option value="">Semua status</option>@foreach (['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'maintenance' => 'Perawatan', 'inactive' => 'Nonaktif'] as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select>
            <select name="division_id"><option value="">Semua divisi</option>@foreach ($divisions as $division)<option value="{{ $division->id }}" @selected((string) request('division_id') === (string) $division->id)>{{ $division->name }}</option>@endforeach</select>
            <button class="button button-dark" type="submit">Cari</button>
            @if (request()->hasAny(['q', 'status', 'division_id']))<a class="button button-outline" href="{{ route('laptops.index') }}">Reset</a>@endif
        </form>
        <div class="table-wrap"><table><thead><tr><th>ASET</th><th>PEMEGANG</th><th>DIVISI</th><th>KONDISI</th><th>STATUS</th><th>AKSI</th></tr></thead><tbody>
            @forelse ($laptops as $laptop)
                <tr>
                    <td><strong>{{ $laptop->brand }} {{ $laptop->model }}</strong><small>Kode aset: {{ $laptop->asset_code }} · SN {{ $laptop->serial_number }}</small><small>RAM {{ $laptop->ram ?? '—' }} · Storage {{ $laptop->storage_capacity ?? '—' }}</small></td>
                    <td>{{ $laptop->activeLoan->first()?->user?->name ?? '—' }}</td>
                    <td>{{ $laptop->division?->name ?? '—' }}@if ($laptop->division_detail)<small>{{ $laptop->division_detail }}</small>@endif</td>
                    <td>{{ ['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'][$laptop->condition] ?? $laptop->condition }}</td>
                    <td><span class="badge {{ ['available' => 'badge-green', 'borrowed' => 'badge-blue', 'maintenance' => 'badge-amber', 'inactive' => 'badge-gray'][$laptop->status] ?? 'badge-gray' }}">{{ ['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'maintenance' => 'Perawatan', 'inactive' => 'Nonaktif'][$laptop->status] ?? $laptop->status }}</span></td>
                    <td><div class="action-row"><a class="text-link" href="{{ route('laptops.edit', $laptop) }}">Ubah</a><a class="text-link" href="{{ route('laptops.label', $laptop) }}">QR</a><form method="POST" action="{{ route('laptops.destroy', $laptop) }}" onsubmit="return confirm('Hapus aset ini?')">@csrf @method('DELETE')<button class="text-danger" type="submit">Hapus</button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state">Belum ada data laptop yang cocok dengan pencarian.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="pagination-wrap">{{ $laptops->links() }}</div>
    </section>
@endsection
