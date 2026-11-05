<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public const PER_PAGE = 12;

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'max:120'],
        ]);

        $search = trim($validated['q'] ?? '');
        $categories = Category::active()->get();
        $selectedCategory = $categories->firstWhere('slug', $validated['kategori'] ?? null);

        $services = Service::publiclyVisible()
            ->forCatalogCard()
            ->when($search !== '', fn (Builder $q) => $q->whereLike('title', "%{$search}%"))
            ->when($selectedCategory, fn (Builder $q) => $q->where('category_id', $selectedCategory->id))
            ->latest('published_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('services.index', [
            'services' => $services,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'search' => $search,
        ]);
    }

    public function show(string $slug): View
    {
        $service = Service::publiclyVisible()
            ->where('slug', $slug)
            ->with([
                'category',
                'freelancer.freelancerProfile',
                'freelancer.roles',
                'activePackages',
            ])
            ->firstOrFail();

        return view('services.show', [
            'service' => $service,
            'packages' => $service->activePackages->sortBy(fn ($package) => $package->tier->sortOrder())->values(),
        ]);
    }
}
