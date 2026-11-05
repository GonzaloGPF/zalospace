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

    public function test_user_can_go_to_contact_page()
    {
        $this->get(route('contacts.create'))
            ->assertSuccessful();
    }

    /**
     * A basic test example.
     */
    public function test_user_can_send_a_contact_email(): void
    {
        Mail::fake();

        config()->set('mail.contact', 'company@mail.com');

        $this->post(route('contacts.store'), [
            'name' => 'Some Client',
            'email' => 'test@email.com',
            'message' => 'Some Message',
        ]);

        $flashMessageData = [
            'message' => tAction('sent', null, false),
            'type' => Levels::SUCCESS->value,
        ];

        $this->assertEquals($flashMessageData, session('flash_message_data'));

        Mail::assertQueued(ContactEmail::class, function (ContactEmail $email) {
            $this->assertEquals('company@mail.com', $email->to[0]['address']);
            $this->assertEquals('Some Client', $email->name);

            return true;
        });
    }

    public function test_the_contact_email_carries_the_visitor_details_and_replies_to_them(): void
    {
        $email = new ContactEmail('Some Client', 'test@email.com', 'I need a Laravel developer');

        $email->assertSeeInHtml('Some Client');
        $email->assertSeeInHtml('test@email.com');
        $email->assertSeeInHtml('I need a Laravel developer');
        $email->assertHasReplyTo('test@email.com', 'Some Client');
    }

    public function test_the_name_is_required_to_contact(): void
    {
        Mail::fake();

        $this->post(route('contacts.store'), [
            'email' => 'test@email.com',
            'message' => 'Some Message',
        ])->assertSessionHasErrors('name');

        Mail::assertNothingQueued();
    }
}
