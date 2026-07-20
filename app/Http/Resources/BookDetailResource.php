<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'genres' => GenreResource::collection(
                $this->whenLoaded('genres')
            ),
            'reviews' => ReviewResource::collection(
                $this->whenLoaded('reviews')
            ),
            'average_rating' => $this->reviews_avg_rating !== null
                ? round((float) $this->reviews_avg_rating, 2)
                : null,
            'review_count' => $this->reviews_count,
        ];
    }
}
