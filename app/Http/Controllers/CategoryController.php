<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(string $slug): View
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $services = Service::publiclyVisible()
            ->forCatalogCard()
            ->where('category_id', $category->id)
            ->latest('published_at')
            ->paginate(ServiceController::PER_PAGE);

        return view('categories.show', [
            'category' => $category,
            'services' => $services,
        ]);
    }
}
