@extends('layouts.app')

@section('title', 'Laptop Saya')

@section('content')
    <div class="welcome-row"><div><h2>Riwayat laptop saya</h2><p>Daftar perangkat yang sedang dan pernah dipinjam.</p></div><a class="button button-outline" href="{{ route('dashboard') }}">← Dashboard</a></div>
    <section class="panel">
        <div class="table-wrap"><table><thead><tr><th>ASET</th><th>PERANGKAT / SERIAL</th><th>TANGGAL PINJAM</th><th>TANGGAL KEMBALI</th><th>STATUS</th></tr></thead><tbody>
            @forelse ($loans as $loan)
                <tr><td><strong>{{ $loan->laptop->asset_code }}</strong></td><td>{{ $loan->laptop->brand }} {{ $loan->laptop->model }}<small>SN: {{ $loan->laptop->serial_number }}</small></td><td>{{ $loan->checked_out_at->format('d M Y') }}</td><td>{{ $loan->returned_at?->format('d M Y') ?? '—' }}</td><td><span class="badge {{ ['active' => 'badge-blue', 'return_requested' => 'badge-amber', 'returned' => 'badge-green'][$loan->status] ?? 'badge-gray' }}">{{ ['active' => 'Dipinjam', 'return_requested' => 'Menunggu kembali', 'returned' => 'Dikembalikan'][$loan->status] ?? $loan->status }}</span></td></tr>
            @empty
                <tr><td colspan="5" class="empty-state">Belum ada riwayat peminjaman untuk akun Anda.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="pagination-wrap">{{ $loans->links() }}</div>
    </section>
@endsection
