<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_KEY = 'kdh.settings.all';

    /** @var array<string, mixed>|null */
    private ?array $loaded = null;

    /**
     * Default values for every known key. Business facts that are unknown stay blank on purpose.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            'business' => [
                'name' => 'KHADAVLI DIGITAL HUB',
                'tagline' => 'CSC & Online Service Center',
                'email' => 'khadavlidigitalhub@gmail.com',
                'phone' => '8055000414',
                'whatsapp' => '8055000414',
                'address' => '',
                'city' => '',
                'state' => '',
                'pincode' => '',
                'gstin' => '',
                'map_url' => '',
                'opening_hours' => '',
                'logo_path' => 'images/brand/kdh-logo.png',
            ],
            'invoice' => [
                'prefix' => 'INV',
                'job_prefix' => 'JOB',
                'payment_prefix' => 'RCT',
                'purchase_prefix' => 'PUR',
                'expense_prefix' => 'EXP',
                'footer_note' => 'Thank you for your business.',
                'terms' => '',
                'show_logo' => true,
                'receipt_width' => '80mm',
                'default_print' => 'receipt',
            ],
            'tax' => [
                'enabled' => false,
                'label' => 'GST',
                'rate' => null,
            ],
            'website' => [
                'enabled' => true,
                'hero_title' => '',
                'hero_subtitle' => '',
                'about' => '',
                'meta_description' => '',
                'show_opening_hours' => true,
                'show_map' => true,
                'privacy_policy' => '',
                'terms' => '',
            ],
            'documents' => [
                'max_size_mb' => 10,
                'retention_days' => 0,
            ],
            'app' => [
                'low_stock_dashboard_limit' => 5,
            ],
        ];
    }

    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $this->loaded = Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = [];
            foreach (Setting::query()->get(['key', 'value']) as $row) {
                $stored[$row->key] = $this->decode($row->value);
            }

            $flatDefaults = $this->flatDefaults();

            return array_replace($flatDefaults, array_intersect_key($stored, $flatDefaults) + $stored);
        });

        return $this->loaded;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function group(string $group): array
    {
        $prefix = $group.'.';
        $out = [];
        foreach ($this->all() as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $out[substr($key, strlen($prefix))] = $value;
            }
        }

        return $out;
    }

    public function set(string $key, mixed $value): void
    {
        [$group] = explode('.', $key, 2) + [null];

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['group' => $group ?? 'app', 'value' => $this->encode($value)],
        );

        $this->flush();
    }

    /** @param array<string, mixed> $values keyed by full key (group.key) */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            [$group] = explode('.', $key, 2) + [null];
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['group' => $group ?? 'app', 'value' => $this->encode($value)],
            );
        }

        $this->flush();
    }

    public function flush(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
    }

    public function taxEnabled(): bool
    {
        return (bool) $this->get('tax.enabled', false) && $this->get('tax.rate') !== null && $this->get('tax.rate') !== '';
    }

    private function flatDefaults(): array
    {
        $flat = [];
        foreach (self::defaults() as $group => $keys) {
            foreach ($keys as $key => $value) {
                $flat["{$group}.{$key}"] = $value;
            }
        }

        return $flat;
    }

    private function encode(mixed $value): ?string
    {
        return $value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function decode(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
