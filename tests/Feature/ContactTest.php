<?php

namespace Tests\Feature;

use App\Enums\Levels;
use App\Mail\ContactEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_user_can_send_a_contact_email(): void
    {
        Mail::fake();

        config()->set('mail.contact', 'company@mail.com');

        $this->post(route('contacts.store'), [
            'email' => 'test@email.com',
            'message' => 'Some Message',
        ]);

        $flashMessageData = [
            'message' => tAction('sent', null, false),
            'type' => Levels::SUCCESS->value
        ];

        $this->assertEquals($flashMessageData, session('flash_message_data'));

        Mail::assertQueued(ContactEmail::class, function (ContactEmail $email) {
            $this->assertEquals('company@mail.com', $email->to[0]['address']);

            return true;
        });
    }
}
