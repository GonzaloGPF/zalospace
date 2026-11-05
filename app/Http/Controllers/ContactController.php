<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Contact/CreateContact');
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        Mail::to(config('mail.contact'))->queue(new ContactEmail(
            $request->string('name')->value(),
            $request->string('email')->value(),
            $request->string('message')->value(),
        ));

        $this->flashMessage(tAction('sent', null, false));

        return to_route('welcome');
    }
}
