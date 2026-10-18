<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\RecipeRepositoryInterface;
use App\Repositories\Contracts\StockLevelRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Eloquent\EloquentRecipeRepository;
use App\Repositories\Eloquent\EloquentStockLevelRepository;
use App\Repositories\Eloquent\EloquentStockMovementRepository;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
