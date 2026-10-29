@extends('layouts.app')

@section('title', $division->exists ? 'Ubah Divisi' : 'Tambah Divisi')

@section('content')
    @php
        $initialSections = old('sections', $division->sections ?? []);
        $initialSections = is_array($initialSections) ? array_values($initialSections) : [];
    @endphp
    <div class="welcome-row">
        <div>
            <h2>{{ $division->exists ? 'Perbarui divisi' : 'Tambahkan divisi baru' }}</h2>
            <p>Tambahkan bagian atau jabatan agar bisa dipilih pada data laptop.</p>
        </div>
        <a class="button button-outline" href="{{ route('divisions.index') }}">← Kembali</a>
    </div>

    <section class="panel form-panel">
        <form method="POST" action="{{ $division->exists ? route('divisions.update', $division) : route('divisions.store') }}" class="form-stack">
            @csrf
            @if ($division->exists)
                @method('PUT')
            @endif
            <label>Nama divisi
                <input type="text" name="name" value="{{ old('name', $division->name) }}" maxlength="255" required autofocus>
            </label>
            <div class="division-sections-control">
                <div>
                    <strong>Bagian / Jabatan</strong>
                    <p class="muted">Tambahkan setiap bagian atau jabatan secara terpisah.</p>
                </div>
                <div id="division-section-list" class="division-section-inputs"></div>
                <button class="button button-outline button-small" id="add-division-section" type="button">＋ Tambah bagian / jabatan</button>
            </div>
            <template id="division-section-template">
                <div class="division-section-input">
                    <label>Bagian / jabatan
                        <input type="text" name="sections[__INDEX__]" maxlength="255" placeholder="Contoh: Teknologi Informasi" required>
                    </label>
                    <button class="button button-outline button-small" type="button" data-remove-section>Hapus</button>
                </div>
            </template>
            <div class="form-actions">
                <a class="button button-outline" href="{{ route('divisions.index') }}">Batal</a>
                <button class="button button-primary" type="submit">Simpan divisi</button>
            </div>
        </form>
    </section>
    <script>
        const sectionList = document.getElementById('division-section-list');
        const sectionTemplate = document.getElementById('division-section-template');
        let nextSectionIndex = 0;

        function addDivisionSection(value = '') {
            const fragment = sectionTemplate.content.cloneNode(true);
            const row = fragment.querySelector('.division-section-input');
            const input = row.querySelector('input');
            input.name = `sections[${nextSectionIndex}]`;
            input.value = value;
            nextSectionIndex += 1;
            sectionList.append(fragment);
        }

        @foreach ($initialSections as $section)
            addDivisionSection(@json($section));
        @endforeach

        document.getElementById('add-division-section').addEventListener('click', () => {
            addDivisionSection();
        });

        sectionList.addEventListener('click', (event) => {
            if (event.target.closest('[data-remove-section]')) {
                event.target.closest('.division-section-input').remove();
            }
        });
    </script>
@endsection
