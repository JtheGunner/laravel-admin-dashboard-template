<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product category. Forms a tree via `parent_id` and is ordered by `position`.
 *
 * @mixin IdeHelperCategory
 */
class Category extends Model {
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'icon',
        'color',
        'position',
    ];

    /** @return BelongsTo<Category, $this> */
    public function parent(): BelongsTo {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany {
        return $this->hasMany(Product::class);
    }

    /**
     * Dropdown options (value/label) for select fields.
     *
     * @return list<array{value: int, label: string}>
     */
    public static function options(): array {
        return array_values(static::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (self $category): array => [
                'value' => $category->id,
                'label' => $category->name,
            ])
            ->all());
    }
}
