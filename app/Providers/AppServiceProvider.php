<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\User;
use App\Payments\DummyPaymentGateway;
use App\Payments\PaymentGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // MVP memakai gateway simulasi. Ganti binding ini saat integrasi provider sungguhan.
        $this->app->bind(PaymentGateway::class, DummyPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Deteksi query N+1 lebih awal saat development dan testing.
        Model::preventLazyLoading(! $this->app->isProduction());

        Gate::define('viewFinancialReports', fn (User $user) => $user->is_active && $user->isAdmin());

        View::composer('layouts.public', function ($view) {
            $view->with('navCategories', Category::active()->get(['id', 'name', 'slug']));
        });

        Blade::directive('rupiah', fn (string $expression) => "<?php echo e(\App\Support\Rupiah::format({$expression})); ?>");
    }
}
