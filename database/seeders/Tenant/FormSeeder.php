<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Form;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = tenant('id');

        Form::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => 'Contact Form',
            'handle' => 'contact',
            'description' => 'Main contact form for the website',
            'fields' => [
                ['handle' => 'name', 'name' => 'name', 'type' => 'text', 'label' => 'Your Name', 'required' => true],
                ['handle' => 'email', 'name' => 'email', 'type' => 'email', 'label' => 'Email Address', 'required' => true],
                ['handle' => 'phone', 'name' => 'phone', 'type' => 'tel', 'label' => 'Phone Number', 'required' => false],
                ['handle' => 'subject', 'name' => 'subject', 'type' => 'text', 'label' => 'Subject', 'required' => true],
                ['handle' => 'message', 'name' => 'message', 'type' => 'textarea', 'label' => 'Your Message', 'required' => true],
            ],
            'email_recipients' => ['admin@' . tenant('slug') . '.test'],
            'success_message' => 'Thank you for your message. We will get back to you soon!',
            'redirect_url' => '/thank-you',
            'store_submissions' => true,
            'is_active' => true,
        ]);

        Form::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => 'Newsletter Signup',
            'handle' => 'newsletter',
            'description' => 'Newsletter subscription form',
            'fields' => [
                ['handle' => 'email', 'name' => 'email', 'type' => 'email', 'label' => 'Email Address', 'required' => true],
            ],
            'email_recipients' => [],
            'success_message' => 'You have been subscribed to our newsletter.',
            'store_submissions' => true,
            'is_active' => true,
        ]);
    }
}
