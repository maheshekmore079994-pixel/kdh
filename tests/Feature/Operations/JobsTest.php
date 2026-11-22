<?php

namespace Tests\Feature\Operations;

use App\Enums\InvoiceStatus;
use App\Enums\JobStatus;
use App\Enums\Permission;
use App\Exceptions\DomainException;
use App\Livewire\Jobs\Form;
use App\Livewire\Jobs\Show;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\User;
use App\Services\JobService;
use App\Services\PaymentService;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JobsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConfigurationSeeder::class);
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_job_lifecycle_with_advance_and_balance(): void
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->priced('150.00')->create();
        $jobs = app(JobService::class);

        $job = $jobs->create(['customer_id' => $customer->id, 'service_id' => $service->id, 'title' => 'PAN application', 'quantity' => 1, 'unit_price' => '150']);
        $this->assertMatchesRegularExpression('/^JOB-\d{4}-00001$/', $job->job_number);
        $this->assertSame(JobStatus::Pending, $job->status);
        $this->assertCount(1, $job->histories);

        $invoice = $jobs->bill($job);
        $this->assertSame('150.00', $invoice->total);
        $this->assertSame($job->id, $invoice->service_job_id);

        $cash = PaymentMethod::query()->where('code', 'cash')->first();
        app(PaymentService::class)->record($invoice, '50', $cash->id);
        $this->assertSame(InvoiceStatus::Partial, $job->fresh()->invoice->status);
        $this->assertSame('100.00', $job->fresh()->invoice->balance_due);

        $jobs->changeStatus($job, JobStatus::Processing, 'Started');
        $jobs->changeStatus($job, JobStatus::Completed);
        $jobs->changeStatus($job, JobStatus::Delivered);
        $this->assertNotNull($job->fresh()->delivered_at);
        $this->assertCount(4, $job->fresh()->histories);

        $this->expectException(DomainException::class);
        $jobs->changeStatus($job->fresh(), JobStatus::Pending);
    }

    public function test_job_cannot_be_billed_twice_or_without_price(): void
    {
        $customer = Customer::factory()->create();
        $jobs = app(JobService::class);

        $free = $jobs->create(['customer_id' => $customer->id, 'title' => 'TBD', 'quantity' => 1, 'unit_price' => 0]);
        try {
            $jobs->bill($free);
            $this->fail('Expected DomainException for zero price');
        } catch (DomainException) {
        }

        $job = $jobs->create(['customer_id' => $customer->id, 'title' => 'Prints', 'quantity' => 10, 'unit_price' => '2']);
        $jobs->bill($job);
        $this->expectException(DomainException::class);
        $jobs->bill($job->fresh());
    }

    public function test_form_component_creates_job_and_show_page_renders(): void
    {
        $customer = Customer::factory()->create();

        Livewire::test(Form::class)
            ->set('customerId', $customer->id)
            ->set('title', 'Lamination of certificates')
            ->set('quantity', '3')
            ->set('unitPrice', '20')
            ->set('receivedAt', now()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $job = $customer->jobs()->first();
        $this->assertSame('60.00', $job->total_amount);

        $this->get(route('jobs.index'))->assertOk()->assertSee($job->job_number);
        $this->get(route('jobs.show', $job))->assertOk()->assertSee('Lamination of certificates');
        $this->get(route('print.job', $job))->assertOk()->assertSee('JOB RECEIPT');
        $this->get(route('pdf.job', $job))->assertOk()->assertHeader('content-type', 'application/pdf');

        Livewire::test(Show::class, ['job' => $job])
            ->call('bill')
            ->call('setStatus', 'processing')
            ->assertHasNoErrors();
        $this->assertSame(JobStatus::Processing, $job->fresh()->status);
        $this->assertNotNull($job->fresh()->invoice_id);
    }

    public function test_cancelling_requires_reason_and_operator_permission_is_enforced(): void
    {
        $customer = Customer::factory()->create();
        $job = app(JobService::class)->create(['customer_id' => $customer->id, 'title' => 'X', 'quantity' => 1, 'unit_price' => '5']);

        Livewire::test(Show::class, ['job' => $job])->call('setStatus', 'cancelled')->assertHasErrors('cancelNote');
        $this->assertSame(JobStatus::Pending, $job->fresh()->status);

        $operator = User::factory()->operator([Permission::Customers])->create();
        $this->actingAs($operator)->get(route('jobs.index'))->assertForbidden();
    }
}
