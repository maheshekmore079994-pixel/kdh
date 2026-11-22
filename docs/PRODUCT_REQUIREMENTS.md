# Product Requirements

Status legend: ☐ pending · ◐ in progress · ☑ done (see DEVELOPMENT_PLAN.md for the live checklist).

## 1. Dashboard
- Today's sales (invoiced), receipts by method, expenses, estimated profit, jobs received/pending/completed/
  delivered, pending customer dues, low-stock count, recent transactions.
- Date filter (today / this week / this month / custom). Quick actions: New bill, New job, Add expense.
- **Acceptance:** numbers match the corresponding reports for the same range; cancelled items excluded.

## 2. POS & Billing
- Add multiple services/products to one invoice; quantity, unit, editable price (prefilled from catalogue),
  line discount, invoice discount, optional tax (settings), payment received now, partial payment, balance due.
- Unique invoice number (`settings.invoice_prefix` + yearly sequence). A4 invoice and 80 mm receipt print;
  PDF download; reprint; invoice and payment history.
- **Acceptance:** totals rounded to 2 dp; stock consumed for product lines; balance due requires a customer;
  cancel reverses stock and excludes from reports.

## 3. Jobs
- Job number, customer, service, description, quantity, price, assigned operator, received date, expected
  delivery, notes, status (pending/processing/completed/delivered/cancelled), status history.
- Payment status separate (from linked invoice). Advance + balance. Search by customer, mobile, job number, date.
  Printable job receipt.
- **Acceptance:** status transitions validated; history recorded with user and time.

## 4. Customers
- Name, mobile (required, 10-digit Indian), optional email/address/notes. History: jobs, invoices, payments, dues.
  Duplicate detection on mobile. Search by name/mobile.

## 5. Expenses
- Date, category (configurable), description, amount, payment method, vendor, optional attachment.
  History + reports. Soft delete with reason.

## 6. Purchases
- Supplier, supplier invoice no., date, items (product, qty, unit cost), total, payment status, partial
  supplier payments, supplier dues. Posting updates stock via movements.

## 7. Inventory
- Products with SKU, category, unit, opening/current stock, reorder level, purchase cost, selling price.
  Movements: opening, purchase, sale, consumption, adjustment, wastage, reversals. Low-stock alerts. History.

## 8. Printing cost calculator
- Admin-defined cost profiles (paper cost/sheet, ink or toner cost/page, maintenance allowance/page, other).
  Calculator shows estimated cost, chosen charge and margin. Clearly labelled estimate; never writes transactions.
  No default rates are seeded.

## 9. Accounts & reports
- Daily cashbook, sales register, expense register, payment register, customer dues, supplier dues, purchase
  register, inventory valuation, profit & loss estimate. Ranges: day/week/month/custom. CSV, XLSX, PDF export.
- Receipts split by cash/UPI/bank/other. Unpaid invoices never counted as received cash.
- **Profit definition:** Sales (non-cancelled, net of tax) − COGS (product lines at snapshot cost) − Expenses.
  Excludes: service consumable costs, purchases not yet consumed, supplier dues, owner drawings, depreciation.

## 10. Settings & users
- Roles: admin, operator. Operator permissions per module (billing, jobs, customers, expenses, purchases,
  inventory, reports, website, settings, users). Secure login, reset, 2FA, password confirm, audit log.
- Business details, logo, invoice prefix, receipt layout, catalogue, price list, expense categories, tax, backups.

## 11. Public website
- Home, About, Services, Contact, Privacy, Terms. Service categories, contact details, opening hours and
  location from settings (blank sections hidden when unset), WhatsApp link, enquiry form (rate-limited),
  SEO meta, sitemap.xml, responsive. Admin-managed content. No fabricated reviews/awards/guarantees.

## 12. Documents
- Attachments on jobs, customers, expenses, purchases. Private disk, validated types/size, authorised
  download only, retention policy (days, configurable) with a prune command.
