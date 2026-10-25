<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit entry. Rows are never updated, so there is no `updated_at`.
 *
 * @mixin IdeHelperActivityLog
 */
class ActivityLog extends Model {
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'event',
        'subject_type',
        'subject_id',
        'description',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'created_at' => 'datetime',
        ];
    }
}
