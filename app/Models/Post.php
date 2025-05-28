<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'is_draft',
        'published_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_draft' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_draft', false)
            ->where(function ($q) {
                $q->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
            });
    }

    public function scopeDraft($query)
    {
        return $query->where('is_draft', true);
    }

    public function scopeScheduled($query)
    {
        return $query->where('is_draft', false)
            ->whereNotNull('published_at')
            ->where('published_at', '>', now());
    }

    // Helper methods
    public function isPublished(): bool
    {
        return ! $this->is_draft &&
               $this->published_at &&
               $this->published_at->isPast();
    }

    public function isDraft(): bool
    {
        return $this->is_draft;
    }

    public function isScheduled(): bool
    {
        return ! $this->is_draft &&
               $this->published_at &&
               $this->published_at->isFuture();
    }

    public function getStatusAttribute(): string
    {
        if ($this->isDraft()) {
            return 'draft';
        }

        if ($this->isScheduled()) {
            return 'scheduled';
        }

        return 'published';
    }
}
