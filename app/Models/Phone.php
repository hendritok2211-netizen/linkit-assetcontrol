<?php

namespace App\Models;

use Database\Factories\PhoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['asset_code', 'imei', 'brand', 'model', 'os', 'storage', 'purchase_date', 'price', 'division_id', 'status', 'condition', 'notes'])]
class Phone extends Model
{
    public const OS_OPTIONS = ['Android', 'iOS'];

    public const STORAGE_OPTIONS = ['32 GB', '64 GB', '128 GB', '256 GB', '512 GB', '1 TB'];

    /** @use HasFactory<PhoneFactory> */
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
