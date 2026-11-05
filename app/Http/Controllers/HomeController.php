<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = Category::active()
            ->withCount(['services' => fn (Builder $q) => $q->publiclyVisible()])
            ->get();

        $featuredServices = Service::publiclyVisible()
            ->forCatalogCard()
            ->latest('published_at')
            ->limit(6)
            ->get();

        // Freelancer unggulan = yang paling banyak memiliki layanan aktif (tanpa rating palsu).
        $featuredFreelancers = User::publicFreelancers()
            ->whereHas('services', fn (Builder $q) => $q->publiclyVisible())
            ->withCount(['services as published_services_count' => fn (Builder $q) => $q->publiclyVisible()])
            ->with('freelancerProfile')
            ->orderByDesc('published_services_count')
            ->orderBy('name')
            ->limit(4)
            ->get();

        return view('home', [
            'categories' => $categories,
            'featuredServices' => $featuredServices,
            'featuredFreelancers' => $featuredFreelancers,
        ]);
    }
}
