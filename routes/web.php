<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerProjectController;
use App\Http\Controllers\FreelancerActivationController;
use App\Http\Controllers\FreelancerProfileController;
use App\Http\Controllers\FreelancerServiceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderWorkflowController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProposalDecisionController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('services.show');
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->whereNumber('project')->name('projects.show');
Route::get('/freelancers/{user}', [FreelancerProfileController::class, 'show'])->whereNumber('user')->name('freelancers.show');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/checkout/package/{package}', [CheckoutController::class, 'create'])->name('checkout.package');
    Route::post('/checkout/package/{package}', [CheckoutController::class, 'store'])->name('checkout.package.store');
    Route::get('/checkout/{order}', [PaymentController::class, 'checkout'])->whereNumber('order')->name('payments.checkout');
    Route::post('/checkout/{order}/payments', [PaymentController::class, 'start'])->whereNumber('order')->name('payments.start');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/simulate', [PaymentController::class, 'simulate'])->middleware('throttle:30,1')->name('payments.simulate');
    Route::get('/dashboard/payments', [ReceiptController::class, 'index'])->name('payments.index');
    Route::get('/dashboard/receipts/{payment}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/dashboard/receipts/{payment}/pdf', [ReceiptController::class, 'pdf'])->name('receipts.pdf');
    Route::get('/dashboard/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/dashboard/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/dashboard/orders/{order}/deliveries', [OrderWorkflowController::class, 'deliver'])->name('orders.deliver');
    Route::post('/dashboard/orders/{order}/revisions', [OrderWorkflowController::class, 'requestRevision'])->name('orders.revisions.store');
    Route::post('/dashboard/orders/{order}/complete', [OrderWorkflowController::class, 'complete'])->name('orders.complete');
    Route::get('/dashboard/deliveries/{delivery}/download', [OrderWorkflowController::class, 'download'])->name('deliveries.download');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/freelancer', [FreelancerActivationController::class, 'store'])->name('profile.freelancer.store');
});

Route::middleware(['auth', 'role:customer'])->prefix('dashboard')->group(function () {
    Route::resource('projects', CustomerProjectController::class)
        ->except(['destroy'])
        ->names('customer.projects');
    Route::post('/projects/{project}/close', [CustomerProjectController::class, 'close'])->name('customer.projects.close');
    Route::post('/proposals/{proposal}/accept', [ProposalDecisionController::class, 'accept'])->name('customer.proposals.accept');
    Route::post('/proposals/{proposal}/reject', [ProposalDecisionController::class, 'reject'])->name('customer.proposals.reject');
});

Route::middleware(['auth', 'role:freelancer'])->prefix('dashboard')->group(function () {
    Route::get('/freelancer-profile', [FreelancerProfileController::class, 'edit'])->name('freelancer.profile.edit');
    Route::patch('/freelancer-profile', [FreelancerProfileController::class, 'update'])->name('freelancer.profile.update');

    Route::resource('services', FreelancerServiceController::class)
        ->except(['show'])
        ->names('freelancer.services');
    Route::post('/services/{service}/submit', [FreelancerServiceController::class, 'submit'])->name('freelancer.services.submit');
    Route::post('/services/{service}/withdraw', [FreelancerServiceController::class, 'withdraw'])->name('freelancer.services.withdraw');

    Route::get('/proposals', [ProposalController::class, 'index'])->name('freelancer.proposals.index');
    Route::post('/projects/{project}/proposals', [ProposalController::class, 'store'])->name('freelancer.proposals.store');
    Route::get('/proposals/{proposal}/edit', [ProposalController::class, 'edit'])->name('freelancer.proposals.edit');
    Route::patch('/proposals/{proposal}', [ProposalController::class, 'update'])->name('freelancer.proposals.update');
    Route::post('/proposals/{proposal}/withdraw', [ProposalController::class, 'withdraw'])->name('freelancer.proposals.withdraw');

    Route::resource('portfolio', PortfolioController::class)
        ->except(['create', 'show'])
        ->parameters(['portfolio' => 'portfolioItem']);
});

require __DIR__.'/auth.php';
