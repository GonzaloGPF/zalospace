<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * @return Response
     */
    public function show(): Response
    {
        return Inertia::render('WelcomeIndex', [
            'appName' => config('app.name')
        ]);
    }
}
