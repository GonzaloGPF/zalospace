<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class TutorialController extends Controller
{
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        return Inertia::location(url('tutorials/overview'));
    }

    public function deploymentsWithLaravelAndAWS(): Response
    {
        return Inertia::render('Tutorials/DeploymentsWithLaravelAndAWS');
    }
}
