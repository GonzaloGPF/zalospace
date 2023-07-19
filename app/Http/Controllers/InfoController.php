<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InfoController extends Controller
{
    /**
     * @return Response
     */
    public function aboutMe(): Response
    {
        return Inertia::render('Info/AboutMe');
    }

    /**
     * @return Response
     */
    public function projects(): Response
    {
        return Inertia::render('Info/Projects');
    }

    /**
     * @return Response
     */
    public function tutorials(): Response
    {
        return Inertia::render('Info/Tutorials');
    }

    /**
     * @return Response
     */
    public function configurator(): Response
    {
        return Inertia::render('Info/Configurator');
    }
}
