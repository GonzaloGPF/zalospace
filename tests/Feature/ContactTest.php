<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertEquals(tAction('sent', null, false), session('flash_message'));

        Mail::assertQueued(ContactEmail::class, function (ContactEmail $email) {
            $this->assertEquals('company@mail.com', $email->to[0]['address']);
            $this->assertEquals('test@email.com', $email->cc[0]['address']);

            return true;
        });
    }
}
