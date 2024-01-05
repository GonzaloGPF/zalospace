<?php

namespace App\Http\Controllers;

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

    public function tutorials(): Response
    {
        return Inertia::render('Info/Tutorials');
    }

    public function configurator(): Response
    {
        return Inertia::render('Info/Configurator');
    }
}
