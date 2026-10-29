@extends('layouts.app')

@section('title', 'Karyawan')

@section('content')
    <div class="welcome-row"><div><h2>Manajemen karyawan</h2><p>Tinjau pendaftaran dan kelola akses ke inventaris.</p></div></div>
    <section class="panel">
        <div class="table-wrap"><table><thead><tr><th>NAMA</th><th>EMAIL KERJA</th><th>DIVISI</th><th>STATUS AKUN</th><th>AKSI</th></tr></thead><tbody>
            @forelse ($employees as $employee)
                <tr><td><strong>{{ $employee->name }}</strong></td><td>{{ $employee->email }}</td><td>{{ $employee->division?->name ?? '—' }}</td>
                    <td><span class="badge {{ ! $employee->is_approved ? 'badge-amber' : ($employee->is_active ? 'badge-green' : 'badge-gray') }}">{{ ! $employee->is_approved ? 'Menunggu persetujuan' : ($employee->is_active ? 'Aktif' : 'Nonaktif') }}</span></td>
                    <td><div class="action-row">
                        @if (! $employee->is_approved)
                            <form method="POST" action="{{ route('employees.approve', $employee) }}">@csrf<button class="button button-small button-primary" type="submit">Setujui akun</button></form>
                        @else
                            <form method="POST" action="{{ route('employees.toggle-active', $employee) }}">@csrf<button class="button button-small {{ $employee->is_active ? 'button-outline' : 'button-primary' }}" type="submit">{{ $employee->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                        @endif
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-state">Belum ada pendaftaran karyawan.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="pagination-wrap">{{ $employees->links() }}</div>
    </section>
@endsection
