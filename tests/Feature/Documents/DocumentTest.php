<?php

namespace Tests\Feature\Documents;

use App\Enums\Permission;
use App\Exceptions\DomainException;
use App\Livewire\Documents\Manager;
use App\Models\Customer;
use App\Models\User;
use App\Services\DocumentService;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConfigurationSeeder::class);
        Storage::fake('private');
    }

    public function test_pdf_can_be_attached_and_downloaded_by_authorised_staff(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = Customer::factory()->create();
        $this->actingAs($admin);

        $file = UploadedFile::fake()->create('aadhaar-copy.pdf', 120, 'application/pdf');
        $doc = app(DocumentService::class)->attach($customer, $file);

        Storage::disk('private')->assertExists($doc->path);
        $this->assertSame('private', $doc->disk);

        $this->get(route('documents.download', $doc))->assertOk()->assertHeader('content-type', 'application/pdf');

        $operator = User::factory()->operator([Permission::Billing])->create();
        $this->actingAs($operator)->get(route('documents.download', $doc))->assertForbidden();

        $this->assertGuest(auth()->logout() ?? null);
        $this->get(route('documents.download', $doc))->assertRedirect(route('login'));
    }

    public function test_executables_and_disguised_files_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $customer = Customer::factory()->create();

        $this->expectException(DomainException::class);
        app(DocumentService::class)->attach($customer, UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'));
    }

    public function test_mismatched_extension_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $customer = Customer::factory()->create();

        $this->expectException(DomainException::class);
        app(DocumentService::class)->attach($customer, UploadedFile::fake()->create('script.php.pdf', 10, 'text/plain'));
    }

    public function test_manager_component_uploads_and_deletes(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = Customer::factory()->create();

        $component = Livewire::actingAs($admin)
            ->test(Manager::class, ['model' => $customer])
            ->set('upload', UploadedFile::fake()->image('photo.jpg'))
            ->assertHasNoErrors();

        $doc = $customer->documents()->first();
        $this->assertNotNull($doc);

        $component->call('delete', $doc->id);
        $this->assertNull($customer->documents()->first());
        Storage::disk('private')->assertMissing($doc->path);
    }

    public function test_expired_documents_are_pruned(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $customer = Customer::factory()->create();
        $doc = app(DocumentService::class)->attach($customer, UploadedFile::fake()->create('old.pdf', 10, 'application/pdf'));
        $doc->forceFill(['expires_at' => now()->subDay()])->save();

        $this->assertSame(1, app(DocumentService::class)->pruneExpired());
        Storage::disk('private')->assertMissing($doc->path);
    }
}
