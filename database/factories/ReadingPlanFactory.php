<?php

namespace Database\Factories;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReadingPlanFactory extends Factory
{
    protected $model = ReadingPlan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'target_date' => fake()->dateTimeBetween('today', '+1 month'),
            'completed_at' => null,
            'status' => ReadingPlanStatus::InProgress,
        ];
    }
}
