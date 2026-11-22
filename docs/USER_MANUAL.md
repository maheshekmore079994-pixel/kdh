# User Manual — KHADAVLI DIGITAL HUB Business System

For shop operators and the administrator. The app runs in a web browser (or the Windows desktop app) and
needs an internet connection to the shop's server.

## 1. Logging in
1. Open the app address given by your administrator (for example `https://your-domain/login`).
2. Enter your email and password. If two-factor authentication is enabled on your account, enter the 6-digit
   code from your authenticator app.
3. Use **My account** (bottom of the sidebar) to change your password, enable 2FA or switch light/dark mode.
4. Forgot your password? Use **Forgot your password?** on the login page (requires email to be configured) or
   ask the administrator to set a new one in **Users**.

## 2. Dashboard
Shows today's sales (bills issued), receipts (money actually received, split by Cash / UPI / Bank),
expenses, estimated profit, job counts, customer dues and low-stock products. Change the period with the
selector at the top right. Quick buttons: **New bill**, **New job**, **Add expense**, **Add customer**.

## 3. Making a bill (POS)
1. **Billing → New bill (POS)**.
2. Tap services or products on the left. Services with price variants (A4 / A3, single / double side…) are
   listed per variant. Items without a configured price open with an empty price box: type the price.
3. Adjust quantity, price and discount per line. Add a bill-level discount or notes if needed.
4. Customer is optional for walk-in bills that are paid in full. To leave a balance due, pick or add a
   customer (type name or mobile; "Add new customer" appears if not found).
5. Enter the amount received now and the payment method (Cash, UPI, Bank transfer, Other). **Full** fills
   the total. Enter a UPI reference if you have one.
6. **Save & print**. The receipt opens for printing automatically. Use the **Print** menu on the invoice page
   for A4 or PDF.

Rules: tax is added only if the administrator enabled it in **Billing & tax**. Product lines reduce stock.

## 4. Invoices and payments
- **Billing → Invoices** lists all bills with status (Unpaid, Partially paid, Paid, Cancelled). Filter by
  status, date or search by number, customer or mobile.
- Open an invoice to **Record payment** (full or partial), **Reverse** a wrong payment (reason required) or
  **Cancel invoice** (reason required; all payments must be reversed first; stock items return to inventory).
- **Billing → Payments** shows every payment received with the method and who received it. Reversed
  payments are hidden by default and never counted in totals.

## 5. Jobs (work to deliver later)
1. **Jobs → New job**: pick the customer (required), service and variant, describe the work, quantity and
   price, expected delivery date and who will do it.
2. From the job page: **Bill this job** creates the invoice; **Record payment** takes an advance.
3. Move the job through **Processing → Completed → Delivered**. Each change is recorded with time and user.
   Cancelling asks for a reason. Delivering with a balance due shows a warning.
4. **Job receipt** prints an A5 slip for the customer with the job number and balance.
5. Attach scanned documents under **Documents** on the job page (PDF/JPG/PNG/DOCX/XLSX).

## 6. Customers
**Customers** lists everyone with their outstanding due. Open a customer to see bills, jobs, payments and
documents. Mobile numbers must be unique; the form warns if a number already exists.
Never store passwords, OTPs, PINs or bank details in notes.

## 7. Expenses
**Expenses → Add expense**: date, category, description, amount, how it was paid, vendor. Attach the bill via
the paper-clip icon. Deleting asks for a reason and keeps the record in the audit log.

## 8. Inventory and purchases
- **Products**: add paper, toner, ink, stationery with SKU, unit, opening stock, reorder level, purchase cost
  and selling price. Products marked "can be sold" appear in the POS.
- Open a product to see every stock movement. **Record stock change** for consumption, wastage or a count
  correction (reason required). Stock is never changed silently.
- **Purchases → New purchase**: choose the supplier, add products with quantity and unit cost, optionally pay
  now. Posting adds the items to stock and updates the product cost. Supplier balances are tracked; pay
  later from the purchase page. Cancelling reverses the stock (payments must be reversed first).
- **Suppliers**: contact details and dues.

## 9. Reports
**Accounts → Reports**: daily cashbook, sales, payments, expenses, purchases, customer dues, supplier dues,
inventory valuation and profit & loss estimate. Pick a period and export to CSV, Excel or PDF.
Profit is an *estimate*: sales net of tax − product cost − expenses. It does not include consumables used
for services, supplier dues, owner drawings or depreciation.

## 10. Print cost calculator
**Accounts → Print cost calculator**: the administrator creates cost profiles (paper per sheet, ink/toner,
maintenance, other per page). Enter pages, copies, sides and an intended charge to see cost and margin.
It is a calculator only; it never records anything.

## 11. Website and enquiries
The public website shows the services, contact details, opening hours and map link from **Business details**
and the text from **Website content**. Visitor messages arrive under **Website → Enquiries**; open one to
call or WhatsApp the person and mark it contacted or closed.

## 12. Administrator settings
- **Business details**: name, phone, email, address, GSTIN, map link, opening hours, logo.
- **Billing & tax**: number prefixes, receipt width (58/80 mm), default print format, footer, terms, tax.
- **Services & prices**: categories, services and price variants. All prices are yours; nothing is preset.
- **Lists & categories**: payment methods, expense categories, product categories, units.
- **Documents & backups**: attachment size limit, retention period, run or view database backups.
- **Audit log**: who changed what and when.
- **Users**: create operators, tick the modules they may use, deactivate accounts, reset passwords.

## 13. Daily routine (suggested)
Morning: check the dashboard and pending jobs. During the day: bill every sale, record every expense and
payment immediately. Evening: open **Reports → Daily cashbook**, compare cash in the drawer and UPI app with
the receipts shown, then check backups ran (Documents & backups).

## 14. Internet and data
The app needs an internet connection; there is no offline mode. All data stays on the shop's server; only
staff with a login can see it. Uploaded documents are private and deleted automatically after the retention
period if one is set.
