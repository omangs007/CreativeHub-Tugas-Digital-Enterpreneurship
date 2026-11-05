<?php

namespace App\Http\Controllers;

use App\Enums\FreelancerAvailability;
use App\Http\Requests\FreelancerProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class FreelancerProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $request->user()->freelancerProfile()->firstOrFail();

        Gate::authorize('update', $profile);

        return view('freelancer.profile-edit', [
            'profile' => $profile,
            'availabilities' => FreelancerAvailability::cases(),
        ]);
    }

    public function update(FreelancerProfileUpdateRequest $request): RedirectResponse
    {
        $profile = $request->user()->freelancerProfile()->firstOrFail();

        Gate::authorize('update', $profile);

        $profile->update($request->validated());

        return Redirect::route('freelancer.profile.edit')->with('status', 'freelancer-profile-updated');
    }

    /**
     * Profil publik freelancer. Email dan data pribadi lain tidak ditampilkan.
     */
    public function show(User $user): View
    {
        abort_unless(User::publicFreelancers()->whereKey($user->id)->exists(), 404);

        $user->load([
            'freelancerProfile',
            'portfolioItems' => fn ($q) => $q->where('is_visible', true)->with('category')->latest(),
        ]);

        $services = $user->services()
            ->publiclyVisible()
            ->forCatalogCard()
            ->latest('published_at')
            ->get();

        return view('freelancers.show', [
            'freelancer' => $user,
            'profile' => $user->freelancerProfile,
            'services' => $services,
            'ratingAvg' => $user->receivedReviews()->visible()->avg('rating'),
            'ratingCount' => $user->receivedReviews()->visible()->count(),
            'reviews' => $user->receivedReviews()->visible()->with('customer')->latest()->limit(5)->get(),
        ]);
    }
}
