<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * @return Response
     */
    public function create(): Response
    {
        return Inertia::render('Contact/CreateContact');
    }
    /**
     * @param ContactRequest $request
     * @return RedirectResponse
     */
    public function store(ContactRequest $request): RedirectResponse
    {
        Mail::to(config('mail.contact'))
//            ->cc($request->get('email'))
            ->queue(new ContactEmail($request->get('email'), $request->get('message')));

        $this->flashMessage(tAction('sent', null, false));

        return to_route('welcome');
    }
}
