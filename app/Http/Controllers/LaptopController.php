<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Division;
use App\Models\Laptop;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class LaptopController extends Controller
{
    public function index(Request $request): View
    {
        $laptops = Laptop::query()
            ->with(['division', 'activeLoan.user'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = $request->string('q')->toString();
                $query->where(function ($query) use ($term): void {
                    $query->where('asset_code', 'like', "%{$term}%")
                        ->orWhere('serial_number', 'like', "%{$term}%")
                        ->orWhere('brand', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('division_id'), fn ($query) => $query->where('division_id', $request->integer('division_id')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('laptops.index', [
            'laptops' => $laptops,
            'divisions' => Division::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('laptops.form', [
            'laptop' => new Laptop,
            'divisions' => Division::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateLaptopUnits($request);
        $units = $validated['units'];

        DB::transaction(function () use ($units): void {
            foreach ($units as $unit) {
                $assetCode = $unit['asset_code'] ?? null;
                $usesCustomAssetCode = filled($assetCode);
                $laptop = Laptop::query()->create([
                    ...$unit,
                    'asset_code' => $usesCustomAssetCode
                        ? $assetCode
                        : 'LT-TMP-'.Str::upper((string) Str::ulid()),
                ]);

                if (! $usesCustomAssetCode) {
                    $this->assignShortAssetCode($laptop);
                }

                $this->log('laptop.created', $laptop, "Laptop {$laptop->asset_code} ditambahkan.");
            }
        });

        return redirect()->route('laptops.index')->with(
            'status',
            count($units) === 1
                ? 'Data laptop berhasil ditambahkan.'
                : count($units).' data laptop berhasil ditambahkan.',
        );
    }

    public function edit(Laptop $laptop): View
    {
        return view('laptops.form', [
            'laptop' => $laptop,
            'divisions' => Division::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Laptop $laptop): RedirectResponse
    {
        $validated = $this->validateLaptopUnits($request, $laptop);

        if ($laptop->activeLoan()->exists()) {
            $validated['units'][0]['status'] = 'borrowed';
        } elseif ($validated['units'][0]['status'] === 'borrowed') {
            throw ValidationException::withMessages([
                'units.0.status' => 'Status dipinjam hanya dapat digunakan saat laptop memiliki transaksi peminjaman aktif.',
            ]);
        }

        $units = $validated['units'];

        DB::transaction(function () use ($laptop, $units): void {
            $primaryUnit = array_shift($units);
            if (blank($primaryUnit['asset_code'])) {
                $primaryUnit['asset_code'] = $laptop->asset_code;
            }
            $laptop->update($primaryUnit);
            $this->log('laptop.updated', $laptop, "Data laptop {$laptop->asset_code} diperbarui.");

            foreach ($units as $unit) {
                $assetCode = $unit['asset_code'] ?? null;
                $additionalLaptop = Laptop::query()->create([
                    ...$unit,
                    'asset_code' => filled($assetCode) ? $assetCode : 'LT-TMP-'.Str::upper((string) Str::ulid()),
                    'status' => $unit['status'] === 'borrowed' ? 'available' : $unit['status'],
                ]);

                if (! filled($assetCode)) {
                    $this->assignShortAssetCode($additionalLaptop);
                }
                $this->log('laptop.created', $additionalLaptop, "Laptop {$additionalLaptop->asset_code} ditambahkan.");
            }
        });

        $quantity = count($validated['units']);

        return redirect()->route('laptops.index')->with(
            'status',
            $quantity === 1
                ? 'Data laptop berhasil diperbarui.'
                : "Data laptop diperbarui dan total {$quantity} perangkat berhasil disimpan.",
        );
    }

    /**
     * @return array{quantity: int, units: list<array<string, mixed>>}
     */
    private function validateLaptopUnits(Request $request, ?Laptop $laptop = null): array
    {
        $quantity = (int) $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ])['quantity'];
        $rules = [
            'units' => ['required', 'array', 'size:'.$quantity],
            'units.*' => ['required', 'array:asset_code,serial_number,brand,model,ram,storage_capacity,purchase_date,division_id,division_detail,condition,status,notes'],
        ];
        $messages = [
            'units.required' => 'Isi data untuk setiap perangkat.',
            'units.array' => 'Data perangkat harus diisi.',
            'units.size' => 'Jumlah data harus sama dengan jumlah perangkat.',
            'units.*.serial_number.required' => 'Nomor seri perangkat ini wajib diisi.',
            'units.*.ram.required' => 'Pilih kapasitas RAM laptop.',
            'units.*.ram.in' => 'Pilihan RAM tidak tersedia.',
            'units.*.storage_capacity.required' => 'Pilih kapasitas penyimpanan laptop.',
            'units.*.storage_capacity.in' => 'Pilihan penyimpanan tidak tersedia.',
        ];

        foreach (range(0, $quantity - 1) as $index) {
            $assetCodeRule = Rule::unique('laptops', 'asset_code');
            $serialNumberRule = Rule::unique('laptops', 'serial_number');

            if ($laptop !== null && $index === 0) {
                $assetCodeRule->ignore($laptop->id);
                $serialNumberRule->ignore($laptop->id);
            }

            $statusOptions = $laptop !== null && $index === 0 && $laptop->status === 'borrowed'
                ? ['available', 'maintenance', 'inactive', 'borrowed']
                : ['available', 'maintenance', 'inactive'];
            $prefix = "units.{$index}.";

            $rules[$prefix.'asset_code'] = ['nullable', 'string', 'max:40', $assetCodeRule];
            $rules[$prefix.'serial_number'] = ['required', 'string', 'max:100', $serialNumberRule];
            $rules[$prefix.'brand'] = ['required', 'string', 'max:100'];
            $rules[$prefix.'model'] = ['required', 'string', 'max:150'];
            $rules[$prefix.'ram'] = ['required', Rule::in(Laptop::RAM_OPTIONS)];
            $rules[$prefix.'storage_capacity'] = ['required', Rule::in(Laptop::STORAGE_OPTIONS)];
            $rules[$prefix.'purchase_date'] = ['nullable', 'date'];
            $rules[$prefix.'division_id'] = ['nullable', 'exists:divisions,id'];
            $rules[$prefix.'division_detail'] = ['nullable', 'string', 'max:255'];
            $rules[$prefix.'condition'] = ['required', Rule::in(['good', 'minor_damage', 'damaged'])];
            $rules[$prefix.'status'] = ['required', Rule::in($statusOptions)];
            $rules[$prefix.'notes'] = ['nullable', 'string', 'max:2000'];
        }

        $validated = $request->validate($rules, $messages);
        $units = array_values($validated['units']);
        $divisionIds = collect($units)
            ->pluck('division_id')
            ->filter()
            ->unique()
            ->all();
        $divisions = Division::query()->whereKey($divisionIds)->get()->keyBy('id');
        $assetCodes = [];
        $serialNumbers = [];

        foreach ($units as $index => $unit) {
            $division = $divisions->get($unit['division_id'] ?? null);
            $allowedDetails = array_filter(array_map(
                fn (string $detail): string => trim($detail),
                $division?->sections ?? [],
            ));

            if (filled($unit['division_detail'] ?? null)
                && ! in_array($unit['division_detail'], $allowedDetails, true)) {
                throw ValidationException::withMessages([
                    "units.{$index}.division_detail" => 'Rincian harus sesuai dengan deskripsi divisi yang dipilih.',
                ]);
            }

            $assetCode = mb_strtolower(trim((string) ($unit['asset_code'] ?? '')));
            $serialNumber = mb_strtolower(trim($unit['serial_number']));

            if ($assetCode !== '' && in_array($assetCode, $assetCodes, true)) {
                throw ValidationException::withMessages([
                    "units.{$index}.asset_code" => 'Kode aset harus berbeda untuk setiap perangkat.',
                ]);
            }

            if (in_array($serialNumber, $serialNumbers, true)) {
                throw ValidationException::withMessages([
                    "units.{$index}.serial_number" => 'Nomor seri harus berbeda untuk setiap perangkat.',
                ]);
            }

            $assetCodes[] = $assetCode;
            $serialNumbers[] = $serialNumber;
        }

        return [
            'quantity' => $quantity,
            'units' => $units,
        ];
    }

    private function assignShortAssetCode(Laptop $laptop): void
    {
        $baseAssetCode = 'LT-'.str_pad((string) $laptop->id, 4, '0', STR_PAD_LEFT);
        $assetCode = $baseAssetCode;
        $suffix = 1;

        while (Laptop::query()
            ->where('asset_code', $assetCode)
            ->where('id', '!=', $laptop->id)
            ->exists()) {
            $assetCode = $baseAssetCode.'-'.$suffix;
            $suffix++;
        }

        $laptop->update(['asset_code' => $assetCode]);
    }

    public function destroy(Laptop $laptop): RedirectResponse
    {
        if ($laptop->loans()->exists()) {
            return back()->withErrors(['laptop' => 'Laptop yang memiliki riwayat peminjaman tidak dapat dihapus. Ubah statusnya menjadi nonaktif.']);
        }

        $assetCode = $laptop->asset_code;
        $laptop->delete();
        $this->log('laptop.deleted', null, "Laptop {$assetCode} dihapus.");

        return redirect()->route('laptops.index')->with('status', 'Data laptop berhasil dihapus.');
    }

    public function qr(Laptop $laptop): Response
    {
        $writer = new PngWriter;
        $code = new QrCode(
            data: $laptop->asset_code,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 320,
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
        $result = $writer->write($code);

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="'.$laptop->asset_code.'.png"',
        ]);
    }

    public function label(Laptop $laptop): View
    {
        return view('laptops.label', ['laptop' => $laptop]);
    }

    public function scan(): View
    {
        return view('laptops.scan');
    }

    public function verify(Request $request): View
    {
        $validated = $request->validate([
            'asset_code' => ['required', 'string', 'max:100'],
        ]);

        return view('laptops.scan', [
            'laptop' => Laptop::query()
                ->with(['division', 'activeLoan.user'])
                ->where(fn ($query) => $query
                    ->where('asset_code', $validated['asset_code'])
                    ->orWhere('serial_number', $validated['asset_code']))
                ->first(),
            'searchedCode' => $validated['asset_code'],
        ]);
    }

    public function audit(Request $request, Laptop $laptop): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['inspect', 'send_service', 'finish_service', 'record_return'])],
            'condition' => ['required', Rule::in(['good', 'minor_damage', 'damaged'])],
            'serial_number' => [
                Rule::requiredIf($request->input('action') === 'record_return'),
                'nullable',
                'string',
                'max:100',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($laptop, $validated): void {
            $lockedLaptop = Laptop::query()->lockForUpdate()->findOrFail($laptop->id);

            if ($validated['action'] === 'record_return') {
                $loan = $lockedLaptop->activeLoan()->lockForUpdate()->first();

                if ($loan === null) {
                    throw ValidationException::withMessages([
                        'action' => 'Tidak ada peminjaman aktif untuk dicatat sebagai pengembalian.',
                    ]);
                }

                if (! hash_equals($lockedLaptop->serial_number, trim($validated['serial_number']))) {
                    throw ValidationException::withMessages([
                        'serial_number' => 'Nomor seri tidak cocok dengan data aset. Pengembalian belum dapat dicatat.',
                    ]);
                }

                $loan->update([
                    'status' => 'returned',
                    'condition_in' => $validated['condition'],
                    'return_notes' => $validated['notes'] ?? null,
                    'returned_at' => now(),
                    'verified_by' => auth()->id(),
                ]);
                $lockedLaptop->update([
                    'condition' => $validated['condition'],
                    'status' => $validated['condition'] === 'good' ? 'available' : 'maintenance',
                ]);

                ActivityLog::query()->create([
                    'user_id' => auth()->id(),
                    'event' => 'loan.return_approved',
                    'subject_type' => Loan::class,
                    'subject_id' => $loan->id,
                    'description' => "Pengembalian laptop {$lockedLaptop->asset_code} diverifikasi dan dicatat melalui pemindaian aset.",
                    'metadata' => [
                        'action' => $validated['action'],
                        'condition' => $validated['condition'],
                        'notes' => $validated['notes'] ?? null,
                    ],
                ]);

                return;
            }

            if ($validated['action'] === 'send_service' && in_array($lockedLaptop->status, ['borrowed', 'inactive'], true)) {
                throw ValidationException::withMessages(['action' => 'Laptop yang sedang dipinjam atau nonaktif tidak dapat dikirim ke perawatan.']);
            }

            if ($validated['action'] === 'finish_service' && $lockedLaptop->status !== 'maintenance') {
                throw ValidationException::withMessages(['action' => 'Hanya laptop dalam status perawatan yang dapat ditandai selesai diservis.']);
            }

            $status = $lockedLaptop->status;

            if ($validated['action'] === 'send_service') {
                $status = 'maintenance';
            } elseif ($validated['action'] === 'finish_service') {
                $status = $validated['condition'] === 'good' ? 'available' : 'maintenance';
            } elseif ($status === 'available' && $validated['condition'] !== 'good') {
                $status = 'maintenance';
            }

            $lockedLaptop->update([
                'condition' => $validated['condition'],
                'status' => $status,
            ]);

            $actionLabels = [
                'inspect' => 'diperiksa',
                'send_service' => 'dikirim ke perawatan',
                'finish_service' => 'selesai diservis',
                'record_return' => 'dikembalikan',
            ];
            $description = 'Laptop '.$lockedLaptop->asset_code.' '.$actionLabels[$validated['action']].' (kondisi: '.$validated['condition'].').';

            if (! empty($validated['notes'])) {
                $description .= ' '.$validated['notes'];
            }

            ActivityLog::query()->create([
                'user_id' => auth()->id(),
                'event' => 'laptop.'.$validated['action'],
                'subject_type' => Laptop::class,
                'subject_id' => $lockedLaptop->id,
                'description' => $description,
                'metadata' => [
                    'action' => $validated['action'],
                    'condition' => $validated['condition'],
                    'notes' => $validated['notes'] ?? null,
                ],
            ]);
        });

        return redirect()->route('laptops.scan')->with('status', 'Pemeriksaan aset berhasil dicatat.');
    }

    private function log(string $event, ?Laptop $laptop, string $description): void
    {
        ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'event' => $event,
            'subject_type' => $laptop ? Laptop::class : null,
            'subject_id' => $laptop?->id,
            'description' => $description,
        ]);
    }
}
