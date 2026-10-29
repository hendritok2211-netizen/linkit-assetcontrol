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
        Schema::table('divisions', function (Blueprint $table) {
            $table->json('sections')->nullable()->after('description');
        });

        foreach (DB::table('divisions')->whereNotNull('description')->orderBy('id')->cursor() as $division) {
            $sections = array_values(array_filter(array_map(
                fn (string $section): string => trim($section),
                explode(',', $division->description),
            )));

            if ($sections !== []) {
                DB::table('divisions')->where('id', $division->id)->update([
                    'sections' => json_encode($sections, JSON_THROW_ON_ERROR),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropColumn('sections');
        });
    }
};
