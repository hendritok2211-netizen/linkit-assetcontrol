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
        Schema::create('phones', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('imei')->unique();
            $table->string('brand');
            $table->string('model');
            $table->string('os')->nullable(); // Android/iOS
            $table->string('storage')->nullable(); // 64GB, 128GB, etc
            $table->date('purchase_date')->nullable();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('available'); // available, borrowed, maintenance, retired
            $table->string('condition')->default('good'); // good, fair, poor
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phones');
    }
};
