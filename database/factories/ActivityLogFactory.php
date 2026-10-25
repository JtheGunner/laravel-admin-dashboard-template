<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory {
    /**
     * @return array<string, mixed>
     */
    public function definition(): array {
        $event = fake()->randomElement(['created', 'updated', 'deleted', 'login']);
        $subject = fake()->randomElement(['Customer', 'Product', 'Order', 'Category']);

        return [
            'user_id' => User::factory(),
            'event' => $event,
            'subject_type' => $subject,
            'subject_id' => fake()->numberBetween(1, 100),
            'description' => ucfirst($event) . " $subject #" . fake()->numberBetween(1, 100),
            'created_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }
}
