<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string {
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function label(): string {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Editor => 'Editor',
            self::Viewer => 'Viewer',
        };
    }

    /**
     * Select options (value/label) for form fields.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array {
        return array_map(
            static fn (self $role): array => ['value' => $role->value, 'label' => $role->label()],
            self::cases(),
        );
    }
}
