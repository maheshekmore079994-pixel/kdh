# UI rules
- Flux UI components first (`flux:button`, `flux:input`, `flux:table`, `flux:badge`, `flux:modal`…), Tailwind
  for layout. Brand accent: logo gold `#D59A2F` on zinc. Minimal, professional, no decorative clutter.
- Sidebar groups: Dashboard · Billing (POS, Invoices, Payments) · Jobs · Customers · Inventory (Products,
  Stock, Purchases, Suppliers) · Expenses · Reports · Website (Content, Enquiries) · Settings.
- Every page: `<x-layouts::app :title>`, a `flux:heading`, primary action top-right, search/filter row,
  table with pagination, status badges, helpful empty state.
- Financial/destructive actions (cancel invoice, reverse payment, delete) need a confirmation modal with reason.
- Money via `money($amount)` → `₹1,23,456.00`; dates `d M Y`; Indian digit grouping.
- Forms: inline validation errors, autofocus first field, Enter submits. POS and job entry minimise clicks.
- Printable views in `resources/views/print/*`: plain HTML + inline CSS, A4 and 80 mm receipt variants.
- Never ship a button that does nothing. Pending features are omitted or clearly marked "Not yet available".
- Light theme by default; dark supported through Flux appearance.
