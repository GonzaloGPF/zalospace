<?php

namespace Tests\Feature;

use Tests\TestCase;

class SitemapTest extends TestCase
{
    public function test_the_sitemap_lists_every_page_in_both_languages_with_hreflang_alternates(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/xml');

        $content = $response->getContent();

        $this->assertStringContainsString(route('info.about_me'), $content);
        $this->assertStringContainsString(route('en.info.about_me'), $content);
        $this->assertStringContainsString('hreflang="es"', $content);
        $this->assertStringContainsString('hreflang="en"', $content);
        $this->assertStringContainsString('hreflang="x-default"', $content);
    }
}
