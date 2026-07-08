<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = Review::all();
        $users = User::all();

        foreach ($reviews as $review) {

            $likeCount = fake()->numberBetween(0, 3);

            if ($likeCount === 0) {
                continue;
            }

            $userIds = $users
                ->where('id', '!=', $review->user_id)
                ->random($likeCount)
                ->pluck('id')
                ->toArray();

            $review->likedUsers()
                ->syncWithoutDetaching($userIds);
        }
    }
}
