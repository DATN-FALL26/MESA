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
        Schema::create(ConstantHelper::TBL_INGREDIENTS, function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained(ConstantHelper::TBL_INGREDIENT_CATEGORIES)
                ->restrictOnDelete();
            $table->foreignId('unit_id')
                ->constrained(ConstantHelper::TBL_UNITS)
                ->restrictOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->unsignedInteger('shelf_life_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TBL_INGREDIENTS);
    }
};
