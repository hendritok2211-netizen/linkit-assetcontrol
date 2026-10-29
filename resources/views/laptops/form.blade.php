@extends('layouts.app')

@section('title', $laptop->exists ? 'Ubah Laptop' : 'Tambah Laptop')

@section('content')
    @php
        $initialUnits = old('units');
        if (! is_array($initialUnits)) {
            $initialUnits = [$laptop->exists ? [
                'asset_code' => $laptop->asset_code,
                'serial_number' => $laptop->serial_number,
                'brand' => $laptop->brand,
                'model' => $laptop->model,
                'ram' => $laptop->ram,
                'storage_capacity' => $laptop->storage_capacity,
                'purchase_date' => $laptop->purchase_date?->format('Y-m-d'),
                'division_id' => $laptop->division_id,
                'division_detail' => $laptop->division_detail,
                'condition' => $laptop->condition,
                'status' => $laptop->status,
                'notes' => $laptop->notes,
            ] : []];
        }

        $divisionDetails = $divisions->map(fn ($division) => [
            'id' => (string) $division->id,
            'details' => $division->sections ?? [],
        ])->values();
    @endphp
    <div class="welcome-row"><div><h2>{{ $laptop->exists ? 'Perbarui informasi perangkat' : 'Daftarkan perangkat baru' }}</h2><p>Setiap perangkat memiliki kode aset, nomor seri, dan spesifikasi masing-masing.</p></div><a class="button button-outline" href="{{ route('laptops.index') }}">← Kembali</a></div>
    <section class="panel form-panel">
        <form method="POST" action="{{ $laptop->exists ? route('laptops.update', $laptop) : route('laptops.store') }}">
            @csrf
            @if ($laptop->exists) @method('PUT') @endif
            <div class="quantity-control">
                <label for="quantity">Jumlah perangkat</label>
                <div class="action-row">
                    <button class="button button-outline button-small" id="quantity-decrease" type="button" aria-label="Kurangi jumlah">−</button>
                    <input id="quantity" name="quantity" type="number" value="{{ old('quantity', count($initialUnits)) }}" min="1" max="100" required aria-describedby="quantity-hint">
                    <button class="button button-outline button-small" id="quantity-increase" type="button" aria-label="Tambah jumlah">＋</button>
                </div>
                <small class="muted" id="quantity-hint">Gunakan tombol +/− untuk menambah atau mengurangi form perangkat. Setiap perangkat dapat diedit sendiri (maksimal 100).</small>
            </div>

            <div class="laptop-unit-list" id="laptop-unit-list" aria-live="polite"></div>
            <template id="laptop-unit-template">
                <section class="laptop-unit-card">
                    <h3 class="laptop-unit-title"></h3>
                    <div class="form-grid">
                        <label>Kode aset <span class="muted">(kosongkan untuk dibuat otomatis)</span><input type="text" name="units[__INDEX__][asset_code]" maxlength="40" placeholder="Otomatis"></label>
                        <label>Nomor seri pabrik<input type="text" name="units[__INDEX__][serial_number]" required maxlength="100"></label>
                        <label>Merek<input type="text" name="units[__INDEX__][brand]" required maxlength="100" placeholder="Contoh: Lenovo"></label>
                        <label>Model<input type="text" name="units[__INDEX__][model]" required maxlength="150" placeholder="Contoh: ThinkPad E14"></label>
                        <fieldset class="spec-field">
                            <legend>RAM</legend>
                            <div class="spec-options">
                                @foreach (\App\Models\Laptop::RAM_OPTIONS as $ramOption)
                                    <label class="spec-option"><input type="radio" name="units[__INDEX__][ram]" value="{{ $ramOption }}" required>{{ $ramOption }}</label>
                                @endforeach
                            </div>
                        </fieldset>
                        <fieldset class="spec-field">
                            <legend>Penyimpanan</legend>
                            <div class="spec-options">
                                @foreach (\App\Models\Laptop::STORAGE_OPTIONS as $storageOption)
                                    <label class="spec-option"><input type="radio" name="units[__INDEX__][storage_capacity]" value="{{ $storageOption }}" required>{{ $storageOption }}</label>
                                @endforeach
                            </div>
                        </fieldset>
                        <label>Tanggal pembelian<input type="date" name="units[__INDEX__][purchase_date]"></label>
                        <label>Divisi<select name="units[__INDEX__][division_id]"><option value="">Belum ditentukan</option>@foreach ($divisions as $division)<option value="{{ $division->id }}">{{ $division->name }}</option>@endforeach</select></label>
                        <label>Bagian / jabatan<select name="units[__INDEX__][division_detail]" disabled><option value="">Pilih divisi terlebih dahulu</option></select></label>
                        <label>Kondisi<select name="units[__INDEX__][condition]" required>@foreach (['good' => 'Baik', 'minor_damage' => 'Rusak ringan', 'damaged' => 'Rusak berat'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
                        <label>Status<select name="units[__INDEX__][status]" required><option value="available">Tersedia</option><option value="maintenance">Perawatan</option><option value="inactive">Nonaktif</option><option value="borrowed">Dipinjam</option></select></label>
                        <label class="full-span">Catatan<textarea name="units[__INDEX__][notes]" rows="3" maxlength="2000" placeholder="Aksesori atau catatan perangkat"></textarea></label>
                    </div>
                </section>
            </template>
            <div class="form-actions"><a class="button button-outline" href="{{ route('laptops.index') }}">Batal</a><button class="button button-primary" type="submit">Simpan laptop</button></div>
        </form>
    </section>
    <script>
        const quantityInput = document.getElementById('quantity');
        const unitList = document.getElementById('laptop-unit-list');
        const unitTemplate = document.getElementById('laptop-unit-template');
        const isEditingLaptop = @json($laptop->exists);
        let unitValues = @json($initialUnits);
        const divisionDetailsById = Object.fromEntries(
            @json($divisionDetails).map(({ id, details }) => [id, details])
        );

        function updateDivisionDetails(card, selectedDetail = '') {
            const divisionSelect = card.querySelector('select[name$="[division_id]"]');
            const detailSelect = card.querySelector('select[name$="[division_detail]"]');
            const details = divisionDetailsById[divisionSelect.value] || [];
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = divisionSelect.value
                ? (details.length ? 'Pilih bagian / jabatan' : 'Tidak ada rincian divisi')
                : 'Pilih divisi terlebih dahulu';
            detailSelect.replaceChildren(placeholder);
            detailSelect.disabled = !divisionSelect.value || details.length === 0;

            details.forEach((detail) => {
                const option = document.createElement('option');
                option.value = detail;
                option.textContent = detail;
                detailSelect.append(option);
            });

            detailSelect.value = details.includes(selectedDetail) ? selectedDetail : '';
        }

        function captureUnitValues() {
            unitValues = Array.from(unitList.querySelectorAll('.laptop-unit-card')).map((card) => {
                const values = {};
                card.querySelectorAll('[name]').forEach((input) => {
                    const match = input.name.match(/\[([^\]]+)\]$/);
                    if (!match) {
                        return;
                    }

                    if (input.type === 'radio') {
                        if (input.checked) {
                            values[match[1]] = input.value;
                        }
                    } else {
                        values[match[1]] = input.value;
                    }
                });
                return values;
            });
        }

        function renderLaptopUnits() {
            const quantity = Math.min(100, Math.max(1, Number.parseInt(quantityInput.value, 10) || 1));
            if (unitList.querySelector('.laptop-unit-card')) {
                captureUnitValues();
            }

            while (unitValues.length < quantity) {
                const previousUnit = unitValues.at(-1) || {};
                unitValues.push({
                    ...previousUnit,
                    asset_code: '',
                    serial_number: '',
                });
            }

            quantityInput.value = quantity;
            unitList.replaceChildren();

            for (let index = 0; index < quantity; index++) {
                const fragment = unitTemplate.content.cloneNode(true);
                const card = fragment.querySelector('.laptop-unit-card');
                card.querySelector('.laptop-unit-title').textContent = `Perangkat ${index + 1}${isEditingLaptop && index === 0 ? ' (aset yang sedang diubah)' : ''}`;

                card.querySelectorAll('[name]').forEach((input) => {
                    input.name = input.name.replaceAll('__INDEX__', String(index));
                    const fieldName = input.name.match(/\[([^\]]+)\]$/)?.[1];
                    const value = unitValues[index]?.[fieldName] ?? '';

                    if (input.type === 'radio') {
                        input.checked = input.value === value;
                    } else if (input.tagName === 'SELECT') {
                        input.value = value || (fieldName === 'condition' ? 'good' : 'available');
                    } else {
                        input.value = value;
                    }

                    if (fieldName === 'status' && input.tagName === 'SELECT' && (! isEditingLaptop || index > 0)) {
                        input.querySelector('option[value="borrowed"]').disabled = true;
                        if (input.value === 'borrowed') {
                            input.value = 'available';
                        }
                    }
                });

                updateDivisionDetails(card, unitValues[index]?.division_detail || '');
                unitList.append(fragment);
            }

            captureUnitValues();
        }

        quantityInput.addEventListener('input', renderLaptopUnits);
        document.getElementById('quantity-decrease').addEventListener('click', () => {
            quantityInput.value = Math.max(1, Number(quantityInput.value) - 1);
            renderLaptopUnits();
        });
        document.getElementById('quantity-increase').addEventListener('click', () => {
            quantityInput.value = Math.min(100, Number(quantityInput.value) + 1);
            renderLaptopUnits();
        });
        unitList.addEventListener('input', captureUnitValues);
        unitList.addEventListener('change', (event) => {
            if (event.target.matches('select[name$="[division_id]"]')) {
                updateDivisionDetails(event.target.closest('.laptop-unit-card'));
            }
            captureUnitValues();
        });
        renderLaptopUnits();
    </script>
@endsection
