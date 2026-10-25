<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Rich table example: image column, badge column, faceted filters, file upload,
 * rich-text description and the card layout on small screens.
 *
 * @extends ResourceController<Product>
 */
class ProductController extends ResourceController {
    protected function modelClass(): string {
        return Product::class;
    }

    protected function page(): string {
        return 'products/Index';
    }

    protected function singularLabel(): string {
        return 'Product';
    }

    protected function searchableColumns(): array {
        return ['name', 'sku'];
    }

    protected function defaultOrderColumn(): string {
        return 'name';
    }

    protected function eagerLoad(): array {
        return ['category'];
    }

    protected function fileColumns(): array {
        return ['image_path' => 'products'];
    }

    protected function fileDisk(): string {
        return 'public';
    }

    protected function transformRow(Model $model): array {
        /** @var Product $model */
        $data = $model->toArray();
        $data['category_name'] = $model->category?->name;
        $data['status_label'] = $model->status->label();
        $data['status_color'] = $model->status->color();
        $data['image_url'] = $model->image_url;

        return $data;
    }

    protected function sortExpressions(): array {
        return [
            'category_name' => function (Builder $query, $direction): void {
                $this->orderByRelated($query, Category::class, 'category_id', 'name', $direction);
            },
        ];
    }

    protected function filters(): array {
        return [
            'status' => [
                'label' => 'Status',
                'options' => ProductStatus::options(),
            ],
            'category_id' => [
                'label' => 'Category',
                'options' => Category::options(),
            ],
        ];
    }

    protected function extraProps(): array {
        return [
            'categoryOptions' => Category::options(),
            'statusOptions' => ProductStatus::options(),
        ];
    }

    protected function rules(?int $id = null): array {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:40', Rule::unique('products', 'sku')->ignore($id)],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'is_featured' => ['boolean'],
            'rating' => ['required', 'integer', 'between:0,5'],
            // A new upload is validated as an image; an unchanged field carries the stored path.
            'image_path' => request()->hasFile('image_path')
                ? ['image', 'max:4096']
                : ['nullable', 'string', 'max:2048'],
        ];
    }
}
