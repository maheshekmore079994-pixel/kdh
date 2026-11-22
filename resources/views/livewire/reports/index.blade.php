<div>
    <x-page-header :title="__('Reports & accounts')" :subtitle="__('Every report can be filtered by date and exported to CSV, Excel or PDF.')" />
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($reports as $key => $r)
            <a href="{{ route('reports.show', $key) }}" class="rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-[#D59A2F] hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-800" wire:navigate>
                <div class="flex items-center gap-2">
                    <flux:icon :name="match($key) { 'cashbook' => 'banknotes', 'sales' => 'document-text', 'payments' => 'currency-rupee', 'expenses' => 'receipt-percent', 'purchases' => 'shopping-cart', 'customer-dues' => 'users', 'supplier-dues' => 'truck', 'inventory-valuation' => 'cube', default => 'chart-bar' }" class="size-5 text-[#B9821F]" />
                    <flux:heading size="lg">{{ __($r['title']) }}</flux:heading>
                </div>
                <flux:text class="mt-1 text-sm">{{ __($r['description']) }}</flux:text>
            </a>
        @endforeach
    </div>
</div>
