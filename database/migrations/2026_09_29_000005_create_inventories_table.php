<?php

use App\Helpers\ConstantHelper;
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
        Schema::create(ConstantHelper::TBL_INVENTORIES, function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')
                ->constrained(ConstantHelper::TBL_WAREHOUSES)
                ->restrictOnDelete();
            $table->foreignId('ingredient_id')
                ->constrained(ConstantHelper::TBL_INGREDIENTS)
                ->restrictOnDelete();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('reserved_quantity', 15, 3)->default(0);
            $table->decimal('min_stock_level', 15, 3)->default(0);
            $table->decimal('max_stock_level', 15, 3)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'ingredient_id']);
            $table->index(['warehouse_id', 'quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TBL_INVENTORIES);
    }
};
