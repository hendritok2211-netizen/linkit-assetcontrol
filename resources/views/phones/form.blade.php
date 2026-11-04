@extends('layouts.app')

@section('title', $phone->exists ? 'Ubah Handphone' : 'Tambah Handphone')

@section('content')
    @php
        $initialUnits = old('units');
        if (! is_array($initialUnits)) {
            $initialUnits = [$phone->exists ? [
                'asset_code' => $phone->asset_code,
                'imei' => $phone->imei,
                'brand' => $phone->brand,
                'model' => $phone->model,
                'os' => $phone->os,
                'storage' => $phone->storage,
                'purchase_date' => $phone->purchase_date?->format('Y-m-d'),
                'price' => $phone->price,
                'division_id' => $phone->division_id,
                'condition' => $phone->condition,
                'status' => $phone->status,
                'notes' => $phone->notes,
            ] : []];
        }
    @endphp
    <div class="page-header">
        <div>
            <h2 class="page-header-title">{{ $phone->exists ? 'Perbarui Informasi Handphone' : 'Daftarkan Handphone Baru' }}</h2>
            <p class="page-header-sub">Setiap handphone memiliki kode aset, IMEI, dan spesifikasi masing-masing.</p>
        </div>
        <a class="button button-outline" href="{{ route('phones.index') }}">← Kembali</a>
    </div>

    <section class="panel form-panel">
        <form method="POST" action="{{ $phone->exists ? route('phones.update', $phone) : route('phones.store') }}">
            @csrf
            @if ($phone->exists) @method('PUT') @endif
            
            <div class="quantity-control">
                <label for="quantity">Jumlah perangkat</label>
                <div class="action-row">
                    <button class="button button-outline button-small" id="quantity-decrease" type="button" aria-label="Kurangi jumlah">−</button>
                    <input id="quantity" name="quantity" type="number" value="{{ old('quantity', count($initialUnits)) }}" min="1" max="100" required aria-describedby="quantity-hint">
                    <button class="button button-outline button-small" id="quantity-increase" type="button" aria-label="Tambah jumlah">＋</button>
                </div>
                <small class="muted" id="quantity-hint">Gunakan tombol +/− untuk menambah atau mengurangi form perangkat. Setiap perangkat dapat diedit sendiri (maksimal 100).</small>
            </div>

            <div class="laptop-unit-list" id="phone-unit-list" aria-live="polite"></div>

            <template id="phone-unit-template">
                <section class="laptop-unit-card">
                    <h3 class="laptop-unit-title"></h3>
                    <div class="form-grid">
                        <label>Kode aset <span class="muted">(kosongkan untuk dibuat otomatis)</span>
                            <input type="text" name="units[__INDEX__][asset_code]" maxlength="40" placeholder="Otomatis">
                        </label>
                        <label>IMEI
                            <input type="text" name="units[__INDEX__][imei]" required maxlength="20" placeholder="123456789012345">
                        </label>
                        <label>Merek
                            <input type="text" name="units[__INDEX__][brand]" required maxlength="100" placeholder="Contoh: Samsung">
                        </label>
                        <label>Model
                            <input type="text" name="units[__INDEX__][model]" required maxlength="150" placeholder="Contoh: Galaxy S23">
                        </label>
                        
                        <fieldset class="spec-field">
                            <legend>Sistem Operasi</legend>
                            <div class="spec-options">
                                @foreach (\App\Models\Phone::OS_OPTIONS as $osOption)
                                    <label class="spec-option">
                                        <input type="radio" name="units[__INDEX__][os]" value="{{ $osOption }}" required>{{ $osOption }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="spec-field">
                            <legend>Penyimpanan</legend>
                            <div class="spec-options">
                                @foreach (\App\Models\Phone::STORAGE_OPTIONS as $storageOption)
                                    <label class="spec-option">
                                        <input type="radio" name="units[__INDEX__][storage]" value="{{ $storageOption }}" required>{{ $storageOption }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <label>Tanggal pembelian
                            <input type="date" name="units[__INDEX__][purchase_date]">
                        </label>

                        <label>Harga pembelian (Rp)
                            <input type="number" name="units[__INDEX__][price]" min="0" step="0.01" placeholder="0">
                        </label>
                        
                        <label>Divisi
                            <select name="units[__INDEX__][division_id]">
                                <option value="">Belum ditentukan</option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>Kondisi
                            <select name="units[__INDEX__][condition]" required>
                                @foreach (['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        
                        <label>Status
                            <select name="units[__INDEX__][status]" required>
                                <option value="available">Tersedia</option>
                                <option value="maintenance">Perawatan</option>
                                <option value="inactive">Nonaktif</option>
                                <option value="borrowed">Dipinjam</option>
                            </select>
                        </label>

                        <label class="full-span">Catatan
                            <textarea name="units[__INDEX__][notes]" rows="3" maxlength="2000" placeholder="Aksesori atau catatan perangkat"></textarea>
                        </label>
                    </div>
                </section>
            </template>

            <div class="form-actions">
                <a class="button button-outline" href="{{ route('phones.index') }}">Batal</a>
                <button class="button button-primary" type="submit">Simpan handphone</button>
            </div>
        </form>
    </section>

    <script>
        const quantityInput = document.getElementById('quantity');
        const unitList = document.getElementById('phone-unit-list');
        const unitTemplate = document.getElementById('phone-unit-template');
        const isEditingPhone = @json($phone->exists);
        let unitValues = @json($initialUnits);

        function captureUnitValues() {
            unitValues = Array.from(unitList.querySelectorAll('.laptop-unit-card')).map((card) => {
                const values = {};
                card.querySelectorAll('[name]').forEach((input) => {
                    const match = input.name.match(/\[([^\]]+)\]$/);
                    if (!match) return;
                    
                    if (input.type === 'radio') {
                        if (input.checked) values[match[1]] = input.value;
                    } else {
                        values[match[1]] = input.value;
                    }
                });
                return values;
            });
        }

        function renderPhoneUnits() {
            const quantity = Math.min(100, Math.max(1, Number.parseInt(quantityInput.value, 10) || 1));
            if (unitList.querySelector('.laptop-unit-card')) {
                captureUnitValues();
            }

            while (unitValues.length < quantity) {
                const previousUnit = unitValues.at(-1) || {};
                unitValues.push({
                    ...previousUnit,
                    asset_code: '',
                    imei: '',
                });
            }

            quantityInput.value = quantity;
            unitList.replaceChildren();

            unitValues.slice(0, quantity).forEach((values, index) => {
                const card = unitTemplate.content.cloneNode(true);
                const cardTitle = card.querySelector('.laptop-unit-title');
                cardTitle.textContent = quantity === 1 ? 'Informasi perangkat' : `Perangkat #${index + 1}`;

                card.querySelectorAll('[name]').forEach((input) => {
                    input.name = input.name.replace('__INDEX__', String(index));
                    const match = input.name.match(/\[([^\]]+)\]$/);
                    if (!match) return;

                    const fieldName = match[1];
                    if (input.type === 'radio') {
                        input.checked = values[fieldName] === input.value;
                    } else {
                        input.value = values[fieldName] || '';
                    }

                    if (fieldName === 'status' && isEditingPhone && index === 0) {
                        if (input.value === 'borrowed') {
                            const borrowedOption = document.createElement('option');
                            borrowedOption.value = 'borrowed';
                            borrowedOption.textContent = 'Dipinjam';
                            input.append(borrowedOption);
                        }
                    }
                });

                unitList.append(card);
            });
        }

        document.getElementById('quantity-decrease').addEventListener('click', () => {
            quantityInput.value = Math.max(1, (Number.parseInt(quantityInput.value, 10) || 1) - 1);
            renderPhoneUnits();
        });

        document.getElementById('quantity-increase').addEventListener('click', () => {
            quantityInput.value = Math.min(100, (Number.parseInt(quantityInput.value, 10) || 1) + 1);
            renderPhoneUnits();
        });

        renderPhoneUnits();
    </script>
@endsection
