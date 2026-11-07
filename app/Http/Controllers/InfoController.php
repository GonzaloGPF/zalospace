<?php

namespace App\Http\Controllers;

use DOMDocument;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InfoController extends Controller
{
    public function aboutMe(): Response
    {
        return Inertia::render('Info/AboutMe');
    }

    public function projects(): Response
    {
        return Inertia::render('Info/Projects');
    }

    public function workWithMe(): Response
    {
        return Inertia::render('Info/WorkWithMe');
    }

    public function cv(): Response
    {
        return Inertia::render('Info/MyCv', [
            'cv' => [
                'es' => $this->styleMarkdownHtml(Str::markdown(file_get_contents(public_path('docs/cv_es.md')))),
                'en' => $this->styleMarkdownHtml(Str::markdown(file_get_contents(public_path('docs/cv_en.md')))),
            ],
        ]);
    }

    public function cvPdf(string $locale): HttpResponse
    {
        $content = Str::markdown(file_get_contents(public_path("docs/cv_{$locale}.md")));

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('pdf.cv', ['content' => $content, 'locale' => $locale])->render());
        $dompdf->setPaper('a4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"gonzalo-pabon-cv-{$locale}.pdf\"",
        ]);
    }

    /**
     * Adds Tailwind utility classes to the markdown-rendered HTML so the frontend
     * doesn't need custom CSS to style content coming from the CV markdown files.
     */
    private function styleMarkdownHtml(string $html): string
    {
        $classes = [
            'h1' => 'text-2xl font-bold text-gray-900 dark:text-white mb-1',
            'h2' => 'text-lg font-semibold text-gray-900 dark:text-white mt-7 mb-3 pb-2 border-b border-gray-300 dark:border-gray-600',
            'h3' => 'font-semibold text-gray-900 dark:text-white mt-5 mb-1',
            'a' => 'text-blue-600 dark:text-blue-400 underline',
            'blockquote' => 'my-3 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-900 dark:border-blue-400 text-gray-800 dark:text-gray-200',
            'ul' => 'list-disc list-inside my-2 space-y-1',
            'li' => 'text-gray-500 dark:text-gray-400',
            'table' => 'w-full border-collapse my-2 text-sm',
            'th' => 'border border-gray-300 dark:border-gray-600 px-2 py-1 text-left bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white',
            'td' => 'border border-gray-300 dark:border-gray-600 px-2 py-1 text-left align-top text-gray-500 dark:text-gray-400',
        ];

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        foreach ($classes as $tag => $class) {
            foreach ($dom->getElementsByTagName($tag) as $element) {
                $element->setAttribute('class', $class);
            }
        }

        foreach ($dom->getElementsByTagName('p') as $element) {
            $element->setAttribute(
                'class',
                $element->parentNode?->nodeName === 'blockquote' ? 'm-0' : 'my-2 text-gray-500 dark:text-gray-400'
            );
        }

        $html = '';
        foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $node) {
            $html .= $dom->saveHTML($node);
        }

        return $html;
    }
}
