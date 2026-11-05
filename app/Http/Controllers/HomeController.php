<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
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

        return view('home', [
            'categories' => $categories,
            'featuredServices' => $featuredServices,
        ]);
    }
}
