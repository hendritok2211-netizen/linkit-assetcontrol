@extends('layouts.app')

@section('title', 'Peminjaman')

@section('content')
    <div class="welcome-row"><div><h2>Serah terima perangkat</h2><p>Catat peminjaman dan verifikasi kondisi saat laptop dikembalikan.</p></div><a href="{{ route('laptops.scan') }}" class="button button-outline">▧ Verifikasi QR</a></div>
    <section class="panel">
        <div class="panel-heading"><div><h2>Catat peminjaman</h2><p>Pilih laptop yang tersedia dan karyawan yang telah disetujui.</p></div></div>
        <form method="POST" action="{{ route('loans.issue') }}" class="form-grid compact-grid">
            @csrf
            <label>Laptop<select name="laptop_id" required><option value="">Pilih laptop</option>@foreach ($laptops as $laptop)<option value="{{ $laptop->id }}" @selected(old('laptop_id') == $laptop->id)>{{ $laptop->asset_code }} · {{ $laptop->brand }} {{ $laptop->model }} · {{ $laptop->ram ?? 'RAM —' }} / {{ $laptop->storage_capacity ?? 'Storage —' }}@if ($laptop->division_detail) · {{ $laptop->division_detail }}@endif</option>@endforeach</select></label>
            <label>Karyawan<select name="user_id" required><option value="">Pilih peminjam</option>@foreach ($employees as $employee)<option value="{{ $employee->id }}" @selected(old('user_id') == $employee->id)>{{ $employee->name }} · {{ $employee->division?->name ?? 'Tanpa divisi' }}</option>@endforeach</select></label>
            <label>Kondisi saat diserahkan<select name="condition_out" required>@foreach (['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'] as $key => $label)<option value="{{ $key }}" @selected(old('condition_out', 'good') === $key)>{{ $label }}</option>@endforeach</select></label>
            <label class="full-span">Catatan serah terima<input type="text" name="handover_notes" value="{{ old('handover_notes') }}" maxlength="2000" placeholder="Contoh: termasuk charger dan tas"></label>
            <div class="form-actions full-span"><button class="button button-primary" type="submit">Simpan serah terima</button></div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-heading"><div><h2>Riwayat transaksi</h2><p>Pemeriksaan nomor seri wajib dilakukan sebelum menyetujui pengembalian.</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>PERANGKAT / PEMEGANG</th><th>TANGGAL PINJAM</th><th>STATUS</th><th>PEMERIKSAAN KEMBALI</th></tr></thead><tbody>
            @forelse ($loans as $loan)
                <tr>
                    <td><strong>{{ $loan->laptop->asset_code }}</strong><small>{{ $loan->laptop->brand }} {{ $loan->laptop->model }}</small><small>{{ $loan->user->name }}</small></td>
                    <td>{{ $loan->checked_out_at->format('d M Y') }}</td>
                    <td><span class="badge {{ ['active' => 'badge-blue', 'return_requested' => 'badge-amber', 'returned' => 'badge-green'][$loan->status] ?? 'badge-gray' }}">{{ ['active' => 'Dipinjam', 'return_requested' => 'Menunggu kembali', 'returned' => 'Dikembalikan'][$loan->status] ?? $loan->status }}</span></td>
                    <td>
                        @if ($loan->status === 'return_requested')
                            <form method="POST" action="{{ route('loans.approve-return', $loan) }}" class="return-verify-form">@csrf
                                <input type="text" name="serial_number" placeholder="Cocokkan nomor seri" required aria-label="Nomor seri laptop">
                                <select name="condition_in" aria-label="Kondisi saat kembali" required><option value="good">Baik</option><option value="minor_damage">Rusak ringan</option><option value="damaged">Rusak berat</option></select>
                                <input type="text" name="return_notes" placeholder="Catatan (opsional)" aria-label="Catatan pemeriksaan">
                                <button class="button button-small button-primary" type="submit">Setujui</button>
                            </form>
                            <form method="POST" action="{{ route('loans.reject-return', $loan) }}" class="inline-form reject-form">@csrf<input name="rejection_note" placeholder="Alasan jika ditolak" required aria-label="Alasan penolakan"><button class="button button-small button-outline" type="submit">Tolak</button></form>
                            @if ($loan->return_notes)<small>Catatan karyawan: {{ $loan->return_notes }}</small>@endif
                        @elseif ($loan->status === 'returned')
                            <span>{{ ['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'][$loan->condition_in] ?? '—' }}</span><small>Diverifikasi {{ $loan->returned_at?->format('d M Y H:i') }}</small>
                        @else
                            <span class="muted">Belum diajukan</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty-state">Belum ada transaksi peminjaman.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="pagination-wrap">{{ $loans->links() }}</div>
    </section>
@endsection
