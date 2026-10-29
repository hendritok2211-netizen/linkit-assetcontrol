<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Laptop;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    public function index(): View
    {
        return view('loans.index', [
            'loans' => Loan::query()->with(['laptop', 'user', 'issuer', 'verifier'])->latest('checked_out_at')->paginate(12),
            'employees' => User::query()->where('role', 'employee')->where('is_approved', true)->where('is_active', true)->orderBy('name')->get(),
            'laptops' => Laptop::query()->where('status', 'available')->orderBy('asset_code')->get(),
        ]);
    }

    public function mine(Request $request): View
    {
        return view('loans.mine', [
            'loans' => $request->user()->loans()->with('laptop')->latest('checked_out_at')->paginate(12),
        ]);
    }

    public function issue(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'laptop_id' => ['required', 'exists:laptops,id'],
            'user_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'employee')->where('is_approved', true)->where('is_active', true))],
            'condition_out' => ['required', Rule::in(['good', 'minor_damage', 'damaged'])],
            'handover_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $loan = DB::transaction(function () use ($validated): Loan {
            $laptop = Laptop::query()->lockForUpdate()->findOrFail($validated['laptop_id']);

            if ($laptop->status !== 'available') {
                throw ValidationException::withMessages(['laptop_id' => 'Laptop ini tidak lagi tersedia untuk dipinjam.']);
            }

            $loan = Loan::query()->create([
                ...$validated,
                'issued_by' => auth()->id(),
                'checked_out_at' => now(),
                'status' => 'active',
            ]);

            $laptop->update(['status' => 'borrowed']);

            ActivityLog::query()->create([
                'user_id' => auth()->id(),
                'event' => 'loan.issued',
                'subject_type' => Loan::class,
                'subject_id' => $loan->id,
                'description' => "Laptop {$laptop->asset_code} diserahkan kepada {$loan->user->name}.",
            ]);

            return $loan;
        });

        return redirect()->route('loans.index')->with('status', "Peminjaman {$loan->laptop->asset_code} berhasil dicatat.");
    }

    public function requestReturn(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless($loan->user_id === $request->user()->id, 403);

        if ($loan->status !== 'active') {
            return back()->withErrors(['return' => 'Pengembalian hanya dapat diajukan untuk peminjaman yang masih aktif.']);
        }

        $validated = $request->validate([
            'return_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $loan->update([
            'status' => 'return_requested',
            'return_requested_at' => now(),
            'return_notes' => $validated['return_notes'] ?? null,
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'event' => 'loan.return_requested',
            'subject_type' => Loan::class,
            'subject_id' => $loan->id,
            'description' => "Pengembalian laptop {$loan->laptop->asset_code} diajukan.",
        ]);

        return back()->with('status', 'Pengajuan pengembalian terkirim dan menunggu pemeriksaan admin.');
    }

    public function approveReturn(Request $request, Loan $loan): RedirectResponse
    {
        $validated = $request->validate([
            'serial_number' => ['required', 'string', 'max:100'],
            'condition_in' => ['required', Rule::in(['good', 'minor_damage', 'damaged'])],
            'return_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($loan, $validated): void {
            $lockedLoan = Loan::query()->lockForUpdate()->with('laptop')->findOrFail($loan->id);

            if ($lockedLoan->status !== 'return_requested') {
                throw ValidationException::withMessages(['return' => 'Pengembalian ini tidak lagi menunggu persetujuan.']);
            }

            if (! hash_equals($lockedLoan->laptop->serial_number, trim($validated['serial_number']))) {
                throw ValidationException::withMessages(['serial_number' => 'Nomor seri tidak cocok dengan data aset. Pengembalian belum dapat disetujui.']);
            }

            $lockedLoan->update([
                'status' => 'returned',
                'condition_in' => $validated['condition_in'],
                'return_notes' => $validated['return_notes'] ?? $lockedLoan->return_notes,
                'returned_at' => now(),
                'verified_by' => auth()->id(),
            ]);
            $lockedLoan->laptop->update([
                'condition' => $validated['condition_in'],
                'status' => $validated['condition_in'] === 'good' ? 'available' : 'maintenance',
            ]);

            ActivityLog::query()->create([
                'user_id' => auth()->id(),
                'event' => 'loan.return_approved',
                'subject_type' => Loan::class,
                'subject_id' => $lockedLoan->id,
                'description' => "Pengembalian laptop {$lockedLoan->laptop->asset_code} diverifikasi dan disetujui.",
            ]);
        });

        return redirect()->route('loans.index')->with('status', 'Nomor seri cocok; pengembalian berhasil diverifikasi.');
    }

    public function rejectReturn(Request $request, Loan $loan): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_note' => ['required', 'string', 'max:2000'],
        ]);

        if ($loan->status !== 'return_requested') {
            return back()->withErrors(['return' => 'Hanya pengajuan pengembalian yang menunggu persetujuan yang dapat ditolak.']);
        }

        $loan->update([
            'status' => 'active',
            'return_requested_at' => null,
            'return_notes' => $validated['rejection_note'],
        ]);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'event' => 'loan.return_rejected',
            'subject_type' => Loan::class,
            'subject_id' => $loan->id,
            'description' => "Pengajuan pengembalian {$loan->laptop->asset_code} ditolak: {$validated['rejection_note']}",
        ]);

        return back()->with('status', 'Pengajuan pengembalian ditolak dan peminjaman tetap aktif.');
    }
}
