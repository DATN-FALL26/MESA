<?php

use App\Enums\Inventory\WarehouseType;
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
        Schema::create(ConstantHelper::TBL_WAREHOUSES, function (Blueprint $table) {
            $table->id();
            // store_id is nullable (for central warehouse), indexed for future FK when stores table is created
            $table->unsignedBigInteger('store_id')->nullable()->index();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('type', 30)->default(WarehouseType::STORE->value);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TBL_WAREHOUSES);
    }
};
