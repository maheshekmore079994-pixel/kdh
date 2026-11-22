<?php

namespace App\Livewire\Tools;

use App\Models\PrintCostProfile;
use App\Services\AuditService;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Estimates printing cost and margin from admin-defined cost profiles. Never writes transactions.
 */
#[Title('Print cost calculator')]
class PrintCalculator extends Component
{
    public ?int $profileId = null;

    public string $pages = '1';

    public string $copies = '1';

    public string $sides = '1';

    public string $charge = '';

    // profile form
    public ?int $editingId = null;

    public string $name = '';

    public string $paper = '';

    public string $ink = '';

    public string $maintenance = '';

    public string $other = '';

    public string $notes = '';

    public function mount(): void
    {
        Gate::authorize('reports');
        $this->profileId = PrintCostProfile::query()->where('is_active', true)->value('id');
    }

    public function createProfile(): void
    {
        Gate::authorize('settings');
        $this->reset(['editingId', 'name', 'paper', 'ink', 'maintenance', 'other', 'notes']);
        $this->resetValidation();
        Flux::modal('profile-form')->show();
    }

    public function editProfile(int $id): void
    {
        Gate::authorize('settings');
        $p = PrintCostProfile::query()->findOrFail($id);
        $this->editingId = $p->id;
        $this->name = $p->name;
        $this->paper = $p->paper_cost_per_sheet === null ? '' : rtrim(rtrim((string) $p->paper_cost_per_sheet, '0'), '.');
        $this->ink = $p->ink_cost_per_page === null ? '' : rtrim(rtrim((string) $p->ink_cost_per_page, '0'), '.');
        $this->maintenance = $p->maintenance_per_page === null ? '' : rtrim(rtrim((string) $p->maintenance_per_page, '0'), '.');
        $this->other = $p->other_cost_per_page === null ? '' : rtrim(rtrim((string) $p->other_cost_per_page, '0'), '.');
        $this->notes = (string) $p->notes;
        $this->resetValidation();
        Flux::modal('profile-form')->show();
    }

    public function saveProfile(AuditService $audit): void
    {
        Gate::authorize('settings');
        $v = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'paper' => ['nullable', 'numeric', 'min:0'],
            'ink' => ['nullable', 'numeric', 'min:0'],
            'maintenance' => ['nullable', 'numeric', 'min:0'],
            'other' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $num = fn ($x) => $x === '' || $x === null ? null : number_format((float) $x, 4, '.', '');
        $data = ['name' => trim($v['name']), 'paper_cost_per_sheet' => $num($v['paper']), 'ink_cost_per_page' => $num($v['ink']), 'maintenance_per_page' => $num($v['maintenance']), 'other_cost_per_page' => $num($v['other']), 'notes' => $v['notes'] ?: null, 'is_active' => true];

        $p = $this->editingId ? PrintCostProfile::query()->findOrFail($this->editingId) : new PrintCostProfile;
        $p->fill($data);
        if ($p->exists) {
            $audit->logChanges('print_profile.updated', $p);
        }
        $p->save();
        if (! $this->editingId) {
            $audit->log('print_profile.created', $p, [], $data);
        }
        $this->profileId = $p->id;
        Flux::modal('profile-form')->close();
        Flux::toast(variant: 'success', text: __('Cost profile saved.'));
    }

    public function deleteProfile(int $id, AuditService $audit): void
    {
        Gate::authorize('settings');
        $p = PrintCostProfile::query()->findOrFail($id);
        $audit->log('print_profile.deleted', $p, ['name' => $p->name], []);
        $p->delete();
        if ($this->profileId === $id) {
            $this->profileId = PrintCostProfile::query()->value('id');
        }
    }

    public function render(): View
    {
        $profiles = PrintCostProfile::query()->orderBy('name')->get();
        $profile = $profiles->firstWhere('id', $this->profileId);

        $pages = max(0, (int) $this->pages);
        $copies = max(0, (int) $this->copies);
        $sidesPerSheet = (int) $this->sides === 2 ? 2 : 1;
        $totalPages = $pages * $copies;
        $sheets = (int) ceil($totalPages / $sidesPerSheet);

        $estimate = null;
        if ($profile) {
            $paperCost = bcmul((string) $sheets, (string) ($profile->paper_cost_per_sheet ?? '0'), 4);
            $perPage = bcadd(bcadd((string) ($profile->ink_cost_per_page ?? '0'), (string) ($profile->maintenance_per_page ?? '0'), 4), (string) ($profile->other_cost_per_page ?? '0'), 4);
            $pageCosts = bcmul((string) $totalPages, $perPage, 4);
            $cost = Money::round(bcadd($paperCost, $pageCosts, 4));
            $charge = $this->charge === '' ? null : Money::of($this->charge);
            $margin = $charge !== null ? Money::sub($charge, $cost) : null;
            $estimate = [
                'sheets' => $sheets, 'total_pages' => $totalPages,
                'paper_cost' => Money::round($paperCost), 'page_costs' => Money::round($pageCosts), 'cost' => $cost,
                'cost_per_page' => $totalPages > 0 ? Money::round(bcdiv($cost, (string) $totalPages, 4)) : '0.00',
                'charge' => $charge, 'margin' => $margin,
                'margin_pct' => $charge !== null && Money::cmp($charge, 0) > 0 ? number_format((float) bcdiv(bcmul($margin, '100', 4), $charge, 4), 1) : null,
            ];
        }

        return view('livewire.tools.print-calculator', ['profiles' => $profiles, 'profile' => $profile, 'estimate' => $estimate]);
    }
}
