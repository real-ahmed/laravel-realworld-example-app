<?php

namespace App\Observers;

use App\Models\Article;
use App\Models\ArticleRevision;

class ArticleObserver
{
    /**
     * Handle the Article "updating" event.
     * This creates a revision before the article is updated.
     */
    public function updating(Article $article): void
    {
        // Only create a revision if the article has been persisted to the database
        if ($article->exists) {
            // Get the original attributes before the update
            $original = $article->getOriginal();

            // Create a revision with the current state before update
            ArticleRevision::create([
                'article_id' => $article->id,
                'title' => $original['title'],
                'slug' => $original['slug'],
                'description' => $original['description'],
                'body' => $original['body'],
            ]);
        }
    }

    /**
     * Handle the Article "created" event.
     */
    public function created(Article $article): void
    {
        //
    }

    /**
     * Handle the Article "updated" event.
     */
    public function updated(Article $article): void
    {
        //
    }

    /**
     * Handle the Article "deleted" event.
     */
    public function deleted(Article $article): void
    {
        //
    }

    /**
     * Handle the Article "restored" event.
     */
    public function restored(Article $article): void
    {
        //
    }

    /**
     * Handle the Article "force deleted" event.
     */
    public function forceDeleted(Article $article): void
    {
        //
    }
}
