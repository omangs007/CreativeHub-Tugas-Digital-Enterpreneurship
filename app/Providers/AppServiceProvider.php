<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Message;
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

        // Jumlah notifikasi dan pesan chat yang belum dibaca untuk navigasi dashboard.
        View::composer('layouts.navigation', function ($view) {
            $user = auth()->user();

            $view->with([
                'unreadNotifications' => $user ? $user->unreadNotifications()->count() : 0,
                'unreadMessages' => $user ? Message::whereNull('read_at')
                    ->where('sender_id', '!=', $user->id)
                    ->whereHas('conversation', fn ($q) => $q->forParticipant($user))
                    ->count() : 0,
            ]);
        });

        Blade::directive('rupiah', fn (string $expression) => "<?php echo e(\App\Support\Rupiah::format({$expression})); ?>");
    }
}
