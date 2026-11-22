# Database Schema

Conventions: money `decimal(12,2)`, qty `decimal(12,3)`, all tables have `id`, `created_at`, `updated_at`.
`SD` = soft deletes. `FK→` = foreign key with restrict on delete unless noted.

## Auth & settings
- **users**: name, email (unique), password, role (`admin|operator`), permissions (json array of Permission values,
  null for admin), is_active (bool), phone (nullable), two_factor_* (Fortify), remember_token.
- **settings**: group, key (unique), value (json/text). Seeded keys only; values blank unless known.
- **number_sequences**: key (unique: invoice, job, payment, purchase, expense), prefix, padding, next_number,
  reset_yearly (bool), year.
- **audit_logs**: user_id (nullable FK→users), action, auditable_type, auditable_id, old_values (json),
  new_values (json), reason (nullable), ip, created_at. Index (auditable_type, auditable_id).

## Catalogue
- **service_categories**: name, slug, description, sort_order, is_active, show_on_website.
- **services**: service_category_id FK, name, code (unique nullable), unit (default "pcs"), default_price
  (nullable money), description, is_active, show_on_website, sort_order.
- **service_variants**: service_id FK (cascade), name (e.g. "A4 B/W single side"), unit_price (money),
  min_quantity (qty, default 1), sort_order, is_active. Unique (service_id, name).
- **units**: name, abbreviation, is_active.
- **payment_methods**: name, code (unique), type (`cash|upi|bank|other`), is_active, sort_order.

## Customers & suppliers
- **customers** SD: name, mobile (index), email, address, notes, created_by FK→users. Index mobile.
- **suppliers** SD: name, phone, email, address, gstin (nullable), notes.

## Inventory
- **product_categories**: name, sort_order, is_active.
- **products** SD: product_category_id FK, sku (unique), name, unit, opening_stock qty, current_stock qty,
  reorder_level qty, purchase_cost money, selling_price money (nullable), is_sellable, is_active, description.
- **stock_movements**: product_id FK, type (`opening|purchase|sale|consumption|adjustment|wastage|
  purchase_reversal|sale_reversal`), quantity (signed qty), balance_after qty, unit_cost (nullable money),
  reference_type/reference_id (nullable morph), reason, user_id FK, created_at. Index (product_id, created_at).

## Billing
- **invoices** SD: invoice_number (unique), customer_id (nullable FK), job_id (nullable FK), invoice_date (date),
  subtotal, discount_amount, tax_rate (decimal 5,2 nullable), tax_amount, total, paid_amount, balance_due,
  status (`unpaid|partial|paid|cancelled`), notes, created_by FK, cancelled_at, cancelled_by, cancel_reason.
  Index invoice_date, status, customer_id.
- **invoice_items**: invoice_id FK (cascade), item_type (`service|product`), service_id, service_variant_id,
  product_id (nullable FKs), description, quantity qty, unit, unit_price money, discount_amount money,
  line_total money, cost_price money (nullable snapshot for products).
- **payments** SD: payment_number (unique), invoice_id FK, customer_id (nullable FK), amount money,
  payment_method_id FK, paid_at (datetime), reference (nullable, e.g. UPI ref), notes, received_by FK→users,
  reversed_at, reversed_by, reversal_reason. Index paid_at.

## Jobs
- **service_jobs** SD (model `ServiceJob`; the name `jobs` is used by Laravel's queue): job_number (unique), customer_id FK, service_id (nullable FK), service_variant_id (nullable),
  title, description, quantity qty, unit_price money, total_amount money, assigned_to (nullable FK→users),
  received_at (datetime), expected_at (nullable datetime), delivered_at (nullable), status
  (`pending|processing|completed|delivered|cancelled`), notes, invoice_id (nullable FK), created_by FK.
  Index status, received_at, customer_id.
- **job_status_histories**: service_job_id FK (cascade), from_status, to_status, user_id FK, note, created_at.

## Expenses & purchases
- **expense_categories**: name, is_active, sort_order.
- **expenses** SD: expense_number (unique), expense_date (date), expense_category_id FK, description,
  amount money, payment_method_id FK, vendor, notes, created_by FK, deleted_reason. Index expense_date.
- **purchases** SD: purchase_number (unique), supplier_id FK, supplier_invoice_no, purchase_date (date),
  subtotal, other_charges, total, paid_amount, balance_due, status (`draft|posted|cancelled`),
  payment_status (`unpaid|partial|paid`), notes, posted_at, created_by FK, cancelled_at/by/reason.
- **purchase_items**: purchase_id FK (cascade), product_id FK, quantity qty, unit_cost money, line_total money.
- **supplier_payments** SD: supplier_id FK, purchase_id (nullable FK), amount money, payment_method_id FK,
  paid_at, reference, notes, paid_by FK→users, reversed_at/by/reason.

## Documents, website, calculator
- **documents** SD: documentable_type/id (morph), disk, path, original_name, mime_type, size (unsigned int),
  uploaded_by FK, expires_at (nullable). Index morph.
- **enquiries**: name, phone, email, service_interest, message, status (`new|contacted|closed`), ip,
  handled_by (nullable FK), handled_at, notes.
- **print_cost_profiles**: name, paper_cost_per_sheet, ink_cost_per_page, maintenance_per_page,
  other_cost_per_page (all money nullable), notes, is_active.

## Relationships (summary)
customer 1─* invoices 1─* invoice_items; invoice 1─* payments; customer 1─* jobs; job 0..1 invoice;
supplier 1─* purchases 1─* purchase_items; product 1─* stock_movements; purchases/invoices → stock_movements
(morph reference); users → everything via created_by/received_by/user_id.

## Integrity rules
- Invoice/purchase totals recalculated by services only. Status columns derived.
- Stock: `products.current_stock` == Σ stock_movements.quantity (verified by `stock:verify` command).
- Reversals never delete; cancelled/reversed rows carry who/when/why.

## Migrations
`php artisan migrate` (SQLite dev, MySQL prod). Rollback: `php artisan migrate:rollback --step=1`.
Fresh dev DB: `php artisan migrate:fresh --seed` (seeds only configuration, never transactions).
