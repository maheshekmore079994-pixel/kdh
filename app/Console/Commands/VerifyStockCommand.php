<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\StockService;
use Illuminate\Console\Command;

class VerifyStockCommand extends Command
{
    protected $signature = 'stock:verify';

    protected $description = 'Check that every product balance equals the sum of its stock movements';

    public function handle(StockService $stock): int
    {
        $bad = 0;
        foreach (Product::query()->cursor() as $product) {
            [$stored, $computed] = $stock->verify($product);
            if (bccomp($stored, $computed, 3) !== 0) {
                $bad++;
                $this->components->error("{$product->sku} {$product->name}: stored {$stored}, movements {$computed}");
            }
        }

        $bad === 0
            ? $this->components->info('All product balances match their movement history.')
            : $this->components->warn("{$bad} product(s) need an adjustment.");

        return $bad === 0 ? self::SUCCESS : self::FAILURE;
    }
}
