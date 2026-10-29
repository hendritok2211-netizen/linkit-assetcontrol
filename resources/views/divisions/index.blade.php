@extends('layouts.app')

@section('title', 'Divisi')

@section('content')
    <div class="welcome-row">
        <div>
            <h2>Kelola divisi</h2>
            <p>Tambah dan perbarui divisi yang digunakan karyawan serta inventaris.</p>
        </div>
        <a class="button button-primary" href="{{ route('divisions.create') }}">＋ Tambah divisi</a>
    </div>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>NAMA DIVISI</th><th>BAGIAN / JABATAN</th><th>KARYAWAN</th><th>LAPTOP</th><th>AKSI</th></tr>
                </thead>
                <tbody>
                    @forelse ($divisions as $division)
                        <tr>
                            <td><strong>{{ $division->name }}</strong></td>
                            <td>
                                @if ($division->sections)
                                    <div class="division-section-list" aria-label="Bagian dan jabatan {{ $division->name }}">
                                        @foreach ($division->sections as $section)
                                            <span class="division-description-item">{{ $section }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>{{ $division->users_count }}</td>
                            <td>{{ $division->laptops_count }}</td>
                            <td>
                                <div class="action-row">
                                    <a class="text-link" href="{{ route('divisions.edit', $division) }}">Ubah</a>
                                    <form method="POST" action="{{ route('divisions.destroy', $division) }}" onsubmit="return confirm('Hapus divisi ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">Belum ada divisi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $divisions->links() }}</div>
    </section>
@endsection
