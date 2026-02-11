<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\MembershipTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ImageRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        // Create role if it doesn't exist (Spatie)
        if (!\Spatie\Permission\Models\Role::where('name', 'admin')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']); 
        }

        // Create admin user
        $this->admin = User::factory()->create([
             'email' => 'admin@example.com'
        ]);
        $this->admin->assignRole('admin');
    }

    /** @test */
    public function profile_avatar_upload_restricts_invalid_formats()
    {
        $this->actingAs($this->admin);

        // Test valid formats
        $response = $this->postJson('/api/v1/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg')
        ]);
        $response->assertStatus(200);

        $response = $this->postJson('/api/v1/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.png')
        ]);
        $response->assertStatus(200);

        // Test invalid formats
        $response = $this->postJson('/api/v1/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('document.pdf', 100)
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['avatar']);
                 
        $response = $this->postJson('/api/v1/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.gif')
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['avatar']);

        $response = $this->postJson('/api/v1/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.webp')
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['avatar']);
    }

    /** @test */
    public function auction_lot_media_restricts_invalid_formats()
    {
        $this->actingAs($this->admin);

        // Minimal required data for step 2 validation
        // We might need to mock state or use the component directly
        
        Livewire::test(\App\Livewire\Admin\Auctions\Lots\LotFormModal::class)
            ->set('newImages', [UploadedFile::fake()->image('lot.jpg')])
            ->call('save') // This might fail validation on other fields, but we check specific image error if present
            ->assertHasNoErrors(['newImages']);

        Livewire::test(\App\Livewire\Admin\Auctions\Lots\LotFormModal::class)
            ->set('title', 'Valid Title')
            ->set('starting_price', 100)
            ->set('min_increment', 10)
            ->set('starts_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('ends_at', now()->addDays(2)->format('Y-m-d\TH:i'))
            ->set('newImages', [UploadedFile::fake()->create('doc.pdf', 100)])
            ->call('save')
            ->assertHasErrors(['newImages.*']);
            
        Livewire::test(\App\Livewire\Admin\Auctions\Lots\LotFormModal::class)
            ->set('title', 'Valid Title')
            ->set('starting_price', 100)
            ->set('min_increment', 10)
            ->set('starts_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('ends_at', now()->addDays(2)->format('Y-m-d\TH:i'))
            ->set('new360Images', [UploadedFile::fake()->image('360.gif')])
            ->call('save')
            ->assertHasErrors(['new360Images.*']);
    }

    /** @test */
    public function shop_product_media_restricts_invalid_formats()
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Admin\Shop\Products\Index::class)
            ->set('newImages', [UploadedFile::fake()->image('product.jpg')])
            ->assertHasNoErrors(['newImages']);

        Livewire::test(\App\Livewire\Admin\Shop\Products\Index::class)
            ->set('newImages', [UploadedFile::fake()->image('product.gif')])
            ->assertHasErrors(['newImages.*']);
            
        Livewire::test(\App\Livewire\Admin\Shop\Products\Index::class)
            ->set('activeGalleryUploads', [UploadedFile::fake()->create('doc.txt', 10)])
            ->assertHasErrors(['activeGalleryUploads.*']);
    }

    /** @test */
    public function archive_category_image_restricts_invalid_formats()
    {
        $this->actingAs($this->admin);
        
        // Need to set required fields to pass "store" validation if we were calling it,
        // but Livewire validation often runs on update.
        // Let's rely on the validation rules we saw.
        
        // Archive Category Index usually handles the modal
        Livewire::test(\App\Livewire\Admin\Archive\Categories\Index::class)
            // Valid
            ->set('image', UploadedFile::fake()->image('cat.png'))
            ->call('store') // expects title too, but we check image error
            ->assertHasNoErrors(['image']);

        Livewire::test(\App\Livewire\Admin\Archive\Categories\Index::class)
            // Invalid
            ->set('title', 'Valid Category')
            ->set('visibility', 'public')
            ->set('image', UploadedFile::fake()->image('cat.gif'))
            ->call('store')
            ->assertHasErrors(['image']);
    }

    /** @test */
    public function cms_block_image_restricts_invalid_formats()
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Admin\Cms\Blocks\Index::class)
            ->set('newSlideImage', UploadedFile::fake()->image('slide.webp'))
            ->call('addSlide')
            ->assertHasErrors(['newSlideImage']);

        Livewire::test(\App\Livewire\Admin\Cms\Blocks\Index::class)
            // Set required fields for store
            ->set('title', 'Block Title')
            ->set('placement', 'home') // Required for Step 1
            ->set('type', 'banner')
            ->set('contentImage', UploadedFile::fake()->create('banner.pdf'))
            ->call('store')
            ->assertHasErrors(['contentImage']);
    }
}
