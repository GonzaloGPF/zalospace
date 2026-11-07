<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $pages = [
            'welcome',
            'info.about_me',
            'info.projects',
            'info.work_with_me',
            'info.cv',
            'contacts.create',
        ];

        $urls = [];

        foreach ($pages as $page) {
            $alternates = [
                'es' => route($page),
                'en' => route("en.{$page}"),
            ];

            foreach ($alternates as $loc) {
                $urls[] = ['loc' => $loc, 'alternates' => $alternates];
            }
        }

        return response(view('sitemap', ['urls' => $urls]))
            ->header('Content-Type', 'application/xml');
    }
}
