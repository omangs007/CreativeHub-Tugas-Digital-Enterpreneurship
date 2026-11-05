<?php

namespace App\Observers;

use App\Models\Category;
use App\Support\AuditLogger;

/**
 * Mencatat perubahan kategori yang dilakukan user yang login (admin) ke audit log.
 * Perubahan dari seeder/console (tanpa user login) tidak dicatat.
 */
class CategoryObserver
{
    public function created(Category $category): void
    {
        if (auth()->check()) {
            AuditLogger::record('category.created', $category, null, $category->only($this->fields()));
        }
    }

    public function updated(Category $category): void
    {
        $changed = array_intersect(array_keys($category->getChanges()), $this->fields());

        if (auth()->check() && $changed !== []) {
            AuditLogger::record(
                'category.updated',
                $category,
                array_intersect_key($category->getOriginal(), array_flip($changed)),
                $category->only($changed),
            );
        }
    }

    public function deleted(Category $category): void
    {
        if (auth()->check()) {
            AuditLogger::record('category.deleted', $category, $category->only($this->fields()));
        }
    }

    /**
     * @return list<string>
     */
    private function fields(): array
    {
        return ['name', 'slug', 'description', 'icon', 'is_active', 'sort_order'];
    }
}
