# Database rules
- Money: `decimal(12, 2)`. Quantities: `decimal(12, 3)`. Percentages: `decimal(5, 2)`.
- Every table: `id`, `timestamps()`. Financial tables add `softDeletes()` and `created_by` FK to users.
- Foreign keys: `foreignId()->constrained()` with explicit `restrictOnDelete()` for financial references.
- Unique: `invoice_number`, `job_number`, `payment_number`, `purchase_number`, `products.sku`, `users.email`.
- Indexes on: customer mobile, dates (invoice_date, expense_date, purchase_date, paid_at), status columns,
  polymorphic (type, id) pairs.
- Migrations must run on SQLite (tests/dev) and MySQL 8 (prod). No MySQL-only SQL.
- Statuses are string columns validated by PHP enums (never DB enums).
- Stock balance is denormalised in `products.current_stock`, but every change writes a `stock_movements`
  row with signed `quantity` and `balance_after`. `StockService::recalculate()` must reproduce the balance.
- Invoice totals are stored and recomputed only by `InvoiceService::recalculate()`.
- Deleting = soft delete + reason + audit row. Cancelling an invoice reverses stock and sets status cancelled.
