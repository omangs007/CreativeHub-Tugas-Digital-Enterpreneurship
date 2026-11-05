<?php

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Services\OrderPlacement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

/**
 * Keputusan customer atas penawaran di proyek miliknya.
 */
class ProposalDecisionController extends Controller
{
    public function accept(Request $request, Proposal $proposal, OrderPlacement $placement): RedirectResponse
    {
        Gate::authorize('decide', $proposal);

        $order = $placement->acceptProposal($proposal, $request->user());

        return Redirect::route('orders.show', $order)->with('status', 'proposal-accepted');
    }

    public function reject(Proposal $proposal): RedirectResponse
    {
        Gate::authorize('decide', $proposal);

        $proposal->status = ProposalStatus::Rejected;
        $proposal->save();

        return Redirect::route('customer.projects.show', $proposal->project_id)->with('status', 'proposal-rejected');
    }
}
