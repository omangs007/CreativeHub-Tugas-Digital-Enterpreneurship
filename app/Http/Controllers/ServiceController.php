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

    public const SORTS = [
        'terbaru' => 'Terbaru',
        'termurah' => 'Harga terendah',
        'termahal' => 'Harga tertinggi',
    ];

    private function priceParam(Request $request, string $key): ?int
    {
        $value = filter_var($request->query($key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1_000_000_000]]);

        return $value === false ? null : $value;
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'max:120'],
        ]);

        $search = trim($validated['q'] ?? '');
        $categories = Category::active()->get();
        $selectedCategory = $categories->firstWhere('slug', $validated['kategori'] ?? null);

        // Filter harga dan urutan yang tidak valid diabaikan (bukan error), karena berasal dari URL publik.
        $minPrice = $this->priceParam($request, 'harga_min');
        $maxPrice = $this->priceParam($request, 'harga_maks');
        $sort = array_key_exists($request->query('urut'), self::SORTS) ? $request->query('urut') : 'terbaru';

        $services = Service::publiclyVisible()
            ->forCatalogCard()
            ->when($search !== '', fn (Builder $q) => $q->whereLike('title', "%{$search}%"))
            ->when($selectedCategory, fn (Builder $q) => $q->where('category_id', $selectedCategory->id))
            ->startingPriceBetween($minPrice, $maxPrice)
            ->when($sort === 'termurah', fn (Builder $q) => $q->orderBy('starting_price')->orderBy('id'))
            ->when($sort === 'termahal', fn (Builder $q) => $q->orderByDesc('starting_price')->orderBy('id'))
            ->when($sort === 'terbaru', fn (Builder $q) => $q->latest('published_at')->orderByDesc('id'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('services.index', [
            'services' => $services,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'search' => $search,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'sort' => $sort,
            'sorts' => self::SORTS,
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
