<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laptops', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('serial_number')->unique();
            $table->string('brand');
            $table->string('model');
            $table->date('purchase_date')->nullable();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('available');
            $table->string('condition')->default('good');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laptops');
    }
};
