<?php

namespace App\Livewire\Configuration;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceVariant;
use App\Models\Unit;
use App\Services\AuditService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Service catalogue: categories → services → price variants. All prices are entered by the admin.
 */
#[Title('Services & prices')]
class Catalogue extends Component
{
    #[Url]
    public ?int $categoryId = null;

    public string $search = '';

    // Category form
    public ?int $catEditingId = null;

    public string $catName = '';

    public string $catDescription = '';

    public bool $catActive = true;

    public bool $catOnWebsite = true;

    // Service form
    public ?int $svcEditingId = null;

    public string $svcName = '';

    public string $svcCode = '';

    public string $svcUnit = 'pcs';

    public string $svcPrice = '';

    public string $svcDescription = '';

    public bool $svcActive = true;

    public bool $svcOnWebsite = true;

    public ?int $svcCategoryId = null;

    // Variant form
    public ?int $varServiceId = null;

    public ?int $varEditingId = null;

    public string $varName = '';

    public string $varPrice = '';

    public string $varMinQty = '1';

    public bool $varActive = true;

    public function mount(): void
    {
        Gate::authorize('settings');
        $this->categoryId ??= ServiceCategory::query()->orderBy('sort_order')->value('id');
    }

    /* ---------- categories ---------- */

    public function createCategory(): void
    {
        Gate::authorize('settings');
        $this->reset(['catEditingId', 'catName', 'catDescription', 'catActive', 'catOnWebsite']);
        $this->resetValidation();
        Flux::modal('category-form')->show();
    }

    public function editCategory(int $id): void
    {
        Gate::authorize('settings');
        $c = ServiceCategory::query()->findOrFail($id);
        $this->catEditingId = $c->id;
        $this->catName = $c->name;
        $this->catDescription = (string) $c->description;
        $this->catActive = $c->is_active;
        $this->catOnWebsite = $c->show_on_website;
        $this->resetValidation();
        Flux::modal('category-form')->show();
    }

    public function saveCategory(AuditService $audit): void
    {
        Gate::authorize('settings');
        $v = $this->validate([
            'catName' => ['required', 'string', 'max:100'],
            'catDescription' => ['nullable', 'string', 'max:500'],
            'catActive' => ['boolean'],
            'catOnWebsite' => ['boolean'],
        ]);

        $c = $this->catEditingId ? ServiceCategory::query()->findOrFail($this->catEditingId) : new ServiceCategory(['sort_order' => ServiceCategory::query()->max('sort_order') + 1]);
        $c->fill([
            'name' => trim($v['catName']),
            'slug' => $this->uniqueSlug($v['catName'], $c->id),
            'description' => $v['catDescription'] ?: null,
            'is_active' => $v['catActive'],
            'show_on_website' => $v['catOnWebsite'],
        ]);
        if ($c->exists) {
            $audit->logChanges('catalogue.category.updated', $c);
            $c->save();
        } else {
            $c->save();
            $audit->log('catalogue.category.created', $c, [], ['name' => $c->name]);
            $this->categoryId = $c->id;
        }

        Flux::modal('category-form')->close();
        Flux::toast(variant: 'success', text: __('Category saved.'));
    }

    /* ---------- services ---------- */

    public function createService(): void
    {
        Gate::authorize('settings');
        $this->reset(['svcEditingId', 'svcName', 'svcCode', 'svcUnit', 'svcPrice', 'svcDescription', 'svcActive', 'svcOnWebsite']);
        $this->svcCategoryId = $this->categoryId;
        $this->resetValidation();
        Flux::modal('service-form')->show();
    }

    public function editService(int $id): void
    {
        Gate::authorize('settings');
        $s = Service::query()->findOrFail($id);
        $this->svcEditingId = $s->id;
        $this->svcCategoryId = $s->service_category_id;
        $this->svcName = $s->name;
        $this->svcCode = (string) $s->code;
        $this->svcUnit = $s->unit;
        $this->svcPrice = $s->default_price === null ? '' : (string) $s->default_price;
        $this->svcDescription = (string) $s->description;
        $this->svcActive = $s->is_active;
        $this->svcOnWebsite = $s->show_on_website;
        $this->resetValidation();
        Flux::modal('service-form')->show();
    }

    public function saveService(AuditService $audit): void
    {
        Gate::authorize('settings');
        $v = $this->validate([
            'svcCategoryId' => ['required', 'exists:service_categories,id'],
            'svcName' => ['required', 'string', 'max:150'],
            'svcCode' => ['nullable', 'string', 'max:30', Rule::unique('services', 'code')->ignore($this->svcEditingId)],
            'svcUnit' => ['required', 'string', 'max:20'],
            'svcPrice' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'svcDescription' => ['nullable', 'string', 'max:1000'],
            'svcActive' => ['boolean'],
            'svcOnWebsite' => ['boolean'],
        ]);

        $s = $this->svcEditingId ? Service::query()->findOrFail($this->svcEditingId) : new Service(['sort_order' => Service::query()->where('service_category_id', $v['svcCategoryId'])->max('sort_order') + 1]);
        $s->fill([
            'service_category_id' => $v['svcCategoryId'],
            'name' => trim($v['svcName']),
            'code' => $v['svcCode'] ? strtoupper(trim($v['svcCode'])) : null,
            'unit' => $v['svcUnit'],
            'default_price' => $v['svcPrice'] === '' || $v['svcPrice'] === null ? null : number_format((float) $v['svcPrice'], 2, '.', ''),
            'description' => $v['svcDescription'] ?: null,
            'is_active' => $v['svcActive'],
            'show_on_website' => $v['svcOnWebsite'],
        ]);
        if ($s->exists) {
            $audit->logChanges('catalogue.service.updated', $s);
            $s->save();
        } else {
            $s->save();
            $audit->log('catalogue.service.created', $s, [], ['name' => $s->name, 'default_price' => $s->default_price]);
        }

        Flux::modal('service-form')->close();
        Flux::toast(variant: 'success', text: __('Service saved.'));
    }

    /* ---------- variants ---------- */

    public function createVariant(int $serviceId): void
    {
        Gate::authorize('settings');
        $this->reset(['varEditingId', 'varName', 'varPrice', 'varMinQty', 'varActive']);
        $this->varServiceId = $serviceId;
        $this->resetValidation();
        Flux::modal('variant-form')->show();
    }

    public function editVariant(int $id): void
    {
        Gate::authorize('settings');
        $vr = ServiceVariant::query()->findOrFail($id);
        $this->varEditingId = $vr->id;
        $this->varServiceId = $vr->service_id;
        $this->varName = $vr->name;
        $this->varPrice = (string) $vr->unit_price;
        $this->varMinQty = qty($vr->min_quantity);
        $this->varActive = $vr->is_active;
        $this->resetValidation();
        Flux::modal('variant-form')->show();
    }

    public function saveVariant(AuditService $audit): void
    {
        Gate::authorize('settings');
        $v = $this->validate([
            'varServiceId' => ['required', 'exists:services,id'],
            'varName' => ['required', 'string', 'max:150', Rule::unique('service_variants', 'name')->where('service_id', $this->varServiceId)->ignore($this->varEditingId)],
            'varPrice' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'varMinQty' => ['required', 'numeric', 'min:0.001'],
            'varActive' => ['boolean'],
        ]);

        $vr = $this->varEditingId ? ServiceVariant::query()->findOrFail($this->varEditingId) : new ServiceVariant(['sort_order' => ServiceVariant::query()->where('service_id', $v['varServiceId'])->max('sort_order') + 1]);
        $vr->fill([
            'service_id' => $v['varServiceId'],
            'name' => trim($v['varName']),
            'unit_price' => number_format((float) $v['varPrice'], 2, '.', ''),
            'min_quantity' => number_format((float) $v['varMinQty'], 3, '.', ''),
            'is_active' => $v['varActive'],
        ]);
        if ($vr->exists) {
            $audit->logChanges('catalogue.variant.updated', $vr);
            $vr->save();
        } else {
            $vr->save();
            $audit->log('catalogue.variant.created', $vr, [], ['name' => $vr->name, 'unit_price' => $vr->unit_price]);
        }

        Flux::modal('variant-form')->close();
        Flux::toast(variant: 'success', text: __('Price variant saved.'));
    }

    public function deleteVariant(int $id, AuditService $audit): void
    {
        Gate::authorize('settings');
        $vr = ServiceVariant::query()->findOrFail($id);
        $audit->log('catalogue.variant.deleted', $vr, ['name' => $vr->name, 'unit_price' => $vr->unit_price], []);
        $vr->delete();
        Flux::toast(variant: 'success', text: __('Price variant removed. Past invoices keep their recorded prices.'));
    }

    private function uniqueSlug(string $name, ?int $ignoreId): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;
        while (ServiceCategory::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function render(): View
    {
        $categories = ServiceCategory::query()->withCount('services')->orderBy('sort_order')->orderBy('name')->get();

        $services = Service::query()->with('variants')
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when(! $this->search && $this->categoryId, fn ($q) => $q->where('service_category_id', $this->categoryId))
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        return view('livewire.configuration.catalogue', [
            'categories' => $categories,
            'services' => $services,
            'currentCategory' => $categories->firstWhere('id', $this->categoryId),
            'units' => Unit::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
