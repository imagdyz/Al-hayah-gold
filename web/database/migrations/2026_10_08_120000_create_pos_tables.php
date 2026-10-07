<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Physical pieces in the shop, one row per tagged item.
        Schema::create('pieces', function (Blueprint $table) {
            $table->id();
            $table->string('barcode', 40)->unique();
            $table->string('name');
            $table->string('category', 20)->index();
            $table->unsignedTinyInteger('karat');
            $table->decimal('weight_g', 8, 3);
            $table->decimal('making_fee', 10, 2)->default(0);
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('status', 20)->default('in_stock')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->string('settlement', 20);
            $table->decimal('base_24', 10, 2)->nullable();
            $table->decimal('sales_total', 12, 2)->default(0);
            $table->decimal('purchases_total', 12, 2)->default(0);
            $table->decimal('net', 12, 2)->default(0);
            $table->string('status', 20)->default('completed')->index();
            $table->string('void_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamp('issued_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10);
            $table->foreignId('piece_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->unsignedTinyInteger('karat');
            $table->decimal('gross_weight', 8, 3);
            $table->decimal('net_weight', 8, 3);
            $table->decimal('gram_price', 10, 2);
            $table->decimal('making_fee', 10, 2)->default(0);
            $table->decimal('cost', 12, 2)->nullable();
            $table->decimal('total', 12, 2);
            $table->index(['invoice_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('pieces');
    }
};
