<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\ArticleRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_revision_belongs_to_article(): void
    {
        $article = Article::factory()->create();
        $revision = ArticleRevision::factory()->create(['article_id' => $article->id]);

        $this->assertInstanceOf(Article::class, $revision->article);
        $this->assertEquals($article->id, $revision->article->id);
    }

    public function test_article_revision_has_fillable_attributes(): void
    {
        $revision = new ArticleRevision();
        $fillable = $revision->getFillable();

        $expectedFillable = [
            'article_id',
            'title',
            'slug',
            'description',
            'body',
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_article_revision_can_be_created(): void
    {
        $article = Article::factory()->create();
        $revisionData = [
            'article_id' => $article->id,
            'title' => 'Test Title',
            'slug' => 'test-title',
            'description' => 'Test Description',
            'body' => 'Test Body Content',
        ];

        $revision = ArticleRevision::create($revisionData);

        $this->assertDatabaseHas('article_revisions', $revisionData);
        $this->assertEquals($revisionData['title'], $revision->title);
        $this->assertEquals($revisionData['description'], $revision->description);
        $this->assertEquals($revisionData['body'], $revision->body);
    }

    public function test_article_revision_has_timestamps(): void
    {
        $revision = ArticleRevision::factory()->create();

        $this->assertNotNull($revision->created_at);
        $this->assertNotNull($revision->updated_at);
    }
}
