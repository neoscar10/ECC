<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\MembershipTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;
use Database\Seeders\RoleSeeder;
use Database\Seeders\MembershipTiersSeeder;

class UserBulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $tier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MembershipTiersSeeder::class);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super_admin');

        $this->tier = MembershipTier::first();
        // Ensure it's active
        $this->tier->update(['is_active' => true]);
    }

    public function test_admin_can_download_import_template()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(\App\Livewire\Admin\Users\UsersIndex::class)
            ->call('downloadTemplate')
            ->assertFileDownloaded('ecc_users_import_template.csv');
            
        // Get the response directly to test contents
        $response = $this->get('/livewire/update'); // Livewire handles the download internally, but we can call the method directly to inspect output
        // Instead of testing the stream download response directly via HTTP, we know the component returns a StreamedResponse. We trust the prior test asserts completion. 
    }

    public function test_import_validation_rejects_invalid_file()
    {
        $this->actingAs($this->adminUser);
        Storage::fake('local');

        // Create a PDF file to simulate invalid mime
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        Livewire::test(\App\Livewire\Admin\Users\UsersIndex::class)
            ->set('bulkUploadFile', $file)
            ->assertHasErrors(['bulkUploadFile']);
    }

    public function test_import_successfully_creates_users()
    {
        $this->actingAs($this->adminUser);
        
        $csvContent = "full_name,email,phone,membership_tier_code,membership_expiry_date\n";
        $csvContent .= "Test User One,testone@example.com,+919876543201,{$this->tier->code},\n";
        $csvContent .= "Test User Two,testtwo@example.com,+919876543202,{$this->tier->code},2030-12-31\n";

        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        Livewire::test(\App\Livewire\Admin\Users\UsersIndex::class)
            ->set('bulkUploadFile', $file)
            ->call('processImport')
            ->assertHasNoErrors()
            ->assertSet('bulkResults.total', 2)
            ->assertSet('bulkResults.created', 2)
            ->assertSet('bulkResults.failed', 0);

        $this->assertDatabaseHas('users', ['email' => 'testone@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'testtwo@example.com']);
        
        $user1 = User::where('email', 'testone@example.com')->first();
        $this->assertDatabaseHas('memberships', [
            'user_id' => $user1->id,
            'membership_tier_id' => $this->tier->id,
            'status' => 'active'
        ]);
    }

    public function test_import_skips_duplicates_without_failing_entire_process()
    {
        $this->actingAs($this->adminUser);
        
        // Pre-create a user to cause a duplicate error
        User::factory()->create(['email' => 'duplicate@example.com']);
        
        $csvContent = "full_name,email,phone,membership_tier_code,membership_expiry_date\n";
        $csvContent .= "Duplicate User,duplicate@example.com,+919876543203,{$this->tier->code},\n"; // Should skip
        $csvContent .= "Valid User,valid@example.com,+919876543204,{$this->tier->code},\n"; // Should create

        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        Livewire::test(\App\Livewire\Admin\Users\UsersIndex::class)
            ->set('bulkUploadFile', $file)
            ->call('processImport')
            ->assertHasNoErrors()
            ->assertSet('bulkResults.total', 2)
            ->assertSet('bulkResults.created', 1)
            ->assertSet('bulkResults.skipped', 1);

        $this->assertDatabaseHas('users', ['email' => 'valid@example.com']);
    }

    public function test_import_logs_failed_rows_on_validation_errors()
    {
        $this->actingAs($this->adminUser);
        
        $csvContent = "full_name,email,phone,membership_tier_code,membership_expiry_date\n";
        // Missing email
        $csvContent .= "Test User One,,+919876543201,{$this->tier->code},\n";

        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        Livewire::test(\App\Livewire\Admin\Users\UsersIndex::class)
            ->set('bulkUploadFile', $file)
            ->call('processImport')
            ->assertSet('bulkResults.total', 1)
            ->assertSet('bulkResults.failed', 1)
            ->assertSet('bulkResults.created', 0);
    }

    public function test_import_succeeds_even_if_password_columns_present_in_legacy_template()
    {
        $this->actingAs($this->adminUser);
        
        // This simulates an admin uploading an older template that still had password headers
        $csvContent = "full_name,email,phone,membership_tier_code,membership_expiry_date,password_mode,password\n";
        $csvContent .= "Legacy User,legacy@example.com,+919876543209,{$this->tier->code},,manual,password123\n";

        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        Livewire::test(\App\Livewire\Admin\Users\UsersIndex::class)
            ->set('bulkUploadFile', $file)
            ->call('processImport')
            ->assertHasNoErrors()
            ->assertSet('bulkResults.total', 1)
            ->assertSet('bulkResults.created', 1);

        $this->assertDatabaseHas('users', ['email' => 'legacy@example.com']);
    }
}
