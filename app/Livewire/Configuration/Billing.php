<?php

namespace App\Livewire\Configuration;

use App\Models\NumberSequence;
use App\Services\AuditService;
use App\Services\SettingsService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Billing & tax')]
class Billing extends Component
{
    public array $invoice = [];

    public array $tax = [];

    public function mount(SettingsService $settings): void
    {
        Gate::authorize('settings');
        $this->invoice = $settings->group('invoice');
        $this->tax = $settings->group('tax');
        $this->tax['rate'] = $this->tax['rate'] === null ? '' : (string) $this->tax['rate'];
    }

    public function save(SettingsService $settings, AuditService $audit): void
    {
        Gate::authorize('settings');

        $validated = $this->validate([
            'invoice.prefix' => ['required', 'string', 'alpha_num:ascii', 'max:10'],
            'invoice.job_prefix' => ['required', 'string', 'alpha_num:ascii', 'max:10'],
            'invoice.payment_prefix' => ['required', 'string', 'alpha_num:ascii', 'max:10'],
            'invoice.purchase_prefix' => ['required', 'string', 'alpha_num:ascii', 'max:10'],
            'invoice.expense_prefix' => ['required', 'string', 'alpha_num:ascii', 'max:10'],
            'invoice.footer_note' => ['nullable', 'string', 'max:500'],
            'invoice.terms' => ['nullable', 'string', 'max:2000'],
            'invoice.show_logo' => ['boolean'],
            'invoice.receipt_width' => ['required', 'in:58mm,80mm'],
            'invoice.default_print' => ['required', 'in:receipt,a4'],
            'tax.enabled' => ['boolean'],
            'tax.label' => ['required_if:tax.enabled,true', 'nullable', 'string', 'max:20'],
            'tax.rate' => ['required_if:tax.enabled,true', 'nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $old = ['invoice' => $settings->group('invoice'), 'tax' => $settings->group('tax')];

        $values = [];
        foreach ($validated['invoice'] as $k => $v) {
            $values["invoice.{$k}"] = is_string($v) ? trim($v) : $v;
        }
        $values['tax.enabled'] = (bool) $validated['tax']['enabled'];
        $values['tax.label'] = trim((string) $validated['tax']['label']);
        $values['tax.rate'] = $validated['tax']['rate'] === '' || $validated['tax']['rate'] === null ? null : number_format((float) $validated['tax']['rate'], 2, '.', '');
        $settings->setMany($values);

        foreach (['invoice' => 'prefix', 'job' => 'job_prefix', 'payment' => 'payment_prefix', 'purchase' => 'purchase_prefix', 'expense' => 'expense_prefix'] as $key => $field) {
            NumberSequence::query()->where('key', $key)->update(['prefix' => strtoupper(trim($validated['invoice'][$field]))]);
        }

        $audit->log('settings.billing.updated', null, $old, $validated);
        $this->mount($settings);

        Flux::toast(variant: 'success', text: __('Billing settings saved.'));
    }

    public function render(): View
    {
        return view('livewire.configuration.billing', [
            'sequences' => NumberSequence::query()->orderBy('key')->get(),
        ]);
    }
}
