@extends('layouts.app')

@section('title', 'Data Handphone')

@section('content')
    <div class="page-header">
        <div>
            <h2 class="page-header-title">Inventaris Handphone</h2>
            <p class="page-header-sub">Kelola aset handphone, kondisi, dan divisi penanggung jawab.</p>
        </div>
        <a class="button button-primary" href="{{ route('phones.create') }}">＋ Tambah Handphone</a>
    </div>

    <section class="card">
        {{-- Filter Bar --}}
        <form method="GET" class="filter-row">
            <div class="filter-search">
                <svg class="filter-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode, IMEI, merek...">
            </div>
            <select name="status">
                <option value="">Semua Status</option>
                @foreach (['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'maintenance' => 'Perawatan', 'inactive' => 'Nonaktif'] as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="division_id">
                <option value="">Semua Divisi</option>
                @foreach ($divisions as $division)
                    <option value="{{ $division->id }}" @selected((string) request('division_id') === (string) $division->id)>{{ $division->name }}</option>
                @endforeach
            </select>
            <button class="button button-dark" type="submit">Terapkan</button>
            @if (request()->hasAny(['q', 'status', 'division_id']))
                <a class="button button-outline" href="{{ route('phones.index') }}">Reset</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ASET</th>
                        <th>PEMEGANG</th>
                        <th>DIVISI</th>
                        <th>HARGA</th>
                        <th>KONDISI</th>
                        <th>STATUS</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($phones as $phone)
                        <tr>
                            <td>
                                <strong>{{ $phone->brand }} {{ $phone->model }}</strong>
                                <small>{{ $phone->asset_code }} · IMEI {{ $phone->imei }}</small>
                                <small>{{ $phone->os ?? '—' }} · {{ $phone->storage ?? '—' }}</small>
                            </td>
                            <td>{{ $phone->activeLoan->first()?->user?->name ?? '—' }}</td>
                            <td>{{ $phone->division?->name ?? '—' }}</td>
                            <td>
                                @if ($phone->price)
                                    <strong>Rp {{ number_format($phone->price, 0, ',', '.') }}</strong>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="condition-badge condition-{{ $phone->condition }}">
                                    {{ ['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'][$phone->condition] ?? $phone->condition }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ ['available' => 'badge-green', 'borrowed' => 'badge-blue', 'maintenance' => 'badge-amber', 'inactive' => 'badge-gray'][$phone->status] ?? 'badge-gray' }}">
                                    {{ ['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'maintenance' => 'Perawatan', 'inactive' => 'Nonaktif'][$phone->status] ?? $phone->status }}
                                </span>
                            </td>
                            <td>
                                <div class="action-row">
                                    <a class="text-link" href="{{ route('phones.edit', $phone) }}">Ubah</a>
                                    <a class="text-link" href="{{ route('phones.label', $phone) }}">QR</a>
                                    <form method="POST" action="{{ route('phones.destroy', $phone) }}" onsubmit="return confirm('Hapus aset ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">Belum ada data handphone yang cocok dengan pencarian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $phones->links() }}</div>
    </section>
@endsection
