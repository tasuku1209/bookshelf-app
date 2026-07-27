<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * 登録した書籍
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /**
     * 投稿したレビュー
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * お気に入りしている書籍
     */
    public function favoriteBooks(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'favorites')
            ->withTimestamps();
    }

    /**
     * 登録したジャンル
     */
    public function genres(): HasMany
    {
        return $this->hasMany(Genre::class);
    }

    /**
     * いいねしたレビュー
     */
    public function likedReviews(): BelongsToMany
    {
        return $this->belongsToMany(Review::class, 'review_likes')
            ->withTimestamps();
    }

    /**
     * 読書計画
     */
    public function readingPlans(): HasMany
    {
        return $this->hasMany(ReadingPlan::class);
    }

    // ------------------------------------------------------------------------
    /**
     * 基本統計
     */
    public function summaryStats(): array
    {
        return [
            'total_reviews' => $this->reviews()->count(),

            'books_read' => $this->readingPlans()
                ->where('status', ReadingPlanStatus::Completed)
                ->distinct('book_id')
                ->count('book_id'),

            'average_rating' => $this->reviews()->avg('rating') ?? 0,
        ];
    }

    /**
     * 評価分布
     */
    public function ratingDistribution(): Collection
    {
        $distribution = $this->reviews()
            ->select('rating')
            ->get()
            ->countBy('rating');

        return collect(range(1, 5))
            ->map(fn (int $rating) => $distribution->get($rating, 0));
    }

    /**
     * 高評価書籍TOP5
     */
    public function topRatedBooks(): Collection
    {
        return $this->reviews()
            ->with('book:id,title,author')
            ->where('rating', '>=', 4)
            ->orderByDesc('rating')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(function (Review $review) {
                return [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'rating' => $review->rating,
                ];
            });
    }

    /**
     * ジャンル別評価傾向TOP5
     */
    public function genreRatings(): Collection
    {
        return $this->reviews()
            ->with('book.genres')
            ->get()
            ->flatMap(function (Review $review) {

                return $review->book->genres->map(function (Genre $genre) use ($review) {

                    return [
                        'id' => $genre->id,
                        'name' => $genre->name,
                        'rating' => $review->rating,
                        'updated_at' => $review->updated_at,
                    ];
                });
            })
            ->groupBy('id')
            ->map(function (Collection $genres) {

                return [
                    'id' => $genres->first()['id'],
                    'name' => $genres->first()['name'],
                    'average_rating' => $genres->avg('rating'),
                    'count' => $genres->count(),
                    'updated_at' => $genres->max('updated_at'),
                ];
            })
            ->sortByDesc([
                ['average_rating', 'desc'],
                ['updated_at', 'desc'],
            ])
            ->take(5)
            ->values();
    }
}
