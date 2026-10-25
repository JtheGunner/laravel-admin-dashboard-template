<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductStatus: string {
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Archived => 'Archived',
        };
    }

    /** Colour token understood by the `badge` column type. */
    public function color(): string {
        return match ($this) {
            self::Draft => 'amber',
            self::Active => 'green',
            self::Archived => 'gray',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array {
        return array_map(
            static fn (self $status): array => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
