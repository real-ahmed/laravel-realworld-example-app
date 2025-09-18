<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleRevisionResource extends JsonResource
{
    public static $wrap = 'revision';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'articleId' => $this->article_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'body' => $this->body,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
            'author' => [
                'username' => $this->article->user->username,
                'bio' => $this->article->user->bio,
                'image' => $this->article->user->image,
            ],
            'tagList' => $this->article->tags->pluck('name')->toArray(),
        ];
    }
}
