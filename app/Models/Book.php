<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'author',
        'isbn',
        'published_date',
        'description',
        'image_url',
    ];

    protected $casts = [
        'published_date' => 'date',
    ];

    /**
     * 登録ユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ジャンル
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class)
            ->withTimestamps();
    }

    /**
     * レビュー
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * お気に入りしているユーザー
     */
    public function favoritedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')
            ->withTimestamps();
    }

    /**
     * 読書計画
     */
    public function readingPlans(): HasMany
    {
        return $this->hasMany(ReadingPlan::class);
    }

    /**
     * キーワード検索（タイトル・著者）
     */
    public function scopeKeyword(Builder $query, ?string $keyword): Builder
    {
        if (blank($keyword)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($keyword) {
            $query->where('title', 'like', "%{$keyword}%")
                ->orWhere('author', 'like', "%{$keyword}%");
        });
    }

    /**
     * ジャンル検索
     */
    public function scopeGenre(Builder $query, ?int $genre): Builder
    {
        if ($genre === null) {
            return $query;
        }

        return $query->whereHas('genres', function (Builder $query) use ($genre) {
            $query->where('genres.id', $genre);
        });
    }

    /**
     * 並び替え
     */
    public function scopeSort(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'newest' => $query->orderByDesc('created_at')
                ->orderByDesc('id'),
            'oldest' => $query->orderBy('created_at')
                ->orderBy('id'),
            'title' => $query->orderBy('title')
                ->orderByDesc('id'),
            'rating' => $query
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->orderByDesc('created_at')
                ->orderByDesc('id'),

            default => $query->orderByDesc('created_at')
                ->orderByDesc('id'),
        };
    }
}
