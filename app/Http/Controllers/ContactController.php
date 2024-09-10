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
        $email = new ContactEmail($request->get('email'), $request->get('message'));
        $email->subject(tl('contact_email'));

        Mail::to(config('mail.contact'))
//            ->cc($request->get('email'))
            ->send($email); // queue

        $this->flashMessage(tAction('sent', null, false));

        return to_route('welcome');
    }
}
