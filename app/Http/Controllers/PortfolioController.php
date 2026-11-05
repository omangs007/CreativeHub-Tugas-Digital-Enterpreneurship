<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortfolioItemRequest;
use App\Models\Category;
use App\Models\PortfolioItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('create', PortfolioItem::class);

        return view('freelancer.portfolio.index', [
            'items' => $request->user()->portfolioItems()->with('category')->latest()->get(),
            'categories' => Category::active()->get(),
            'maxItems' => PortfolioItem::MAX_PER_FREELANCER,
        ]);
    }

    public function store(PortfolioItemRequest $request): RedirectResponse
    {
        Gate::authorize('create', PortfolioItem::class);

        $user = $request->user();

        DB::transaction(function () use ($request, $user) {
            // Kunci baris user agar dua upload bersamaan tidak melewati batas.
            $user->newQuery()->whereKey($user->id)->lockForUpdate()->first();

            if ($user->portfolioItems()->count() >= PortfolioItem::MAX_PER_FREELANCER) {
                throw ValidationException::withMessages([
                    'image' => __('Maksimal :max item portofolio. Hapus item lama terlebih dahulu.', ['max' => PortfolioItem::MAX_PER_FREELANCER]),
                ]);
            }

            $item = new PortfolioItem($request->safe()->except('image'));
            $item->user_id = $user->id;
            $item->image_path = $this->storeImage($request, $user->id);
            $item->save();
        });

        return Redirect::route('portfolio.index')->with('status', 'portfolio-created');
    }

    public function edit(PortfolioItem $portfolioItem): View
    {
        Gate::authorize('update', $portfolioItem);

        return view('freelancer.portfolio.edit', [
            'item' => $portfolioItem,
            'categories' => Category::active()->get(),
        ]);
    }

    public function update(PortfolioItemRequest $request, PortfolioItem $portfolioItem): RedirectResponse
    {
        Gate::authorize('update', $portfolioItem);

        $portfolioItem->fill($request->safe()->except('image'));

        $oldImage = null;

        if ($request->hasFile('image')) {
            $oldImage = $portfolioItem->image_path;
            $portfolioItem->image_path = $this->storeImage($request, $portfolioItem->user_id);
        }

        $portfolioItem->save();

        if ($oldImage) {
            Storage::disk(PortfolioItem::DISK)->delete($oldImage);
        }

        return Redirect::route('portfolio.index')->with('status', 'portfolio-updated');
    }

    public function destroy(PortfolioItem $portfolioItem): RedirectResponse
    {
        Gate::authorize('delete', $portfolioItem);

        $portfolioItem->delete();
        Storage::disk(PortfolioItem::DISK)->delete($portfolioItem->image_path);

        return Redirect::route('portfolio.index')->with('status', 'portfolio-deleted');
    }

    /**
     * Nama file dibuat acak oleh Laravel; nama asli dari browser tidak dipakai.
     */
    private function storeImage(PortfolioItemRequest $request, int $userId): string
    {
        return $request->file('image')->store("portfolio/{$userId}", PortfolioItem::DISK);
    }
}
