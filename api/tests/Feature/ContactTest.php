<?php

namespace Tests\Feature;

use App\Mail\ContactMessage;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactTest extends TestCase
{
    public function test_contact_message_is_sent_to_configured_recipient(): void
    {
        Mail::fake();
        config(['mail.contact_to' => 'contact@worksyne.test']);

        $response = $this->postJson('/api/contact', [
            'name' => 'Jane Operator',
            'email' => 'jane@example.com',
            'company' => 'Example Company',
            'subject' => 'Enterprise rollout',
            'message' => 'We would like to discuss onboarding our team.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Your message was sent successfully.');

        Mail::assertSent(ContactMessage::class, function (ContactMessage $mail) {
            return $mail->hasTo('contact@worksyne.test')
                && $mail->hasReplyTo('jane@example.com', 'Jane Operator')
                && $mail->contact['subject'] === 'Enterprise rollout';
        });
    }

    public function test_contact_message_requires_valid_input(): void
    {
        Mail::fake();

        $this
            ->postJson('/api/contact', [
                'name' => '',
                'email' => 'invalid',
                'subject' => '',
                'message' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);

        Mail::assertNothingSent();
    }

    public function test_contact_honeypot_must_be_empty(): void
    {
        Mail::fake();

        $this
            ->postJson('/api/contact', [
                'name' => 'Automated Sender',
                'email' => 'sender@example.com',
                'subject' => 'Hello',
                'message' => 'Automated message',
                'website' => 'https://spam.example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('website');

        Mail::assertNothingSent();
    }
}
