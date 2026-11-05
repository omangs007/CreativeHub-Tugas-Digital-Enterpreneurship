<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Models\FreelancerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

class FreelancerActivationController extends Controller
{
    /**
     * Menambahkan role Freelancer dan profil freelancer kosong ke akun yang sedang login.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', FreelancerProfile::class);

        $user = $request->user();

        DB::transaction(function () use ($user) {
            $user->freelancerProfile()->create();
            $user->assignRole(RoleName::Freelancer);
        });

        return Redirect::route('profile.edit')->with('status', 'freelancer-activated');
    }
}
