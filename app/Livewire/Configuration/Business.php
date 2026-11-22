<?php

namespace App\Livewire\Configuration;

use App\Services\AuditService;
use App\Services\SettingsService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

#[Title('Business details')]
class Business extends Component
{
    use WithFileUploads;

    public array $form = [];

    public $logo = null;

    public function mount(SettingsService $settings): void
    {
        Gate::authorize('settings');
        $this->form = $settings->group('business');
    }

    public function save(SettingsService $settings, AuditService $audit): void
    {
        Gate::authorize('settings');

        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:120'],
            'form.tagline' => ['nullable', 'string', 'max:160'],
            'form.email' => ['nullable', 'email', 'max:150'],
            'form.phone' => ['nullable', 'string', 'max:20'],
            'form.whatsapp' => ['nullable', 'string', 'max:20'],
            'form.address' => ['nullable', 'string', 'max:500'],
            'form.city' => ['nullable', 'string', 'max:80'],
            'form.state' => ['nullable', 'string', 'max:80'],
            'form.pincode' => ['nullable', 'string', 'max:10'],
            'form.gstin' => ['nullable', 'string', 'max:20'],
            'form.map_url' => ['nullable', 'url', 'max:500'],
            'form.opening_hours' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ])['form'];

        $old = $settings->group('business');

        if ($this->logo) {
            $path = $this->logo->store('branding', 'public');
            $validated['logo_path'] = 'storage/'.$path;
        }

        $values = [];
        foreach ($validated as $key => $value) {
            $values["business.{$key}"] = is_string($value) ? trim($value) : $value;
        }
        $settings->setMany($values);

        $audit->log('settings.business.updated', null, array_intersect_key($old, $validated), $validated);
        $this->form = $settings->group('business');
        $this->logo = null;

        Flux::toast(variant: 'success', text: __('Business details saved.'));
    }

    public function render(): View
    {
        return view('livewire.configuration.business', [
            'logoUrl' => str_starts_with($this->form['logo_path'] ?? '', 'storage/')
                ? Storage::disk('public')->url(substr($this->form['logo_path'], 8))
                : asset($this->form['logo_path'] ?? 'images/brand/kdh-logo.png'),
        ]);
    }
}
