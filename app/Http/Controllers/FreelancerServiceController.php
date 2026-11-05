<?php

namespace App\Http\Controllers;

use App\Enums\PackageTier;
use App\Enums\ServiceStatus;
use App\Http\Requests\ServiceRequest;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pengelolaan layanan milik freelancer yang sedang login (dashboard).
 */
class FreelancerServiceController extends Controller
{
    private const DISK = 'public';

    public function index(Request $request): View
    {
        Gate::authorize('create', Service::class);

        $services = $request->user()->services()
            ->with('category')
            ->withMin('activePackages as starting_price', 'price')
            ->withCount('activePackages')
            ->latest('updated_at')
            ->get();

        return view('freelancer.services.index', ['services' => $services]);
    }

    public function create(): View
    {
        Gate::authorize('create', Service::class);

        return view('freelancer.services.create', $this->formData(new Service));
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        Gate::authorize('create', Service::class);

        $user = $request->user();
        $thumbnail = $request->file('thumbnail')?->store("services/{$user->id}", self::DISK);

        DB::transaction(function () use ($request, $user, $thumbnail) {
            $service = new Service($request->safe()->only(['title', 'category_id', 'description']));
            $service->user_id = $user->id;
            $service->slug = Service::uniqueSlug($request->validated('title'));
            $service->thumbnail_path = $thumbnail;
            $service->save();

            $this->syncPackages($service, $request->enabledPackages());
        });

        return Redirect::route('freelancer.services.index')->with('status', 'service-created');
    }

    public function edit(Service $service): View
    {
        Gate::authorize('update', $service);

        return view('freelancer.services.edit', $this->formData($service->load('packages')));
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        Gate::authorize('update', $service);

        $oldThumbnail = null;

        if ($request->hasFile('thumbnail')) {
            $oldThumbnail = $service->thumbnail_path;
            $service->thumbnail_path = $request->file('thumbnail')->store("services/{$service->user_id}", self::DISK);
        }

        DB::transaction(function () use ($request, $service) {
            $service->fill($request->safe()->only(['title', 'category_id', 'description']));
            $service->markEditedByOwner();
            $service->save();

            $this->syncPackages($service, $request->enabledPackages());
        });

        if ($oldThumbnail) {
            Storage::disk(self::DISK)->delete($oldThumbnail);
        }

        return Redirect::route('freelancer.services.index')->with('status', 'service-updated');
    }

    public function submit(Service $service): RedirectResponse
    {
        Gate::authorize('update', $service);

        $service->submitForReview();

        return Redirect::route('freelancer.services.index')->with('status', 'service-submitted');
    }

    public function withdraw(Service $service): RedirectResponse
    {
        Gate::authorize('update', $service);

        $service->withdraw();

        return Redirect::route('freelancer.services.index')->with('status', 'service-withdrawn');
    }

    public function destroy(Service $service): RedirectResponse
    {
        Gate::authorize('delete', $service);

        if (! $service->isDeletable()) {
            throw ValidationException::withMessages([
                'service' => $service->status === ServiceStatus::Published
                    ? __('Tarik layanan dari katalog terlebih dahulu sebelum menghapusnya.')
                    : __('Layanan yang sudah pernah dipesan tidak dapat dihapus. Tarik layanan atau nonaktifkan paketnya.'),
            ]);
        }

        $service->delete();

        if ($service->thumbnail_path) {
            Storage::disk(self::DISK)->delete($service->thumbnail_path);
        }

        return Redirect::route('freelancer.services.index')->with('status', 'service-deleted');
    }

    /**
     * Paket yang diaktifkan disimpan/diperbarui. Paket yang dimatikan dinonaktifkan,
     * bukan dihapus, agar data paket lama tetap tersedia untuk riwayat pesanan.
     *
     * @param  array<string, array<string, mixed>>  $enabled
     */
    private function syncPackages(Service $service, array $enabled): void
    {
        foreach (PackageTier::cases() as $tier) {
            if (isset($enabled[$tier->value])) {
                $service->packages()->updateOrCreate(
                    ['tier' => $tier],
                    [...$enabled[$tier->value], 'is_active' => true],
                );
            } else {
                $service->packages()->where('tier', $tier)->update(['is_active' => false]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Service $service): array
    {
        return [
            'service' => $service,
            'categories' => Category::active()->get(),
            'tiers' => PackageTier::cases(),
            'packages' => $service->packages->keyBy(fn ($package) => $package->tier->value),
        ];
    }
}
