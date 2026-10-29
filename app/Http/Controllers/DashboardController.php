<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Laptop;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            return view('dashboard.employee', [
                'activeLoans' => $user->loans()->with('laptop')->whereIn('status', ['active', 'return_requested'])->latest()->get(),
                'loanHistory' => $user->loans()->with('laptop')->where('status', 'returned')->latest('returned_at')->limit(8)->get(),
            ]);
        }

        $loans = Loan::query()->with(['laptop', 'user'])->latest('checked_out_at');

        return view('dashboard.admin', [
            'totalLaptops' => Laptop::query()->count(),
            'availableLaptops' => Laptop::query()->where('status', 'available')->count(),
            'borrowedLaptops' => Laptop::query()->where('status', 'borrowed')->count(),
            'maintenanceLaptops' => Laptop::query()->where('status', 'maintenance')->count(),
            'pendingEmployees' => User::query()->where('role', 'employee')->where('is_approved', false)->count(),
            'pendingReturns' => Loan::query()->where('status', 'return_requested')->count(),
            'recentLoans' => $loans->limit(6)->get(),
            'recentActivity' => ActivityLog::query()->with('user')->latest()->limit(8)->get(),
        ]);
    }
}
