<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laptop_asset_code_mappings', function (Blueprint $table) {
            $table->foreignId('laptop_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('previous_asset_code')->unique();
        });

        foreach (DB::table('laptops')->where('asset_code', 'like', 'LT-%')->orderBy('id')->cursor() as $laptop) {
            if (! preg_match('/^LT-[0-9A-HJKMNP-TV-Z]{26}$/', $laptop->asset_code)) {
                continue;
            }

            DB::table('laptop_asset_code_mappings')->insert([
                'laptop_id' => $laptop->id,
                'previous_asset_code' => $laptop->asset_code,
            ]);

            $baseAssetCode = 'LT-'.str_pad((string) $laptop->id, 4, '0', STR_PAD_LEFT);
            $assetCode = $baseAssetCode;
            $suffix = 1;

            while (DB::table('laptops')
                ->where('asset_code', $assetCode)
                ->where('id', '!=', $laptop->id)
                ->exists()) {
                $assetCode = $baseAssetCode.'-'.$suffix;
                $suffix++;
            }

            DB::table('laptops')->where('id', $laptop->id)->update(['asset_code' => $assetCode]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (DB::table('laptop_asset_code_mappings')->orderBy('laptop_id')->cursor() as $mapping) {
            DB::table('laptops')
                ->where('id', $mapping->laptop_id)
                ->update(['asset_code' => $mapping->previous_asset_code]);
        }

        Schema::dropIfExists('laptop_asset_code_mappings');
    }
};
