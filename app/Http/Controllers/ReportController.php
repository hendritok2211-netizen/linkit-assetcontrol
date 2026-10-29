<?php

namespace App\Http\Controllers;

use App\Models\Laptop;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.index', [
            'laptops' => $this->query($request)->paginate(15)->withQueryString(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $laptops = $this->query($request)->get();

        return response()->streamDownload(function () use ($laptops): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Kode Aset',
                'Merek',
                'Perangkat',
                'RAM',
                'Penyimpanan',
                'Nomor Seri',
                'Divisi',
                'Bagian / Jabatan',
                'Tanggal Beli',
                'Kondisi Aset',
                'Status Aset',
                'Catatan Aset',
                'Pemegang Terakhir',
                'Tanggal Pinjam',
                'Status Peminjaman',
                'Tanggal Pengajuan Kembali',
                'Tanggal Dikembalikan',
                'Kondisi Saat Dipinjam',
                'Kondisi Saat Dikembalikan',
                'Catatan Serah Terima',
                'Catatan Pengembalian',
            ]);

            foreach ($laptops as $laptop) {
                $loan = $laptop->activeLoan->first();
                $row = [
                    $laptop->asset_code,
                    $laptop->brand,
                    $laptop->model,
                    $laptop->ram,
                    $laptop->storage_capacity,
                    $laptop->serial_number,
                    $laptop->division?->name,
                    $laptop->division_detail,
                    $laptop->purchase_date?->format('Y-m-d'),
                    $laptop->condition,
                    $laptop->status,
                    $laptop->notes,
                    $loan?->user?->name,
                    $loan?->checked_out_at?->format('Y-m-d H:i:s'),
                    $loan?->status,
                    $loan?->return_requested_at?->format('Y-m-d H:i:s'),
                    $loan?->returned_at?->format('Y-m-d H:i:s'),
                    $loan?->condition_out,
                    $loan?->condition_in,
                    $loan?->handover_notes,
                    $loan?->return_notes,
                ];

                fputcsv($output, array_map(
                    fn (?string $value): ?string => $this->escapeSpreadsheetFormula($value),
                    $row,
                ));
            }

            fclose($output);
        }, 'laporan-inventaris.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function query(Request $request): Builder
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['available', 'borrowed', 'maintenance', 'inactive'])],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        return Laptop::query()
            ->with(['division', 'activeLoan.user'])
            ->when($validated['q'] ?? null, function ($query, string $term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('asset_code', 'like', "%{$term}%")
                        ->orWhere('serial_number', 'like', "%{$term}%")
                        ->orWhere('brand', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%");
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['division_id'] ?? null, fn ($query, int $divisionId) => $query->where('division_id', $divisionId))
            ->when($validated['from_date'] ?? null, fn ($query, string $date) => $query->whereDate('purchase_date', '>=', $date))
            ->when($validated['to_date'] ?? null, fn ($query, string $date) => $query->whereDate('purchase_date', '<=', $date));
    }

    private function escapeSpreadsheetFormula(?string $value): ?string
    {
        if ($value !== null && preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
