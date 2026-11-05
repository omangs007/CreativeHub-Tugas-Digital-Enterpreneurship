<?php

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Http\Requests\ProposalRequest;
use App\Models\Project;
use App\Models\Proposal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Penawaran milik freelancer yang sedang login.
 */
class ProposalController extends Controller
{
    public function index(Request $request): View
    {
        return view('freelancer.proposals.index', [
            'proposals' => $request->user()->proposals()
                ->with(['project.category', 'order'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(ProposalRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('propose', $project);

        $proposal = new Proposal($request->validated());
        $proposal->project_id = $project->id;
        $proposal->freelancer_id = $request->user()->id;

        try {
            $proposal->save();
        } catch (UniqueConstraintViolationException) {
            // Pengiriman ganda bersamaan ditahan oleh unique index parsial di database.
            throw ValidationException::withMessages(['proposal' => __('Anda sudah memiliki penawaran aktif untuk proyek ini.')]);
        }

        return Redirect::route('freelancer.proposals.index')->with('status', 'proposal-sent');
    }

    public function edit(Proposal $proposal): View
    {
        Gate::authorize('update', $proposal);

        return view('freelancer.proposals.edit', ['proposal' => $proposal->load('project')]);
    }

    public function update(ProposalRequest $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $proposal->update($request->validated());

        return Redirect::route('freelancer.proposals.index')->with('status', 'proposal-updated');
    }

    public function withdraw(Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $proposal->status = ProposalStatus::Withdrawn;
        $proposal->save();

        return Redirect::route('freelancer.proposals.index')->with('status', 'proposal-withdrawn');
    }
}
