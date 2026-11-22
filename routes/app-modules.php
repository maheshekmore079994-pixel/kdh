<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ReportExportController;
use App\Livewire\Billing\Invoices;
use App\Livewire\Billing\InvoiceShow;
use App\Livewire\Billing\Payments;
use App\Livewire\Billing\Pos;
use App\Livewire\Customers\Index;
use App\Livewire\Customers\Show;
use App\Livewire\Inventory\Products;
use App\Livewire\Inventory\ProductShow;
use App\Livewire\Inventory\Stock;
use App\Livewire\Jobs\Form;
use App\Livewire\Purchases\Suppliers;
use App\Livewire\Tools\PrintCalculator;
use App\Livewire\Website\Enquiries;
use Illuminate\Support\Facades\Route;

/*
| Module routes (included inside the authenticated /app group from routes/app.php).
| All modules are implemented; see docs/DEVELOPMENT_PLAN.md for status.
*/

// Customers
Route::get('customers', Index::class)->name('customers.index');
Route::get('customers/{customer}', Show::class)->name('customers.show');

// Billing
Route::get('pos', Pos::class)->name('pos');
Route::get('invoices', Invoices::class)->name('invoices.index');
Route::get('invoices/{invoice}', InvoiceShow::class)->name('invoices.show');
Route::get('payments', Payments::class)->name('payments.index');

// Print & PDF
Route::get('print/invoice/{invoice}/{format?}', [PrintController::class, 'invoice'])->name('print.invoice');
Route::get('print/payment/{payment}', [PrintController::class, 'payment'])->name('print.payment');
Route::get('print/job/{job}', [PrintController::class, 'job'])->name('print.job');
Route::get('pdf/invoice/{invoice}/{format?}', [PrintController::class, 'invoicePdf'])->name('pdf.invoice');
Route::get('pdf/job/{job}', [PrintController::class, 'jobPdf'])->name('pdf.job');

// Jobs
Route::get('jobs', App\Livewire\Jobs\Index::class)->name('jobs.index');
Route::get('jobs/create', Form::class)->name('jobs.create');
Route::get('jobs/{job}', App\Livewire\Jobs\Show::class)->name('jobs.show');
Route::get('jobs/{job}/edit', Form::class)->name('jobs.edit');

// Expenses
Route::get('expenses', App\Livewire\Expenses\Index::class)->name('expenses.index');

// Inventory & purchases
Route::get('products', Products::class)->name('products.index');
Route::get('products/{product}', ProductShow::class)->name('products.show');
Route::get('stock', Stock::class)->name('stock.index');
Route::get('suppliers', Suppliers::class)->name('suppliers.index');
Route::get('purchases', App\Livewire\Purchases\Index::class)->name('purchases.index');
Route::get('purchases/create', App\Livewire\Purchases\Form::class)->name('purchases.create');
Route::get('purchases/{purchase}', App\Livewire\Purchases\Show::class)->name('purchases.show');

// Reports & tools
Route::get('reports', App\Livewire\Reports\Index::class)->name('reports.index');
Route::get('reports/{report}', App\Livewire\Reports\Show::class)->name('reports.show');
Route::get('export/{report}/{format}', [ReportExportController::class, 'export'])->name('reports.export');
Route::get('tools/print-calculator', PrintCalculator::class)->name('tools.calculator');

// Website management
Route::get('enquiries', Enquiries::class)->name('enquiries.index');

// Documents (private download)
Route::get('documents/{document}/download', [DocumentController::class, 'download'])
    ->middleware('throttle:60,1')->name('documents.download');
