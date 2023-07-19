<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * @return Response
     */
    public function show(): \Inertia\Response
    {
        return Inertia::render('Welcome', [
            'appName' => config('app.name')
        ]);
    }
}
