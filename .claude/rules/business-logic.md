# Business logic rules

## Invoices & payments
- line_total = round(qty × unit_price − line_discount, 2). subtotal = Σ line_total.
- total = subtotal − invoice_discount + tax_amount, where tax_amount = round((subtotal − discount) × tax_rate / 100, 2)
  only when tax is enabled in settings; `tax_rate` is snapshotted on the invoice.
- Status derived from payments: `unpaid` (paid = 0), `partial`, `paid` (paid ≥ total), `cancelled`.
- A payment belongs to an invoice (and thus the customer). Reversal sets `reversed_at/by/reason`; reversed
  payments are excluded from all receipt totals and the invoice is recalculated.
- Customer dues = Σ balance_due of non-cancelled invoices. Walk-in invoices may have no customer, but an
  invoice with a balance due requires a customer.

## Jobs
- Statuses: pending → processing → completed → delivered; cancelled allowed from any non-delivered state.
  Every change writes `job_status_histories`. Delivering with balance due warns but does not block.
- One invoice per job (optional). Advance payments are payments on that invoice.

## Stock
- Product lines on an invoice consume stock on issue; cancelling the invoice restores it.
- Posting a purchase adds stock; cancelling reverses it. Adjustment and wastage require a reason.
- Low stock = current_stock ≤ reorder_level.

## Expenses & purchases
- Expense totals exclude soft-deleted rows. Supplier dues = Σ balance_due of non-cancelled purchases.

## Profit (always labelled "estimate")
- Estimated profit = Sales (non-cancelled invoices, net of tax) − COGS − Expenses.
  COGS = Σ product lines qty × `invoice_items.cost_price` (snapshot of purchase_cost at sale time).
  Service consumable costs are unknown and excluded. Documented in PRODUCT_REQUIREMENTS.md.
- Cash position = receipts − expenses − supplier payments, grouped by payment method.
