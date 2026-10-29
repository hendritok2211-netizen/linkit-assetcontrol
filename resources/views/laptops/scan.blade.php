@extends('layouts.app')

@section('title', 'Verifikasi QR')

@section('content')
    <div class="welcome-row"><div><h2>Verifikasi perangkat</h2><p>Pindai QR code atau barcode aset dengan kamera HP, pilih gambar dari galeri, atau masukkan kode aset/nomor seri secara manual.</p></div></div>
    <div class="two-column">
        <section class="panel">
            <div class="panel-heading"><div><h2>Pindai dari foto</h2><p>Gunakan kamera bawaan HP atau pilih gambar. Mendukung QR dan barcode 1D; foto diproses langsung di perangkat.</p></div></div>
            <div class="scanner-box" data-qr-scanner>
                <video id="qr-video" muted playsinline hidden></video>
                <p id="scanner-message" class="muted">Kamera belum dimulai.</p>
                <div class="action-row">
                    <button class="button button-primary" id="scan-photo" type="button">Ambil Foto QR / Pilih Gambar</button>
                    <input type="file" id="qr-file-input" accept="image/*" capture="environment" style="display:none;">
                </div>
                <details>
                    <summary>Gunakan kamera live (perlu HTTPS atau localhost)</summary>
                    <div class="action-row">
                        <button class="button button-outline" id="scan-start" type="button">Mulai kamera live</button>
                        <button class="button button-outline" id="scan-stop" type="button" disabled>Hentikan</button>
                    </div>
                </details>
            </div>
            <form method="POST" action="{{ route('laptops.verify') }}" id="scan-form" class="form-stack scan-form">@csrf<label>Kode aset atau nomor seri<input id="asset-code-input" type="text" name="asset_code" value="{{ old('asset_code', $searchedCode ?? '') }}" placeholder="Contoh LT-0001 atau nomor seri" required></label><button class="button button-dark" type="submit">Cari perangkat</button></form>
        </section>
        <section class="panel">
            <div class="panel-heading"><div><h2>Hasil verifikasi</h2><p>Periksa kode aset dan nomor seri yang terdaftar.</p></div></div>
            @isset($searchedCode)
                @if ($laptop)
                    <div class="asset-result">
                        <span class="badge badge-green">Aset ditemukan</span>
                        <h3>{{ $laptop->asset_code }}</h3>
                        <p>{{ $laptop->brand }} {{ $laptop->model }}</p>
                        <dl>
                            <dt>Nomor seri</dt><dd>{{ $laptop->serial_number }}</dd>
                            <dt>RAM</dt><dd>{{ $laptop->ram ?? '—' }}</dd>
                            <dt>Penyimpanan</dt><dd>{{ $laptop->storage_capacity ?? '—' }}</dd>
                            <dt>Status</dt><dd>{{ ['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'maintenance' => 'Perawatan', 'inactive' => 'Nonaktif'][$laptop->status] ?? $laptop->status }}</dd>
                            <dt>Kondisi</dt><dd>{{ ['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'][$laptop->condition] ?? $laptop->condition }}</dd>
                            <dt>Divisi</dt><dd>{{ $laptop->division?->name ?? '—' }}</dd>
                            <dt>Bagian / jabatan</dt><dd>{{ $laptop->division_detail ?? '—' }}</dd>
                            <dt>Pemegang</dt><dd>{{ $laptop->activeLoan->first()?->user?->name ?? 'Tidak sedang dipinjam' }}</dd>
                        </dl>
                        <form method="POST" action="{{ route('laptops.audit', $laptop) }}" class="form-stack audit-form">
                            @csrf
                            <label>Aksi aset<select name="action" id="asset-audit-action" required>
                                <option value="inspect" @selected(old('action') === 'inspect')>Catat pemeriksaan</option>
                                <option value="send_service" @selected(old('action') === 'send_service') @disabled(in_array($laptop->status, ['borrowed', 'inactive'], true))>Kirim ke perawatan</option>
                                <option value="finish_service" @selected(old('action') === 'finish_service') @disabled($laptop->status !== 'maintenance')>Selesai diservis</option>
                                @if ($laptop->status === 'borrowed' && $laptop->activeLoan->isNotEmpty())
                                    <option value="record_return" @selected(old('action') === 'record_return')>Catat pengembalian</option>
                                @endif
                            </select></label>
                            <label>Kondisi saat diperiksa<select name="condition" required>@foreach (['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'] as $key => $label)<option value="{{ $key }}" @selected($laptop->condition === $key)>{{ $label }}</option>@endforeach</select></label>
                            @if ($laptop->status === 'borrowed' && $laptop->activeLoan->isNotEmpty())
                                <label id="return-serial-field" hidden>Konfirmasi nomor seri<input type="text" name="serial_number" maxlength="100" value="{{ old('serial_number') }}" autocomplete="off"></label>
                            @endif
                            <label>Catatan pemeriksaan<input type="text" name="notes" maxlength="2000" value="{{ old('notes') }}" placeholder="Catatan servis, pemeriksaan, atau pengembalian"></label>
                            <button class="button button-primary" type="submit">Simpan pemeriksaan</button>
                        </form>
                        @if ($laptop->status === 'borrowed' && $laptop->activeLoan->isNotEmpty())
                            <script>
                                const auditAction = document.getElementById('asset-audit-action');
                                const returnSerialField = document.getElementById('return-serial-field');
                                const returnSerialInput = returnSerialField.querySelector('input');

                                function updateReturnFields() {
                                    const isRecordingReturn = auditAction.value === 'record_return';
                                    returnSerialField.hidden = !isRecordingReturn;
                                    returnSerialInput.required = isRecordingReturn;
                                    returnSerialInput.disabled = !isRecordingReturn;
                                }

                                auditAction.addEventListener('change', updateReturnFields);
                                updateReturnFields();
                            </script>
                        @endif
                    </div>
                @else
                    <div class="empty-state">Kode <strong>{{ $searchedCode }}</strong> tidak ditemukan dalam inventaris.</div>
                @endif
            @else
                <div class="empty-state">Hasil pemindaian atau pencarian aset akan tampil di sini.</div>
            @endisset
        </section>
    </div>
@endsection
