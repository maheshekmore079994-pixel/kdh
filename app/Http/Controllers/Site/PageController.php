<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function home(): View
    {
        return view('site.home', [
            'categories' => $this->categories(),
        ] + $this->shared());
    }

    public function about(): View
    {
        return view('site.about', $this->shared());
    }

    public function services(): View
    {
        return view('site.services', ['categories' => $this->categories()] + $this->shared());
    }

    public function contact(): View
    {
        return view('site.contact', $this->shared());
    }

    public function privacy(): View
    {
        return view('site.legal', [
            'pageTitle' => __('Privacy Policy'),
            'content' => $this->settings->get('website.privacy_policy') ?: null,
            'kind' => 'privacy',
        ] + $this->shared());
    }

    public function terms(): View
    {
        return view('site.legal', [
            'pageTitle' => __('Terms of Service'),
            'content' => $this->settings->get('website.terms') ?: null,
            'kind' => 'terms',
        ] + $this->shared());
    }

    private function categories()
    {
        return ServiceCategory::query()->active()->where('show_on_website', true)
            ->with(['services' => fn ($q) => $q->where('is_active', true)->where('show_on_website', true)])
            ->get();
    }

    private function shared(): array
    {
        return [
            'business' => $this->settings->group('business'),
            'site' => $this->settings->group('website'),
        ];
    }
}
