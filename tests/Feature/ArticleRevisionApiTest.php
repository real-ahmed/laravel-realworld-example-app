<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ArticleRevisionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticateUser(User $user = null): User
    {
        $user = $user ?: User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Clear any existing authorization headers
        $this->withoutHeader('Authorization');
        $this->withHeader('Authorization', 'Bearer ' . $token);

        return $user;
    }

    public function test_can_list_revisions_for_own_article(): void
    {
        $user = $this->authenticateUser();
        $article = Article::factory()->for($user)->create();

        // Create some revisions
        ArticleRevision::factory()->count(3)->for($article)->create();

        $response = $this->getJson("/api/articles/{$article->slug}/revisions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'revisions' => [
                    '*' => [
                        'id',
                        'articleId',
                        'title',
                        'slug',
                        'description',
                        'body',
                        'createdAt',
                        'updatedAt'
                    ]
                ],
                'revisionsCount'
            ])
            ->assertJsonPath('revisionsCount', 3);
    }

    public function test_cannot_list_revisions_for_other_users_article(): void
    {
        $author = User::factory()->create();
        $otherUser = $this->authenticateUser();
        $article = Article::factory()->for($author)->create();

        ArticleRevision::factory()->for($article)->create();

        $response = $this->getJson("/api/articles/{$article->slug}/revisions");

        $response->assertStatus(403);
    }

    public function test_can_show_specific_revision_for_own_article(): void
    {
        $user = $this->authenticateUser();
        $article = Article::factory()->for($user)->create();
        $revision = ArticleRevision::factory()->for($article)->create([
            'title' => 'Revision Title',
            'description' => 'Revision Description',
            'body' => 'Revision Body'
        ]);

        $response = $this->getJson("/api/articles/{$article->slug}/revisions/{$revision->id}");

        $response->assertStatus(200)
            ->assertJson([
                'revision' => [
                    'id' => $revision->id,
                    'articleId' => $article->id,
                    'title' => 'Revision Title',
                    'description' => 'Revision Description',
                    'body' => 'Revision Body'
                ]
            ]);
    }

    public function test_cannot_show_revision_for_other_users_article(): void
    {
        $author = User::factory()->create();
        $otherUser = $this->authenticateUser();
        $article = Article::factory()->for($author)->create();
        $revision = ArticleRevision::factory()->for($article)->create();

        $response = $this->getJson("/api/articles/{$article->slug}/revisions/{$revision->id}");

        $response->assertStatus(403);
    }

    public function test_cannot_show_revision_that_doesnt_belong_to_article(): void
    {
        $user = $this->authenticateUser();
        $article1 = Article::factory()->for($user)->create();
        $article2 = Article::factory()->for($user)->create();
        $revision = ArticleRevision::factory()->for($article2)->create();

        $response = $this->getJson("/api/articles/{$article1->slug}/revisions/{$revision->id}");

        $response->assertStatus(404);
    }

    public function test_can_revert_own_article_to_revision(): void
    {
        $user = $this->authenticateUser();
        $article = Article::factory()->for($user)->create([
            'title' => 'Current Title',
            'description' => 'Current Description',
            'body' => 'Current Body'
        ]);

        $revision = ArticleRevision::factory()->for($article)->create([
            'title' => 'Old Title',
            'description' => 'Old Description',
            'body' => 'Old Body'
        ]);

        $response = $this->postJson("/api/articles/{$article->slug}/revisions/{$revision->id}/revert");

        $response->assertStatus(200)
            ->assertJson([
                'article' => [
                    'title' => 'Old Title',
                    'description' => 'Old Description',
                    'body' => 'Old Body'
                ]
            ]);

        // Verify the article was actually updated
        $article->refresh();
        $this->assertEquals('Old Title', $article->title);
        $this->assertEquals('Old Description', $article->description);
        $this->assertEquals('Old Body', $article->body);
    }

    public function test_cannot_revert_other_users_article(): void
    {
        $author = User::factory()->create();
        $otherUser = $this->authenticateUser();
        $article = Article::factory()->for($author)->create();
        $revision = ArticleRevision::factory()->for($article)->create();

        $response = $this->postJson("/api/articles/{$article->slug}/revisions/{$revision->id}/revert");

        $response->assertStatus(403);
    }

    public function test_cannot_revert_to_revision_that_doesnt_belong_to_article(): void
    {
        $user = $this->authenticateUser();
        $article1 = Article::factory()->for($user)->create();
        $article2 = Article::factory()->for($user)->create();
        $revision = ArticleRevision::factory()->for($article2)->create();

        $response = $this->postJson("/api/articles/{$article1->slug}/revisions/{$revision->id}/revert");

        $response->assertStatus(404);
    }

    public function test_revision_endpoints_require_authentication(): void
    {
        $article = Article::factory()->create();
        $revision = ArticleRevision::factory()->for($article)->create();

        // Test list revisions
        $response = $this->getJson("/api/articles/{$article->slug}/revisions");
        $response->assertStatus(401);

        // Test show revision
        $response = $this->getJson("/api/articles/{$article->slug}/revisions/{$revision->id}");
        $response->assertStatus(401);

        // Test revert to revision
        $response = $this->postJson("/api/articles/{$article->slug}/revisions/{$revision->id}/revert");
        $response->assertStatus(401);
    }

    public function test_revisions_are_ordered_by_creation_date_desc(): void
    {
        $user = $this->authenticateUser();
        $article = Article::factory()->for($user)->create();

        // Create revisions with different timestamps
        $oldRevision = ArticleRevision::factory()->for($article)->create(['created_at' => now()->subHours(2)]);
        $newerRevision = ArticleRevision::factory()->for($article)->create(['created_at' => now()->subHour()]);
        $newestRevision = ArticleRevision::factory()->for($article)->create(['created_at' => now()]);

        $response = $this->getJson("/api/articles/{$article->slug}/revisions");

        $response->assertStatus(200);
        $revisions = $response->json('revisions');

        // Should be ordered newest first
        $this->assertEquals($newestRevision->id, $revisions[0]['id']);
        $this->assertEquals($newerRevision->id, $revisions[1]['id']);
        $this->assertEquals($oldRevision->id, $revisions[2]['id']);
    }
}
