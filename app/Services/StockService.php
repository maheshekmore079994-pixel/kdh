<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\DomainException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The only place that changes product stock. Every change is a stock_movements row.
 */
class StockService
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Record a signed quantity change for a product. Positive adds stock, negative removes.
     * Must be called inside a transaction (it locks the product row).
     */
    public function record(
        Product $product,
        StockMovementType $type,
        string|float|int $quantity,
        ?string $unitCost = null,
        ?Model $reference = null,
        ?string $reason = null,
        bool $allowNegative = false,
    ): StockMovement {
        $run = function () use ($product, $type, $quantity, $unitCost, $reference, $reason, $allowNegative) {
            /** @var Product $locked */
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);

            $qty = number_format((float) $quantity, 3, '.', '');
            if (bccomp($qty, '0', 3) === 0) {
                throw new DomainException(__('Quantity must not be zero.'));
            }

            $balance = bcadd($locked->current_stock, $qty, 3);
            if (bccomp($balance, '0', 3) < 0 && ! $allowNegative) {
                throw new DomainException(__('Insufficient stock for :product. Available: :qty :unit.', [
                    'product' => $locked->name, 'qty' => qty($locked->current_stock), 'unit' => $locked->unit,
                ]));
            }

            $movement = StockMovement::query()->create([
                'product_id' => $locked->id,
                'type' => $type,
                'quantity' => $qty,
                'balance_after' => $balance,
                'unit_cost' => $unitCost !== null ? Money::of($unitCost) : null,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'reason' => $reason,
                'user_id' => Auth::id(),
                'created_at' => now(),
            ]);

            $locked->current_stock = $balance;
            $locked->save();
            $product->current_stock = $balance;

            return $movement;
        };

        return DB::transactionLevel() > 0 ? $run() : DB::transaction($run);
    }

    /** Manual movement by an operator (consumption, adjustment, wastage) with mandatory reason. */
    public function manual(Product $product, StockMovementType $type, string|float $quantity, string $reason): StockMovement
    {
        if (! in_array($type, StockMovementType::manual(), true)) {
            throw new DomainException(__('This movement type cannot be recorded manually.'));
        }
        if (trim($reason) === '') {
            throw new DomainException(__('A reason is required for manual stock changes.'));
        }

        $signed = match ($type) {
            StockMovementType::Consumption, StockMovementType::Wastage => '-'.ltrim((string) $quantity, '-'),
            default => (string) $quantity,
        };

        return DB::transaction(function () use ($product, $type, $signed, $reason) {
            $movement = $this->record($product, $type, $signed, $product->purchase_cost, null, $reason);
            $this->audit->log('stock.manual', $product, [], ['type' => $type->value, 'quantity' => $signed, 'balance_after' => $movement->balance_after], $reason);

            return $movement;
        });
    }

    /** Set opening stock for a new product (creates an opening movement). */
    public function opening(Product $product, string|float $quantity): ?StockMovement
    {
        if (bccomp(number_format((float) $quantity, 3, '.', ''), '0', 3) === 0) {
            return null;
        }

        return $this->record($product, StockMovementType::Opening, $quantity, $product->purchase_cost, null, 'Opening stock');
    }

    /** Recompute the balance from movements; returns [stored, computed]. */
    public function verify(Product $product): array
    {
        $computed = number_format((float) StockMovement::query()->where('product_id', $product->id)->sum('quantity'), 3, '.', '');

        return [$product->current_stock, $computed];
    }
}
