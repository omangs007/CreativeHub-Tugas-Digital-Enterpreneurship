<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman publik proyek terbuka yang dapat ditawar freelancer.
 */
class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $search = mb_substr($search, 0, 100);
        $categories = Category::active()->get();
        $selectedCategory = $categories->firstWhere('slug', $request->query('kategori'));

        $projects = Project::publiclyVisible()
            ->with(['category', 'customer'])
            ->when($search !== '', fn (Builder $q) => $q->whereLike('title', "%{$search}%"))
            ->when($selectedCategory, fn (Builder $q) => $q->where('category_id', $selectedCategory->id))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('projects.index', [
            'projects' => $projects,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'search' => $search,
        ]);
    }

    public function show(Project $project): View
    {
        abort_unless(Project::publiclyVisible()->whereKey($project->id)->exists(), 404);

        return view('projects.show', ['project' => $project->load(['category', 'customer'])]);
    }
}
