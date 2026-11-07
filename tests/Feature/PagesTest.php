<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tighten\Ziggy\Ziggy;

class PagesTest extends TestCase
{
    public static function pages(): array
    {
        return [
            'home' => ['welcome', 'WelcomeIndex'],
            'about me' => ['info.about_me', 'Info/AboutMe'],
            'projects' => ['info.projects', 'Info/Projects'],
            'work with me' => ['info.work_with_me', 'Info/WorkWithMe'],
            'my cv' => ['info.cv', 'Info/MyCv'],
            'contact' => ['contacts.create', 'Contact/CreateContact'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders_its_inertia_component(string $route, string $component): void
    {
        $this->get(route($route))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
    }

    public function test_the_frontend_only_receives_the_routes_it_links_to(): void
    {
        $routes = array_keys((new Ziggy)->toArray()['routes']);

        sort($routes);

        $this->assertSame([
            'contacts.create',
            'contacts.store',
            'info.about_me',
            'info.cv',
            'info.cv_pdf',
            'info.projects',
            'info.work_with_me',
            'larecipe.docs.page',
            'welcome',
        ], $routes);
    }
}
