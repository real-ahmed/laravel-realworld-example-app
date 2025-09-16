<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ArticleRevisionCollection extends ResourceCollection
{
    public static $wrap = 'revisions';

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'revisions' => $this->collection,
            'revisionsCount' => $this->collection->count(),
        ];
    }
}
