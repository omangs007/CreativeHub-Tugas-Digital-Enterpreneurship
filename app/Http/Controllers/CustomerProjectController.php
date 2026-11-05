<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Category;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * Proyek milik customer yang sedang login (dashboard).
 */
class CustomerProjectController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('create', Project::class);

        return view('customer.projects.index', [
            'projects' => $request->user()->projects()->with('category')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Project::class);

        return view('customer.projects.create', [
            'project' => new Project,
            'categories' => Category::active()->get(),
        ]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        Gate::authorize('create', Project::class);

        $project = new Project($request->validated());
        $project->customer_id = $request->user()->id;
        $project->save();

        return Redirect::route('customer.projects.show', $project)->with('status', 'project-created');
    }

    public function show(Project $project): View
    {
        Gate::authorize('view', $project);

        return view('customer.projects.show', ['project' => $project->load('category')]);
    }

    public function edit(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('customer.projects.edit', [
            'project' => $project,
            'categories' => Category::active()->get(),
        ]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $project->update($request->validated());

        return Redirect::route('customer.projects.show', $project)->with('status', 'project-updated');
    }

    public function close(Project $project): RedirectResponse
    {
        Gate::authorize('close', $project);

        $project->close();

        return Redirect::route('customer.projects.show', $project)->with('status', 'project-closed');
    }
}
