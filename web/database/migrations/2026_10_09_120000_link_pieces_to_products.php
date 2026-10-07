<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Every piece belongs to a product on the site and sits in a branch.
    // Nullable only for pieces entered before this; the admin requires both.
    public function up(): void
    {
        Schema::table('pieces', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('barcode')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pieces', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
