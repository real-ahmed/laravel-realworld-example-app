<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleRevisionCollection;
use App\Http\Resources\ArticleRevisionResource;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\ArticleRevision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleRevisionController extends Controller
{
    /**
     * List all revisions for a specific article
     */
    public function index(Article $article): ArticleRevisionCollection
    {
        // Ensure the user can access this article's revisions
        $this->authorize('viewRevisions', $article);

        $revisions = $article->revisions()->orderBy('created_at', 'desc')->get();

        return new ArticleRevisionCollection($revisions);
    }

    /**
     * Show a specific revision
     */
    public function show(Article $article, ArticleRevision $revision): ArticleRevisionResource
    {
        // Ensure the revision belongs to this article
        if ($revision->article_id !== $article->id) {
            abort(404, 'Revision not found for this article');
        }

        // Ensure the user can access this article's revisions
        $this->authorize('viewRevisions', $article);

        return new ArticleRevisionResource($revision);
    }

    /**
     * Revert an article to a specific revision
     */
    public function revert(Article $article, ArticleRevision $revision): ArticleResource
    {
        // Ensure the revision belongs to this article
        if ($revision->article_id !== $article->id) {
            abort(404, 'Revision not found for this article');
        }

        // Ensure the user can revert this article
        $this->authorize('revert', $article);

        // Update the article with the revision data
        $article->update([
            'title' => $revision->title,
            'description' => $revision->description,
            'body' => $revision->body,
        ]);

        // Return the updated article
        return new ArticleResource($article->load('user', 'users', 'tags', 'user.followers'));
    }
}
