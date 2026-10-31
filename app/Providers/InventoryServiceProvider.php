<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\IngredientRepositoryInterface;
use App\Repositories\Contracts\RecipeRepositoryInterface;
use App\Repositories\Contracts\StockLevelRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Repositories\Eloquent\EloquentIngredientRepository;
use App\Repositories\Eloquent\EloquentRecipeRepository;
use App\Repositories\Eloquent\EloquentStockLevelRepository;
use App\Repositories\Eloquent\EloquentStockMovementRepository;
use App\Repositories\Eloquent\EloquentSupplierRepository;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    /**
     * Đăng ký liên kết Repository Interfaces với Eloquent Implementations.
     */
    public function register(): void
    {
        $this->app->bind(
            StockMovementRepositoryInterface::class,
            EloquentStockMovementRepository::class
        );

        $this->app->bind(
            StockLevelRepositoryInterface::class,
            EloquentStockLevelRepository::class
        );

        $this->app->bind(
            RecipeRepositoryInterface::class,
            EloquentRecipeRepository::class
        );

        $this->app->bind(
            IngredientRepositoryInterface::class,
            EloquentIngredientRepository::class
        );

        $this->app->bind(
            SupplierRepositoryInterface::class,
            EloquentSupplierRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
