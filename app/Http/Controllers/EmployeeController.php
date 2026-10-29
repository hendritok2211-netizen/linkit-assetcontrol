<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(): View
    {
        return view('employees.index', [
            'employees' => User::query()->with('division')->where('role', 'employee')->orderBy('is_approved')->orderBy('name')->paginate(15),
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->role === 'employee', 404);

        $user->update(['is_approved' => true]);
        ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'event' => 'employee.approved',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'description' => "Akun karyawan {$user->name} disetujui.",
        ]);

        return back()->with('status', 'Akun karyawan berhasil disetujui.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'employee', 404);

        $user->update(['is_active' => ! $user->is_active]);
        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'event' => $user->is_active ? 'employee.activated' : 'employee.deactivated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'description' => "Akun karyawan {$user->name} ".($user->is_active ? 'diaktifkan.' : 'dinonaktifkan.'),
        ]);

        return back()->with('status', 'Status akun karyawan berhasil diperbarui.');
    }
}
