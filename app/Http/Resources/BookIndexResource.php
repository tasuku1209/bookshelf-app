<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'image_url' => $this->image_url,
            'genres' => GenreResource::collection(
                $this->whenLoaded('genres')
            ),
            'average_rating' => $this->reviews_avg_rating !== null
                ? round((float) $this->reviews_avg_rating, 2)
                : null,
            'review_count' => $this->reviews_count,
        ];
    }
}
