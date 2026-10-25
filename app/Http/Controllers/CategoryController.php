<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tree example: hierarchical rows (`parent_id`) with drag-and-drop ordering among
 * siblings. The whole dataset is loaded, so the list is not paginated.
 *
 * @extends ResourceController<Category>
 */
class CategoryController extends ResourceController {
    protected function modelClass(): string {
        return Category::class;
    }

    protected function page(): string {
        return 'categories/Index';
    }

    protected function singularLabel(): string {
        return 'Category';
    }

    protected function searchableColumns(): array {
        return ['name'];
    }

    protected function defaultOrderColumn(): string {
        return 'position';
    }

    protected function paginates(): bool {
        return false;
    }

    protected function extraProps(): array {
        return ['parentOptions' => Category::options()];
    }

    protected function rules(?int $id = null): array {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', 'not_in:' . ($id ?? 0)],
            'name' => ['required', 'string', 'max:80'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * New categories are appended after their last sibling.
     */
    protected function mutateData(array $data, ?int $id = null): array {
        if ($id === null) {
            $data['position'] = (int) $this->baseQuery()
                ->where('parent_id', $data['parent_id'] ?? null)
                ->max('position') + 1;
        }

        return $data;
    }

    /**
     * Persist the order of the siblings below one parent (`parentId` null = roots).
     */
    public function reorder(Request $request): RedirectResponse {
        $validated = $request->validate([
            'parentId' => ['nullable', 'integer'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach (array_values($validated['ids']) as $index => $id) {
            $this->baseQuery()
                ->whereKey($id)
                ->where('parent_id', $validated['parentId'] ?? null)
                ->update(['position' => $index + 1]);
        }

        return back();
    }
}
