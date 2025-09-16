<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\User;
use App\Policies\ArticlePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ArticlePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ArticlePolicy();
    }

    public function test_view_revisions_allows_article_author(): void
    {
        $author = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $this->assertTrue($this->policy->viewRevisions($author, $article));
    }

    public function test_view_revisions_denies_other_users(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $this->assertFalse($this->policy->viewRevisions($otherUser, $article));
    }

    public function test_revert_allows_article_author(): void
    {
        $author = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $this->assertTrue($this->policy->revert($author, $article));
    }

    public function test_revert_denies_other_users(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $this->assertFalse($this->policy->revert($otherUser, $article));
    }

    public function test_update_allows_article_author(): void
    {
        $author = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $this->assertTrue($this->policy->update($author, $article));
    }

    public function test_update_denies_other_users(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $this->assertFalse($this->policy->update($otherUser, $article));
    }

    public function test_delete_allows_article_author(): void
    {
        $author = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $this->assertTrue($this->policy->delete($author, $article));
    }

    public function test_delete_denies_other_users(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $this->assertFalse($this->policy->delete($otherUser, $article));
    }

    public function test_view_any_allows_all_users(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_view_allows_all_users(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $this->assertTrue($this->policy->view($otherUser, $article));
    }

    public function test_create_allows_all_users(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
    }
}
