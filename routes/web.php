<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerProjectController;
use App\Http\Controllers\FreelancerActivationController;
use App\Http\Controllers\FreelancerProfileController;
use App\Http\Controllers\FreelancerServiceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
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
});

Route::middleware(['auth', 'role:freelancer'])->prefix('dashboard')->group(function () {
    Route::get('/freelancer-profile', [FreelancerProfileController::class, 'edit'])->name('freelancer.profile.edit');
    Route::patch('/freelancer-profile', [FreelancerProfileController::class, 'update'])->name('freelancer.profile.update');

    Route::resource('services', FreelancerServiceController::class)
        ->except(['show'])
        ->names('freelancer.services');
    Route::post('/services/{service}/submit', [FreelancerServiceController::class, 'submit'])->name('freelancer.services.submit');
    Route::post('/services/{service}/withdraw', [FreelancerServiceController::class, 'withdraw'])->name('freelancer.services.withdraw');

    Route::resource('portfolio', PortfolioController::class)
        ->except(['create', 'show'])
        ->parameters(['portfolio' => 'portfolioItem']);
});

require __DIR__.'/auth.php';
