<?php

namespace Tests\Feature\Api\v1;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_contact_without_auth(): void
    {
        $response = $this->postJson('/api/v1/contacts', [
            'name'    => 'Test User',
            'email'   => 'test@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello Xibra',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('contacts', [
            'email'   => 'test@example.com',
            'subject' => 'Inquiry',
        ]);
    }

    public function test_can_list_contacts_without_auth(): void
    {
        Contact::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_get_contact_without_auth(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->getJson('/api/v1/contacts/' . $contact->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $contact->id]]);
    }

    public function test_can_update_contact_without_auth(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->putJson('/api/v1/contacts/' . $contact->id, [
            'status' => 'read',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('contacts', [
            'id'    => $contact->id,
            'status' => 'read',
        ]);
    }

    public function test_can_delete_contact_without_auth(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson('/api/v1/contacts/' . $contact->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }
}
