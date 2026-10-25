<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperProduct
 */
class Product extends Model {
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'description',
        'price',
        'stock',
        'status',
        'is_featured',
        'rating',
        'image_path',
    ];

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'rating' => 'integer',
            'is_featured' => 'boolean',
            'status' => ProductStatus::class,
        ];
    }

    /**
     * Public URL of the uploaded product image, or null when none is set.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute {
        return Attribute::get(fn (): ?string => $this->image_path
            ? asset('storage/' . $this->image_path)
            : null
        );
    }
}
