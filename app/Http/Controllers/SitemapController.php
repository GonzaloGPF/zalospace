<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            route('welcome'),
            route('info.about_me'),
            route('info.projects'),
            route('info.work_with_me'),
            route('info.cv'),
            route('contacts.create'),
        ];

        return response(view('sitemap', ['urls' => $urls]))
            ->header('Content-Type', 'application/xml');
    }
}
