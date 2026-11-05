<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Deteksi query N+1 lebih awal saat development dan testing.
        Model::preventLazyLoading(! $this->app->isProduction());

        View::composer('layouts.public', function ($view) {
            $view->with('navCategories', Category::active()->get(['id', 'name', 'slug']));
        });

        Blade::directive('rupiah', fn (string $expression) => "<?php echo e(\App\Support\Rupiah::format({$expression})); ?>");
    }
}
