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
        Schema::table('laptops', function (Blueprint $table) {
            $table->string('ram')->nullable()->after('model');
            $table->string('storage_capacity')->nullable()->after('ram');
        });

        foreach (DB::table('laptops')->whereNotNull('notes')->orderBy('id')->cursor() as $laptop) {
            $specifications = [];

            if (preg_match('/\bRAM\s*(4|8|16|24|32|64)\s*GB\b/i', $laptop->notes, $ramMatch) === 1) {
                $specifications['ram'] = $ramMatch[1].' GB';
            }

            if (preg_match('/\b(?:SSD|HDD)\s*(128|256|512)\s*GB\b/i', $laptop->notes, $storageMatch) === 1) {
                $specifications['storage_capacity'] = $storageMatch[1].' GB';
            } elseif (preg_match('/\b(?:SSD|HDD)\s*(1|2|4)\s*TB\b/i', $laptop->notes, $storageMatch) === 1) {
                $specifications['storage_capacity'] = $storageMatch[1].' TB';
            }

            if ($specifications !== []) {
                DB::table('laptops')->where('id', $laptop->id)->update($specifications);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laptops', function (Blueprint $table) {
            $table->dropColumn(['ram', 'storage_capacity']);
        });
    }
};
