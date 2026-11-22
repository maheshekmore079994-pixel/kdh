<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\NumberSequence;
use App\Models\PaymentMethod;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Unit;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds configuration only: payment methods, units, category and service NAMES (no prices),
 * expense categories, number sequences and settings keys. Never seeds transactions.
 * Safe to run repeatedly (idempotent).
 */
class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPaymentMethods();
        $this->seedUnits();
        $this->seedServiceCatalogue();
        $this->seedExpenseCategories();
        $this->seedProductCategories();
        $this->seedNumberSequences();
        $this->seedSettings();

        app(SettingsService::class)->flush();
    }

    private function seedPaymentMethods(): void
    {
        $methods = [
            ['code' => 'cash', 'name' => 'Cash', 'type' => 'cash', 'sort_order' => 1],
            ['code' => 'upi', 'name' => 'UPI', 'type' => 'upi', 'sort_order' => 2],
            ['code' => 'bank', 'name' => 'Bank transfer', 'type' => 'bank', 'sort_order' => 3],
            ['code' => 'other', 'name' => 'Other', 'type' => 'other', 'sort_order' => 9],
        ];
        foreach ($methods as $m) {
            PaymentMethod::query()->firstOrCreate(['code' => $m['code']], $m);
        }
    }

    private function seedUnits(): void
    {
        $units = [
            ['abbreviation' => 'pcs', 'name' => 'Pieces'],
            ['abbreviation' => 'page', 'name' => 'Pages'],
            ['abbreviation' => 'sheet', 'name' => 'Sheets'],
            ['abbreviation' => 'copy', 'name' => 'Copies'],
            ['abbreviation' => 'set', 'name' => 'Sets'],
            ['abbreviation' => 'hr', 'name' => 'Hours'],
            ['abbreviation' => 'ream', 'name' => 'Reams'],
            ['abbreviation' => 'box', 'name' => 'Boxes'],
            ['abbreviation' => 'pkt', 'name' => 'Packets'],
        ];
        foreach ($units as $u) {
            Unit::query()->firstOrCreate(['abbreviation' => $u['abbreviation']], $u);
        }
    }

    private function seedServiceCatalogue(): void
    {
        $catalogue = [
            'Xerox & Printing' => ['Black & white Xerox', 'Black & white printing', 'Colour printing', 'Photo printing'],
            'Scanning & Typing' => ['Scanning', 'Document typing', 'Document formatting'],
            'Online Applications' => ['Online application form', 'Examination application', 'Job application', 'Admission / education form'],
            'Government Services' => ['Government certificate application', 'Government service (CSC)'],
            'Finishing' => ['Lamination', 'Spiral binding', 'Hard binding'],
            'Other Digital Services' => ['Other digital service'],
        ];

        $sort = 0;
        foreach ($catalogue as $categoryName => $services) {
            $category = ServiceCategory::query()->firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'sort_order' => ++$sort],
            );

            $serviceSort = 0;
            foreach ($services as $serviceName) {
                Service::query()->firstOrCreate(
                    ['service_category_id' => $category->id, 'name' => $serviceName],
                    ['unit' => 'pcs', 'default_price' => null, 'sort_order' => ++$serviceSort],
                );
            }
        }
    }

    private function seedExpenseCategories(): void
    {
        $names = ['Electricity', 'Rent', 'Internet', 'Maintenance', 'Transport', 'Salary', 'Miscellaneous'];
        foreach ($names as $i => $name) {
            ExpenseCategory::query()->firstOrCreate(['name' => $name], ['sort_order' => $i + 1]);
        }
    }

    private function seedProductCategories(): void
    {
        $names = ['Paper', 'Photo paper', 'Ink & toner', 'Stationery', 'Lamination & binding supplies', 'Other'];
        foreach ($names as $i => $name) {
            ProductCategory::query()->firstOrCreate(['name' => $name], ['sort_order' => $i + 1]);
        }
    }

    private function seedNumberSequences(): void
    {
        $sequences = [
            'invoice' => 'INV', 'job' => 'JOB', 'payment' => 'RCT', 'purchase' => 'PUR', 'expense' => 'EXP',
        ];
        foreach ($sequences as $key => $prefix) {
            NumberSequence::query()->firstOrCreate(
                ['key' => $key],
                ['prefix' => $prefix, 'padding' => 5, 'next_number' => 1, 'reset_yearly' => true, 'year' => (int) now()->format('Y')],
            );
        }
    }

    private function seedSettings(): void
    {
        foreach (SettingsService::defaults() as $group => $keys) {
            foreach ($keys as $key => $value) {
                Setting::query()->firstOrCreate(
                    ['key' => "{$group}.{$key}"],
                    ['group' => $group, 'value' => $value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE)],
                );
            }
        }
    }
}
