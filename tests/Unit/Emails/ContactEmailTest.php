<?php

namespace Tests\Unit\Emails;

use App\Mail\ContactEmail;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContactEmailTest extends TestCase
{
    private ContactEmail $email;

    protected function setUp(): void
    {
        parent::setUp();
        $this->email = new ContactEmail('some@mail.com', 'some message');
    }

    #[Test] public function it_can_create_a_contact_email(): void
    {
        $this->assertEquals('some@mail.com', $this->email->email);
        $this->assertEquals('some message', $this->email->message);
    }

    #[Test] public function it_can_return_expected_content()
    {
        $content = $this->email->content();

        $this->assertEquals('emails.contact_email', $content->markdown);
    }
}
