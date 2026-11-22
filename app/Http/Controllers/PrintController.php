<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceJob;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PrintController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function invoice(Request $request, Invoice $invoice, string $format = 'receipt'): View
    {
        Gate::authorize('billing');

        return view($this->invoiceView($format), $this->invoiceData($invoice) + ['auto' => $request->boolean('auto')]);
    }

    public function invoicePdf(Invoice $invoice, string $format = 'a4'): Response
    {
        Gate::authorize('billing');

        $pdf = Pdf::loadView($this->invoiceView($format), $this->invoiceData($invoice) + ['auto' => false, 'pdf' => true]);
        if ($format === 'receipt') {
            $pdf->setPaper([0, 0, 226.77, 800], 'portrait'); // 80 mm wide
        } else {
            $pdf->setPaper('a4');
        }

        return $pdf->download($invoice->invoice_number.($format === 'receipt' ? '-receipt' : '').'.pdf');
    }

    public function payment(Request $request, Payment $payment): View
    {
        Gate::authorize('billing');
        $payment->load(['invoice.customer', 'method', 'receiver']);

        return view('print.payment', [
            'payment' => $payment,
            'business' => $this->settings->group('business'),
            'auto' => $request->boolean('auto'),
        ]);
    }

    public function job(Request $request, ServiceJob $job): View
    {
        Gate::authorize('jobs');
        $job->load(['customer', 'service', 'variant', 'invoice.payments', 'assignee']);

        return view('print.job', [
            'job' => $job,
            'business' => $this->settings->group('business'),
            'invoiceSettings' => $this->settings->group('invoice'),
            'auto' => $request->boolean('auto'),
        ]);
    }

    public function jobPdf(ServiceJob $job): Response
    {
        Gate::authorize('jobs');
        $job->load(['customer', 'service', 'variant', 'invoice.payments', 'assignee']);

        return Pdf::loadView('print.job', [
            'job' => $job,
            'business' => $this->settings->group('business'),
            'invoiceSettings' => $this->settings->group('invoice'),
            'auto' => false,
            'pdf' => true,
        ])->setPaper('a5')->download($job->job_number.'.pdf');
    }

    private function invoiceView(string $format): string
    {
        return $format === 'a4' ? 'print.invoice-a4' : 'print.invoice-receipt';
    }

    private function invoiceData(Invoice $invoice): array
    {
        $invoice->load(['items', 'customer', 'payments.method', 'creator']);

        return [
            'invoice' => $invoice,
            'business' => $this->settings->group('business'),
            'invoiceSettings' => $this->settings->group('invoice'),
            'taxLabel' => $this->settings->get('tax.label', 'Tax'),
            'logoUrl' => $this->logoUrl(),
        ];
    }

    private function logoUrl(): string
    {
        $path = (string) $this->settings->get('business.logo_path', 'images/brand/kdh-logo.png');

        return str_starts_with($path, 'storage/') ? public_path($path) : public_path($path);
    }
}
