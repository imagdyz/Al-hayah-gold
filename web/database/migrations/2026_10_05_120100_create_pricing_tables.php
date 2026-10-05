<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Spot price of one gram of 24k in EGP, before our margins.
        Schema::create('gold_prices', function (Blueprint $table) {
            $table->id();
            $table->decimal('base_24', 10, 2);
            $table->string('source', 30)->default('manual');
            $table->timestamp('recorded_at')->index();
        });

        // Margins in EGP added to (sell) or taken from (buy) the karat's base price.
        Schema::create('karat_margins', function (Blueprint $table) {
            $table->id();
            $table->string('karat', 8)->unique(); // 24, 21, 18, coin
            $table->decimal('sell_margin', 10, 2)->default(0);
            $table->decimal('buy_margin', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('karat_margins');
        Schema::dropIfExists('gold_prices');
    }
};
