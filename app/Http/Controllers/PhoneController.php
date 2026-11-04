<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Division;
use App\Models\Phone;
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

class PhoneController extends Controller
{
    public function index(Request $request): View
    {
        $phones = Phone::query()
            ->with(['division', 'activeLoan.user'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = $request->string('q')->toString();
                $query->where(function ($query) use ($term): void {
                    $query->where('asset_code', 'like', "%{$term}%")
                        ->orWhere('imei', 'like', "%{$term}%")
                        ->orWhere('brand', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('division_id'), fn ($query) => $query->where('division_id', $request->integer('division_id')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('phones.index', [
            'phones' => $phones,
            'divisions' => Division::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('phones.form', [
            'phone' => new Phone,
            'divisions' => Division::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePhoneUnits($request);
        $units = $validated['units'];

        DB::transaction(function () use ($units): void {
            foreach ($units as $unit) {
                $assetCode = $unit['asset_code'] ?? null;
                $usesCustomAssetCode = filled($assetCode);
                $phone = Phone::query()->create([
                    ...$unit,
                    'asset_code' => $usesCustomAssetCode
                        ? $assetCode
                        : 'PH-TMP-'.Str::upper((string) Str::ulid()),
                ]);

                if (! $usesCustomAssetCode) {
                    $this->assignShortAssetCode($phone);
                }

                $this->log('phone.created', $phone, "Handphone {$phone->asset_code} ditambahkan.");
            }
        });

        return redirect()->route('phones.index')->with(
            'status',
            count($units) === 1
                ? 'Data handphone berhasil ditambahkan.'
                : count($units).' data handphone berhasil ditambahkan.',
        );
    }

    public function edit(Phone $phone): View
    {
        return view('phones.form', [
            'phone' => $phone,
            'divisions' => Division::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Phone $phone): RedirectResponse
    {
        $validated = $this->validatePhoneUnits($request, $phone);

        if ($phone->activeLoan()->exists()) {
            $validated['units'][0]['status'] = 'borrowed';
        } elseif ($validated['units'][0]['status'] === 'borrowed') {
            throw ValidationException::withMessages([
                'units.0.status' => 'Status dipinjam hanya dapat digunakan saat handphone memiliki transaksi peminjaman aktif.',
            ]);
        }

        $units = $validated['units'];

        DB::transaction(function () use ($phone, $units): void {
            $primaryUnit = array_shift($units);
            if (blank($primaryUnit['asset_code'])) {
                $primaryUnit['asset_code'] = $phone->asset_code;
            }
            $phone->update($primaryUnit);
            $this->log('phone.updated', $phone, "Data handphone {$phone->asset_code} diperbarui.");

            foreach ($units as $unit) {
                $assetCode = $unit['asset_code'] ?? null;
                $additionalPhone = Phone::query()->create([
                    ...$unit,
                    'asset_code' => filled($assetCode) ? $assetCode : 'PH-TMP-'.Str::upper((string) Str::ulid()),
                    'status' => $unit['status'] === 'borrowed' ? 'available' : $unit['status'],
                ]);

                if (! filled($assetCode)) {
                    $this->assignShortAssetCode($additionalPhone);
                }
                $this->log('phone.created', $additionalPhone, "Handphone {$additionalPhone->asset_code} ditambahkan.");
            }
        });

        $quantity = count($validated['units']);

        return redirect()->route('phones.index')->with(
            'status',
            $quantity === 1
                ? 'Data handphone berhasil diperbarui.'
                : "Data handphone diperbarui dan total {$quantity} perangkat berhasil disimpan.",
        );
    }

    /**
     * @return array{quantity: int, units: list<array<string, mixed>>}
     */
    private function validatePhoneUnits(Request $request, ?Phone $phone = null): array
    {
        $quantity = (int) $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ])['quantity'];

        $rules = [
            'units' => ['required', 'array', 'size:'.$quantity],
            'units.*' => ['required', 'array:asset_code,imei,brand,model,os,storage,purchase_date,price,division_id,condition,status,notes'],
        ];

        $messages = [
            'units.required' => 'Isi data untuk setiap perangkat.',
            'units.array' => 'Data perangkat harus diisi.',
            'units.size' => 'Jumlah data harus sama dengan jumlah perangkat.',
            'units.*.imei.required' => 'IMEI perangkat ini wajib diisi.',
            'units.*.os.required' => 'Pilih sistem operasi handphone.',
            'units.*.os.in' => 'Pilihan OS tidak tersedia.',
            'units.*.storage.required' => 'Pilih kapasitas penyimpanan handphone.',
            'units.*.storage.in' => 'Pilihan penyimpanan tidak tersedia.',
        ];

        foreach (range(0, $quantity - 1) as $index) {
            $assetCodeRule = Rule::unique('phones', 'asset_code');
            $imeiRule = Rule::unique('phones', 'imei');

            if ($phone !== null && $index === 0) {
                $assetCodeRule->ignore($phone->id);
                $imeiRule->ignore($phone->id);
            }

            $statusOptions = $phone !== null && $index === 0 && $phone->status === 'borrowed'
                ? ['available', 'maintenance', 'inactive', 'borrowed']
                : ['available', 'maintenance', 'inactive'];

            $prefix = "units.{$index}.";

            $rules[$prefix.'asset_code'] = ['nullable', 'string', 'max:40', $assetCodeRule];
            $rules[$prefix.'imei'] = ['required', 'string', 'max:20', $imeiRule];
            $rules[$prefix.'brand'] = ['required', 'string', 'max:100'];
            $rules[$prefix.'model'] = ['required', 'string', 'max:150'];
            $rules[$prefix.'os'] = ['required', Rule::in(Phone::OS_OPTIONS)];
            $rules[$prefix.'storage'] = ['required', Rule::in(Phone::STORAGE_OPTIONS)];
            $rules[$prefix.'purchase_date'] = ['nullable', 'date'];
            $rules[$prefix.'price'] = ['nullable', 'numeric', 'min:0', 'max:999999999999.99'];
            $rules[$prefix.'division_id'] = ['nullable', 'exists:divisions,id'];
            $rules[$prefix.'condition'] = ['required', Rule::in(['good', 'minor_damage', 'damaged'])];
            $rules[$prefix.'status'] = ['required', Rule::in($statusOptions)];
            $rules[$prefix.'notes'] = ['nullable', 'string', 'max:2000'];
        }

        $validated = $request->validate($rules, $messages);
        $units = array_values($validated['units']);
        $assetCodes = [];
        $imeis = [];

        foreach ($units as $index => $unit) {
            $assetCode = mb_strtolower(trim((string) ($unit['asset_code'] ?? '')));
            $imei = mb_strtolower(trim($unit['imei']));

            if ($assetCode !== '' && in_array($assetCode, $assetCodes, true)) {
                throw ValidationException::withMessages([
                    "units.{$index}.asset_code" => 'Kode aset harus berbeda untuk setiap perangkat.',
                ]);
            }

            if (in_array($imei, $imeis, true)) {
                throw ValidationException::withMessages([
                    "units.{$index}.imei" => 'IMEI harus berbeda untuk setiap perangkat.',
                ]);
            }

            $assetCodes[] = $assetCode;
            $imeis[] = $imei;
        }

        return [
            'quantity' => $quantity,
            'units' => $units,
        ];
    }

    private function assignShortAssetCode(Phone $phone): void
    {
        $baseAssetCode = 'PH-'.str_pad((string) $phone->id, 4, '0', STR_PAD_LEFT);
        $assetCode = $baseAssetCode;
        $suffix = 1;

        while (Phone::query()
            ->where('asset_code', $assetCode)
            ->where('id', '!=', $phone->id)
            ->exists()) {
            $assetCode = $baseAssetCode.'-'.$suffix;
            $suffix++;
        }

        $phone->update(['asset_code' => $assetCode]);
    }

    public function destroy(Phone $phone): RedirectResponse
    {
        if ($phone->loans()->exists()) {
            return back()->withErrors(['phone' => 'Handphone yang memiliki riwayat peminjaman tidak dapat dihapus. Ubah statusnya menjadi nonaktif.']);
        }

        $assetCode = $phone->asset_code;
        $phone->delete();
        $this->log('phone.deleted', null, "Handphone {$assetCode} dihapus.");

        return redirect()->route('phones.index')->with('status', 'Data handphone berhasil dihapus.');
    }

    public function qr(Phone $phone): Response
    {
        $writer = new PngWriter;
        $code = new QrCode(
            data: $phone->asset_code,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 320,
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
        $result = $writer->write($code);

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="'.$phone->asset_code.'.png"',
        ]);
    }

    public function label(Phone $phone): View
    {
        return view('phones.label', ['phone' => $phone]);
    }

    private function log(string $event, ?Phone $phone, string $description): void
    {
        ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'event' => $event,
            'subject_type' => $phone ? Phone::class : null,
            'subject_id' => $phone?->id,
            'description' => $description,
        ]);
    }
}
