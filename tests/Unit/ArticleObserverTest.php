<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\ArticleRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_revision_is_created_when_article_is_updated(): void
    {
        // Create an article
        $article = Article::factory()->create([
            'title' => 'Original Title',
            'description' => 'Original Description',
            'body' => 'Original Body'
        ]);

        // Ensure no revisions exist initially
        $this->assertEquals(0, $article->revisions()->count());

        // Update the article
        $article->update([
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'body' => 'Updated Body'
        ]);

        // Check that a revision was created with the original data
        $this->assertEquals(1, $article->revisions()->count());

        $revision = $article->revisions()->first();
        $this->assertEquals('Original Title', $revision->title);
        $this->assertEquals('Original Description', $revision->description);
        $this->assertEquals('Original Body', $revision->body);
    }

    public function test_revision_is_not_created_for_new_article(): void
    {
        // Create a new article (this should not trigger revision creation)
        $article = Article::factory()->create();

        // No revisions should exist for a new article
        $this->assertEquals(0, $article->revisions()->count());
    }

    public function test_multiple_revisions_are_created_for_multiple_updates(): void
    {
        // Create an article
        $article = Article::factory()->create([
            'title' => 'First Title',
            'description' => 'First Description',
            'body' => 'First Body'
        ]);

        // First update
        $article->update([
            'title' => 'Second Title',
            'description' => 'Second Description',
            'body' => 'Second Body'
        ]);

        // Verify first revision was created
        $this->assertEquals(1, $article->revisions()->count());
        $firstRevision = $article->revisions()->first();
        $this->assertEquals('First Title', $firstRevision->title);

        // Add a small delay to ensure different timestamps
        sleep(1);

        // Second update
        $article->update([
            'title' => 'Third Title',
            'description' => 'Third Description',
            'body' => 'Third Body'
        ]);

        // Should now have 2 revisions
        $this->assertEquals(2, $article->revisions()->count());

        // Check that we have both the original and first update data preserved
        $revisionTitles = $article->revisions()->pluck('title')->toArray();
        $this->assertContains('First Title', $revisionTitles);
        $this->assertContains('Second Title', $revisionTitles);
    }

    public function test_revision_includes_slug_from_original_data(): void
    {
        $article = Article::factory()->create([
            'title' => 'Original Title With Slug'
        ]);

        $originalSlug = $article->slug;

        // Update the article
        $article->update(['title' => 'New Title With New Slug']);

        $revision = $article->revisions()->first();
        $this->assertEquals($originalSlug, $revision->slug);
        $this->assertEquals('Original Title With Slug', $revision->title);
    }
}
