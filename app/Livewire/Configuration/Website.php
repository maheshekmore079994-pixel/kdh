<?php

namespace App\Livewire\Configuration;

use App\Services\AuditService;
use App\Services\SettingsService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Website content')]
class Website extends Component
{
    public array $form = [];

    public function mount(SettingsService $settings): void
    {
        Gate::authorize('website');
        $this->form = $settings->group('website');
    }

    public function save(SettingsService $settings, AuditService $audit): void
    {
        Gate::authorize('website');

        $validated = $this->validate([
            'form.enabled' => ['boolean'],
            'form.hero_title' => ['nullable', 'string', 'max:120'],
            'form.hero_subtitle' => ['nullable', 'string', 'max:300'],
            'form.about' => ['nullable', 'string', 'max:5000'],
            'form.meta_description' => ['nullable', 'string', 'max:160'],
            'form.show_opening_hours' => ['boolean'],
            'form.show_map' => ['boolean'],
            'form.privacy_policy' => ['nullable', 'string', 'max:20000'],
            'form.terms' => ['nullable', 'string', 'max:20000'],
        ])['form'];

        $old = $settings->group('website');
        $values = [];
        foreach ($validated as $k => $v) {
            $values["website.{$k}"] = is_string($v) ? trim($v) : $v;
        }
        $settings->setMany($values);
        $audit->log('settings.website.updated', null, array_intersect_key($old, $validated), $validated);
        $this->form = $settings->group('website');

        Flux::toast(variant: 'success', text: __('Website content saved.'));
    }

    public function render(): View
    {
        return view('livewire.configuration.website');
    }
}
