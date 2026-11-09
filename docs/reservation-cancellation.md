# Unpaid initial reservations

Assigning a stall creates an inactive reservation and an initial invoice due seven calendar days later. The tenant may pay through that due date. Starting the next day in Asia/Manila, an unpaid reservation is eligible for automatic cancellation.

`stallm:cancel-expired-reservations` runs every five minutes through Laravel's scheduler. It cancels only overdue pending initial invoices linked to inactive, unterminated reservations. Paid invoices, active leases, monthly bills, and renewal invoices are excluded.

Cancellation voids the initial invoice, records the termination reason and time, and makes the stall available if no other assignment holds it. The tenant and admins receive in-app notifications; the tenant also receives an email. The approved application and tenant account remain as historical records. Existing unrelated unpaid balances remain owed.

Invoice and reservation rows are checked again under transaction locks. Repeated runs do not repeat cancellations or notifications. A payment confirmed before cancellation activates the reservation. A gateway confirmation received after cancellation is recorded and flagged for office review; it cannot reactivate the reservation or take a reassigned stall. Staff must review such payments for refund or other resolution.

For local development, keep a scheduler process running alongside the web server:

```sh
php artisan schedule:work
```

For deployment, configure the host's task scheduler to run `php artisan schedule:run` every minute from the application directory. Running the web server alone does not execute scheduled tasks. Verify registration with `php artisan schedule:list`.

To run cancellation immediately, use `php artisan stallm:cancel-expired-reservations`. This changes eligible reservations and sends notifications.

Regression tests: `php artisan test --filter=CancelExpiredReservationsTest`.

Verification on October 10, 2026: 42 targeted PHP tests passed with 303 assertions across cancellation, lease reassignment, overdue handling, full-term billing, and renewal UI. The Chromium assignment-to-cash-payment journey passed, including database checks, activation, tenant navigation, and refresh. PHP syntax and whitespace checks passed. The scheduler lists the cancellation job every five minutes with overlap prevention. SQLite tests cover payment-first and cancellation-first ordering; they do not simulate simultaneous transactions on MySQL. The complete browser suite was not rerun.
