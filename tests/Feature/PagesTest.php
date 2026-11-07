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
            'home (en)' => ['en.welcome', 'WelcomeIndex'],
            'about me (en)' => ['en.info.about_me', 'Info/AboutMe'],
            'projects (en)' => ['en.info.projects', 'Info/Projects'],
            'work with me (en)' => ['en.info.work_with_me', 'Info/WorkWithMe'],
            'my cv (en)' => ['en.info.cv', 'Info/MyCv'],
            'contact (en)' => ['en.contacts.create', 'Contact/CreateContact'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders_its_inertia_component(string $route, string $component): void
    {
        $this->get(route($route))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
    }

    public function test_english_routes_set_the_application_locale_to_english(): void
    {
        $this->get(route('en.info.about_me'));

        $this->assertSame('en', app()->getLocale());
    }

    public function test_spanish_routes_set_the_application_locale_to_spanish(): void
    {
        $this->get(route('info.about_me'));

        $this->assertSame('es', app()->getLocale());
    }

    public function test_the_page_shares_the_equivalent_url_in_each_locale(): void
    {
        $this->get(route('info.about_me'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('localeAlternates', [
                'es' => route('info.about_me'),
                'en' => route('en.info.about_me'),
            ]));

        $this->get(route('en.info.about_me'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('localeAlternates', [
                'es' => route('info.about_me'),
                'en' => route('en.info.about_me'),
            ]));
    }

    public function test_the_frontend_only_receives_the_routes_it_links_to(): void
    {
        $routes = array_keys((new Ziggy)->toArray()['routes']);

        sort($routes);

        $this->assertSame([
            'contacts.create',
            'contacts.store',
            'en.contacts.create',
            'en.info.about_me',
            'en.info.cv',
            'en.info.projects',
            'en.info.work_with_me',
            'en.welcome',
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
