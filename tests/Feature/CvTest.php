<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CvTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_cv_page_includes_both_language_versions(): void
    {
        $this->get(route('info.cv'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Info/MyCv')
                ->where('cv.es', fn (string $html) => str_contains($html, 'Perfil') && str_contains($html, '<h1'))
                ->where('cv.en', fn (string $html) => str_contains($html, 'Profile') && str_contains($html, '<h1'))
            );
    }

    public static function supportedLocales(): array
    {
        return [
            'spanish' => ['es'],
            'english' => ['en'],
        ];
    }

    #[DataProvider('supportedLocales')]
    public function test_the_cv_can_be_downloaded_as_a_pdf_in_each_supported_language(string $locale): void
    {
        $response = $this->get(route('info.cv_pdf', ['locale' => $locale]));

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString(
            "cv-{$locale}.pdf",
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_the_cv_pdf_rejects_an_unsupported_locale(): void
    {
        $this->get('/my-cv/pdf/fr')->assertNotFound();
    }
}
