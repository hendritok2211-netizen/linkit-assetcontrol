<?php

namespace App\Models;

use Database\Factories\LaptopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['asset_code', 'serial_number', 'brand', 'model', 'ram', 'storage_capacity', 'purchase_date', 'division_id', 'division_detail', 'status', 'condition', 'notes'])]
class Laptop extends Model
{
    public const RAM_OPTIONS = ['4 GB', '8 GB', '16 GB', '24 GB', '32 GB', '64 GB'];

    public const STORAGE_OPTIONS = ['128 GB', '256 GB', '512 GB', '1 TB', '2 TB', '4 TB'];

    /** @use HasFactory<LaptopFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoan(): HasMany
    {
        return $this->loans()->whereIn('status', ['active', 'return_requested']);
    }
}
