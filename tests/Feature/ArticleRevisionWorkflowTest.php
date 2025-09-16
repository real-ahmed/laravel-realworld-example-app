<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ArticleRevisionWorkflowTest extends TestCase
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

    public function test_complete_revision_workflow(): void
    {
        $user = $this->authenticateUser();

        // 1. Create an article
        $article = Article::factory()->for($user)->create([
            'title' => 'Original Article Title',
            'description' => 'Original description',
            'body' => 'Original body content'
        ]);

        // Initially, no revisions should exist
        $this->assertEquals(0, $article->revisions()->count());

        // 2. Update the article (this should create a revision)
        $article->update([
            'title' => 'First Updated Title',
            'description' => 'First updated description',
            'body' => 'First updated body content'
        ]);

        // 3. Verify revision was created with original data
        $this->assertEquals(1, $article->revisions()->count());
        $firstRevision = $article->revisions()->first();
        $this->assertEquals('Original Article Title', $firstRevision->title);
        $this->assertEquals('Original description', $firstRevision->description);
        $this->assertEquals('Original body content', $firstRevision->body);

        // 4. Update the article again
        $article->update([
            'title' => 'Second Updated Title',
            'description' => 'Second updated description',
            'body' => 'Second updated body content'
        ]);

        // 5. Verify we now have 2 revisions
        $this->assertEquals(2, $article->revisions()->count());

        // 6. List revisions via API
        $response = $this->getJson("/api/articles/{$article->slug}/revisions");
        $response->assertStatus(200)
            ->assertJsonPath('revisionsCount', 2);

        $revisions = $response->json('revisions');

        // Should be ordered newest first
        $this->assertEquals('First Updated Title', $revisions[0]['title']);
        $this->assertEquals('Original Article Title', $revisions[1]['title']);

        // 7. View a specific revision
        $response = $this->getJson("/api/articles/{$article->slug}/revisions/{$firstRevision->id}");
        $response->assertStatus(200)
            ->assertJsonPath('revision.title', 'Original Article Title')
            ->assertJsonPath('revision.description', 'Original description')
            ->assertJsonPath('revision.body', 'Original body content');

        // 8. Revert to the first revision
        $response = $this->postJson("/api/articles/{$article->slug}/revisions/{$firstRevision->id}/revert");
        $response->assertStatus(200)
            ->assertJsonPath('article.title', 'Original Article Title')
            ->assertJsonPath('article.description', 'Original description')
            ->assertJsonPath('article.body', 'Original body content');

        // 9. Verify the article was actually reverted
        $article->refresh();
        $this->assertEquals('Original Article Title', $article->title);
        $this->assertEquals('Original description', $article->description);
        $this->assertEquals('Original body content', $article->body);

        // 10. Verify that reverting created another revision
        $this->assertEquals(3, $article->revisions()->count());

        // The newest revision should contain the "Second Updated" data
        $newestRevision = $article->revisions()->first();
        $this->assertEquals('Second Updated Title', $newestRevision->title);
        $this->assertEquals('Second updated description', $newestRevision->description);
        $this->assertEquals('Second updated body content', $newestRevision->body);
    }

    public function test_revision_creation_preserves_original_slug(): void
    {
        $user = $this->authenticateUser();

        $article = Article::factory()->for($user)->create([
            'title' => 'Original Title'
        ]);

        $originalSlug = $article->slug;

        // Update the article (which will change the slug)
        $article->update(['title' => 'New Title']);

        // The revision should preserve the original slug
        $revision = $article->revisions()->first();
        $this->assertEquals($originalSlug, $revision->slug);
        $this->assertEquals('Original Title', $revision->title);
    }



    public function test_revision_data_integrity(): void
    {
        $user = $this->authenticateUser();

        $originalData = [
            'title' => 'Test Title with Special Characters !@#$%',
            'description' => 'Description with "quotes" and \'apostrophes\'',
            'body' => "Body with\nmultiple\nlines\nand\tspecial\tcharacters"
        ];

        $article = Article::factory()->for($user)->create($originalData);

        // Update the article
        $article->update([
            'title' => 'New Title',
            'description' => 'New Description',
            'body' => 'New Body'
        ]);

        // Verify revision preserves all original data exactly
        $revision = $article->revisions()->first();
        $this->assertEquals($originalData['title'], $revision->title);
        $this->assertEquals($originalData['description'], $revision->description);
        $this->assertEquals($originalData['body'], $revision->body);

        // Verify via API
        $response = $this->getJson("/api/articles/{$article->slug}/revisions/{$revision->id}");
        $response->assertStatus(200)
            ->assertJsonPath('revision.title', $originalData['title'])
            ->assertJsonPath('revision.description', $originalData['description'])
            ->assertJsonPath('revision.body', $originalData['body']);
    }
}
