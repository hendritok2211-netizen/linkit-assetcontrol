@extends('layouts.app')

@section('title', 'Verifikasi QR')

@section('content')
    <div class="page-header">
        <div>
            <h2 class="page-header-title">Verifikasi Perangkat</h2>
            <p class="page-header-sub">Pindai QR code / barcode atau masukkan kode aset secara manual.</p>
        </div>
    </div>

    <div class="two-column">
        {{-- Scanner Panel --}}
        <section class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Pindai QR / Barcode</h3>
                    <p class="card-sub">Pilih foto dari galeri, ambil foto baru, atau gunakan kamera live.</p>
                </div>
            </div>

            <div class="scan-wrap" data-qr-scanner>
                {{-- Video feed (live camera) --}}
                <video id="qr-video" class="scan-video" muted playsinline hidden></video>

                {{-- Status message --}}
                <div id="scanner-message" class="scan-message">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><line x1="7" y1="12" x2="17" y2="12"/></svg>
                    <span>Kamera belum dimulai.</span>
                </div>

                {{-- Primary action: photo / gallery --}}
                <div class="scan-actions">
                    <button class="button button-primary" id="scan-photo" type="button">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        Ambil / Pilih Foto QR
                    </button>
                    {{-- File input: tanpa capture agar tidak paksa kamera --}}
                    <input type="file" id="qr-file-input" accept="image/*" style="display:none;">
                </div>

                {{-- Live camera (collapsible) --}}
                <details class="scan-live-toggle">
                    <summary>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Kamera live (perlu HTTPS / localhost)
                    </summary>
                    <div class="scan-live-controls">
                        <button class="button button-outline button-small" id="scan-start" type="button">▶ Mulai kamera</button>
                        <button class="button button-outline button-small" id="scan-stop" type="button" disabled>■ Hentikan</button>
                    </div>
                </details>
            </div>

            {{-- Manual input form --}}
            <form method="POST" action="{{ route('laptops.verify') }}" id="scan-form" class="scan-manual-form">
                @csrf
                <label class="scan-manual-label" for="asset-code-input">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    Cari manual
                </label>
                <div class="scan-manual-row">
                    <input
                        id="asset-code-input"
                        type="text"
                        name="asset_code"
                        value="{{ old('asset_code', $searchedCode ?? '') }}"
                        placeholder="Kode aset (misal LT-0001) atau nomor seri"
                        required
                        autocomplete="off"
                        autofocus
                    >
                    <button class="button button-dark" type="submit">Cari</button>
                </div>
            </form>
        </section>

        {{-- Result Panel --}}
        <section class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Hasil Verifikasi</h3>
                    <p class="card-sub">Periksa kode aset dan nomor seri yang terdaftar.</p>
                </div>
            </div>

            <div class="scan-result-wrap">
                @isset($searchedCode)
                    @if ($laptop)
                        {{-- Found --}}
                        <div class="scan-found">
                            <div class="scan-found-header">
                                <span class="badge badge-green">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    Aset ditemukan
                                </span>
                                <div class="scan-found-title">
                                    <h3>{{ $laptop->asset_code }}</h3>
                                    <p>{{ $laptop->brand }} {{ $laptop->model }}</p>
                                </div>
                            </div>

                            <dl class="scan-detail-grid">
                                <dt>Nomor seri</dt>
                                <dd>{{ $laptop->serial_number }}</dd>

                                <dt>RAM</dt>
                                <dd>{{ $laptop->ram ?? '—' }}</dd>

                                <dt>Penyimpanan</dt>
                                <dd>{{ $laptop->storage_capacity ?? '—' }}</dd>

                                <dt>Status</dt>
                                <dd>
                                    <span class="badge {{ ['available' => 'badge-green', 'borrowed' => 'badge-blue', 'maintenance' => 'badge-amber', 'inactive' => 'badge-gray'][$laptop->status] ?? 'badge-gray' }}">
                                        {{ ['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'maintenance' => 'Perawatan', 'inactive' => 'Nonaktif'][$laptop->status] ?? $laptop->status }}
                                    </span>
                                </dd>

                                <dt>Kondisi</dt>
                                <dd>
                                    <span class="condition-badge condition-{{ $laptop->condition }}">
                                        {{ ['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'][$laptop->condition] ?? $laptop->condition }}
                                    </span>
                                </dd>

                                <dt>Divisi</dt>
                                <dd>{{ $laptop->division?->name ?? '—' }}</dd>

                                <dt>Bagian / jabatan</dt>
                                <dd>{{ $laptop->division_detail ?? '—' }}</dd>

                                <dt>Pemegang</dt>
                                <dd>{{ $laptop->activeLoan->first()?->user?->name ?? 'Tidak sedang dipinjam' }}</dd>
                            </dl>

                            {{-- Audit Form --}}
                            <form method="POST" action="{{ route('laptops.audit', $laptop) }}" class="audit-form-wrap">
                                @csrf
                                <div class="audit-form-grid">
                                    <label>
                                        Aksi aset
                                        <select name="action" id="asset-audit-action" required>
                                            <option value="inspect" @selected(old('action') === 'inspect')>Catat pemeriksaan</option>
                                            <option value="send_service"
                                                @selected(old('action') === 'send_service')
                                                @disabled(in_array($laptop->status, ['borrowed', 'inactive'], true))>
                                                Kirim ke perawatan
                                            </option>
                                            <option value="finish_service"
                                                @selected(old('action') === 'finish_service')
                                                @disabled($laptop->status !== 'maintenance')>
                                                Selesai diservis
                                            </option>
                                            @if ($laptop->status === 'borrowed' && $laptop->activeLoan->isNotEmpty())
                                                <option value="record_return" @selected(old('action') === 'record_return')>
                                                    Catat pengembalian
                                                </option>
                                            @endif
                                        </select>
                                    </label>

                                    <label>
                                        Kondisi saat diperiksa
                                        <select name="condition" required>
                                            @foreach (['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'] as $key => $label)
                                                <option value="{{ $key }}" @selected($laptop->condition === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>

                                    @if ($laptop->status === 'borrowed' && $laptop->activeLoan->isNotEmpty())
                                        <label id="return-serial-field" class="full-span" hidden>
                                            Konfirmasi nomor seri
                                            <input type="text" name="serial_number" maxlength="100" value="{{ old('serial_number') }}" autocomplete="off" placeholder="Masukkan nomor seri untuk verifikasi">
                                        </label>
                                    @endif

                                    <label class="full-span">
                                        Catatan pemeriksaan
                                        <input type="text" name="notes" maxlength="2000" value="{{ old('notes') }}" placeholder="Catatan servis, pemeriksaan, atau pengembalian">
                                    </label>
                                </div>

                                <div class="audit-form-actions">
                                    <button class="button button-primary" type="submit">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                        Simpan Pemeriksaan
                                    </button>
                                </div>
                            </form>

                            @if ($laptop->status === 'borrowed' && $laptop->activeLoan->isNotEmpty())
                                <script>
                                    (function () {
                                        const auditAction = document.getElementById('asset-audit-action');
                                        const returnSerialField = document.getElementById('return-serial-field');
                                        if (!auditAction || !returnSerialField) return;
                                        const returnSerialInput = returnSerialField.querySelector('input');

                                        function updateReturnFields() {
                                            const isReturn = auditAction.value === 'record_return';
                                            returnSerialField.hidden = !isReturn;
                                            returnSerialInput.required = isReturn;
                                            returnSerialInput.disabled = !isReturn;
                                        }

                                        auditAction.addEventListener('change', updateReturnFields);
                                        updateReturnFields();
                                    })();
                                </script>
                            @endif
                        </div>

                    @else
                        {{-- Not found --}}
                        <div class="scan-not-found">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/><path d="M8 11h6"/></svg>
                            <p>Kode <strong>{{ $searchedCode }}</strong> tidak ditemukan dalam inventaris.</p>
                            <p class="scan-hint">Pastikan kode aset atau nomor seri diketik dengan benar.</p>
                        </div>
                    @endif

                @else
                    {{-- Idle state --}}
                    <div class="scan-idle">
                        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><line x1="7" y1="12" x2="17" y2="12"/></svg>
                        <p>Hasil pemindaian akan tampil di sini.</p>
                        <p class="scan-hint">Pindai QR code atau masukkan kode aset / nomor seri secara manual.</p>
                    </div>
                @endisset
            </div>
        </section>
    </div>
@endsection
