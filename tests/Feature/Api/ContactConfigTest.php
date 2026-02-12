<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\ContactConfig;
use App\Models\ContactSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Admin\Enquiries\Config;

class ContactConfigTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_empty_config_if_db_empty()
    {
        $response = $this->getJson('/api/v1/contact/config');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'direct_lines',
                    'subjects'
                ]
            ]);
            
        // Check that values are null/empty, NOT hardcoded defaults
        $this->assertNull($response->json('data.direct_lines.0.value'));
        $this->assertNull($response->json('data.direct_lines.1.value'));
        $this->assertEmpty($response->json('data.subjects'));
    }

    /** @test */
    public function it_returns_db_config_if_exists()
    {
        ContactConfig::create([
            'concierge_phone' => '+1 555 000 1234',
            'support_email' => 'test@example.com',
        ]);

        ContactSubject::create(['label' => 'Test Subject', 'sort_order' => 1]);

        $response = $this->getJson('/api/v1/contact/config');

        $response->assertStatus(200);
        $this->assertEquals('+1 555 000 1234', $response->json('data.direct_lines.0.value'));
        $this->assertEquals('test@example.com', $response->json('data.direct_lines.1.value'));
        $this->assertEquals('Test Subject', $response->json('data.subjects.0.label'));
    }

    /** @test */
    public function admin_can_update_config_via_livewire()
    {
        $admin = User::factory()->create();
        
        Livewire::test(Config::class)
            ->set('concierge_phone', '999')
            ->set('support_email', 'admin@test.com')
            ->call('save');

        $this->assertDatabaseHas('contact_configs', [
            'concierge_phone' => '999',
            'support_email' => 'admin@test.com'
        ]);
    }

    /** @test */
    public function admin_can_add_and_remove_subjects()
    {
        Livewire::test(Config::class)
            ->set('newSubjectLabel', 'New Topic')
            ->call('addSubject');

        $this->assertDatabaseHas('contact_subjects', ['label' => 'New Topic']);

        $subject = ContactSubject::where('label', 'New Topic')->first();

        Livewire::test(Config::class)
            ->call('deleteSubject', $subject->id);

        $this->assertDatabaseMissing('contact_subjects', ['id' => $subject->id]);
    }
}
