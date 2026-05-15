<?php

namespace App\Providers;

use App\Import\Contracts\CsvReaderInterface;
use App\Import\Contracts\ProductRepositoryInterface;
use App\Import\CsvReader;
use App\Import\ProductRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CsvReaderInterface::class, CsvReader::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
