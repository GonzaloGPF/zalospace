<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TutorialsTest extends TestCase
{
    public function test_tutorials_landing_keeps_working(): void
    {
        $this->get('/tutorials')->assertRedirect('/tutorials/es/overview');
    }

    public static function publishedPages(): array
    {
        return [
            'overview' => ['/tutorials/es/overview', 'Tutoriales de distintas cosas'],
            'laravel and aws' => ['/tutorials/es/laravel_aws', 'Despliegues con Laravel y AWS'],
        ];
    }

    #[DataProvider('publishedPages')]
    public function test_published_tutorials_render(string $url, string $expectedText): void
    {
        $this->get($url)->assertSuccessful()->assertSee($expectedText);
    }

    public static function tutorialImages(): array
    {
        return [
            'workflows' => ['workflows.png'],
            'forge ssh' => ['forge_ssh.png'],
            'github actions secrets' => ['github_actions_secrets.png'],
            'forge api tokens' => ['forge_api_tokens.png'],
        ];
    }

    #[DataProvider('tutorialImages')]
    public function test_tutorial_renders_images_as_static_files_in_public(string $image): void
    {
        $response = $this->get('/tutorials/es/laravel_aws');

        $response->assertOk()->assertSee("https://zalospace.com/images/tutorials/{$image}");
        $this->assertFileExists(public_path("images/tutorials/{$image}"));
    }

    public static function protectedEndpoints(): array
    {
        return [
            'admin shell' => ['get', '/tutorials/_admin'],
            'admin read api' => ['get', '/tutorials/_admin/api/tree'],
            'admin write api' => ['put', '/tutorials/_admin/api/page'],
        ];
    }

    #[DataProvider('protectedEndpoints')]
    public function test_visitors_cannot_reach_the_docs_admin(string $method, string $url): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->{$method}($url)
            ->assertForbidden();
    }

    public function test_the_public_mcp_endpoint_is_disabled(): void
    {
        $this->postJson('/tutorials/mcp')->assertMethodNotAllowed();
    }
}
