<?php

namespace Tests\Feature\Api\v1;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_contact_message(): void
    {
        $this->postJson('/api/v1/contacts', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello Xibra',
        ])->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('contacts', [
            'email' => 'test@example.com',
            'subject' => 'Inquiry',
        ]);
    }

    public function test_guest_cannot_list_contacts(): void
    {
        Contact::factory()->count(3)->create();

        $this->getJson('/api/v1/contacts')->assertUnauthorized();
    }

    public function test_guest_cannot_view_update_or_delete_contact(): void
    {
        $contact = Contact::factory()->create();

        $this->getJson("/api/v1/contacts/{$contact->id}")->assertUnauthorized();
        $this->putJson("/api/v1/contacts/{$contact->id}", ['status' => 'read'])->assertUnauthorized();
        $this->deleteJson("/api/v1/contacts/{$contact->id}")->assertUnauthorized();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'status' => $contact->status,
        ]);
    }

    public function test_authenticated_user_can_list_contacts(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Contact::factory()->count(3)->create();

        $this->getJson('/api/v1/contacts')
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_authenticated_user_can_update_and_delete_contact(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $contact = Contact::factory()->create();

        $this->putJson("/api/v1/contacts/{$contact->id}", ['status' => 'read'])
            ->assertOk();

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'status' => 'read']);

        $this->deleteJson("/api/v1/contacts/{$contact->id}")
            ->assertOk();

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }
}
