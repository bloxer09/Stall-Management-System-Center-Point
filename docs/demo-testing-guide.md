# Center Point Stall Management System — Testing & Navigation Guide

How to start the system on a laptop, sign in as each kind of user, and walk through every
feature. Use it to rehearse the defense demo and to test the system before release.

All accounts and records below are **demo data** created by the seeders. Never use them on a
public server.

---

## 1. Start the system

Open a terminal in the project folder and run these once per machine (skip any already done):

```bash
composer install
npm install
npm run build
php artisan migrate
php artisan db:seed --class=DemoSeeder
```

`DemoSeeder` is safe to run again; it updates the demo records without deleting anything.

Then start the app and the local email catcher (two terminals):

```bash
php artisan serve
```

```bash
mailpit
```

| What | Where |
|---|---|
| The system | http://127.0.0.1:8000 |
| Emails it sends (password links, reminders, notices) | http://localhost:8025 (Mailpit) |
| Text messages it sends (2FA codes, reminders) | http://127.0.0.1:8000/sms-catcher |

> No real email or SMS leaves the laptop: emails land in Mailpit and texts land in the SMS catcher.

---

## 2. Demo accounts

| Role | Email | Password | Name |
|---|---|---|---|
| Administrator | `admin@stallm.com` | `password` | System Administrator |
| Tenant 1 | `tenant1@demo.test` | `password` | Juan Dela Cruz — stall G-01, lease ends in ~45 days |
| Tenant 2 | `tenant2@demo.test` | `password` | Maria Clara Reyes — stall G-02, renewal requested |
| Tenant 3 | `tenant3@demo.test` | `password` | Jose Rizal Santos — stall G-03 |
| Tenant 4 | `tenant4@demo.test` | `password` | Andres Bonifacio Lim — stall G-04 |

Other demo records worth knowing:

- **Stall applications:** Carmen Villanueva (F-02, pending), Ramon Bautista (S-01, pending),
  Liza Mercado (G-06, rejected, `liza.mercado@example.com`).
- **Stall statuses:** G-01 to G-04 occupied, G-05 reserved, S-03 under maintenance, the rest available.
  G-13 and G-14 are not yet placed on the floor map (to demo the floor map editor).
- **Floors:** Ground Floor (G-), Second Floor (S-), Food Court (F-).

---

## 3. Signing in and out

### Administrator
1. Go to http://127.0.0.1:8000 and click **Sign in** (top right).
2. Enter the admin email and password, then **Sign in**. You land on `/admin/dashboard`.
3. **If 2FA is turned on** for the admin, a code screen appears. Open
   http://127.0.0.1:8000/sms-catcher in another tab, copy the 6-digit code, and enter it.
   Codes expire after 10 minutes; **Resend** is available after 60 seconds.

### Tenant
1. **Sign in** with a tenant account. You land on `/user/dashboard`.
   Tenants never get a 2FA prompt (2FA is admin-only).

### Signing out
Click your name or avatar (top right) → **Sign out**.

### Things to try
| Test | Expected result |
|---|---|
| Wrong password 5 times | Sign-in is blocked for a short time with a countdown message |
| Tenant opens `/admin/dashboard` | Access denied — tenants can't reach admin pages |
| Signed-out user opens `/user/dashboard` | Sent back to sign in |
| **Forgot password** on the sign-in box | A code is sent to the email (check Mailpit; on the local machine it is also shown on screen) |
| **Create account** on the sign-in box | Registers a tenant account; the password must meet the strength rules |

### Turning admin 2FA on or off
- **On:** signed in as admin → avatar → **Profile Settings** → add a phone number → enable two-factor
  authentication → enter the code from the SMS catcher.
- **Lost the phone:** run `php artisan stallm:2fa-reset admin@stallm.com` to switch it off.
- **Whole system:** `ENABLE_2FA=false` in `.env` turns off the 2FA feature for everyone.

---

## 4. Visitor (not signed in) walkthrough

| Page | What to do | Expected |
|---|---|---|
| Home `/` | Scroll through | Overview of Center Point and links to stalls |
| Browse stalls `/stalls` | Switch floors, click a green (available) stall | Stall details with **Apply** |
| Apply | Fill in the form and submit | A reference number like `APP-2026-000123` is shown; a confirmation email appears in Mailpit |
| Occupied stall | Click a red stall → **Notify me** | You join the waitlist and get an email when it frees up |
| Application status `/application-status` | Enter Liza Mercado's reference (find it on the admin **Applications** page) and `liza.mercado@example.com` | Shows *Rejected* with the reason |
| Terms / Privacy | `/terms-and-conditions`, `/privacy-policy` | Text set in admin **Settings** |

---

## 5. Administrator walkthrough

Sign in as admin. Every page is on the left sidebar. The **search box** at the top finds
stalls, tenants, invoices and applications (try typing `G-0`).

| Page | What to show / test |
|---|---|
| **Dashboard** | Counts that need attention: pending applications, renewal requests, overdue invoices, unassigned tickets; revenue and occupancy charts |
| **Applications** | Open Carmen Villanueva's application → **Approve**. A tenant account is created and a "set your password" email appears in Mailpit. Reject one with a reason |
| **Stalls** | Add, edit, filter stalls; change status (available / reserved / maintenance) |
| **Floors** | Add or edit a floor and its grid size |
| **Floor map** (grid editor) | Drag G-13 or G-14 onto an empty cell and save |
| **Leases** (mappings) | Assign a stall to a tenant (this issues the first invoice); approve Maria Clara Reyes's **renewal request**; **Terminate** a lease with a reason |
| **Tenants** | Tenant accounts; open a **Statement of account** and download it as PDF |
| **Payments** | Invoices and payments; **Record Cash Payment** for a pending invoice; **Export Excel** |
| **Calendar** | Due dates, lease end dates and renewal requests by month; click an event to jump to it |
| **Announcements** | Post an announcement; it shows on tenant dashboards |
| **Tickets** | Open a ticket thread, reply, assign, close |
| **Reports / Analytics** | Revenue, occupancy and collection reports |
| **Audit logs** | Every important action (approvals, payments, terminations, backups) with who and when |
| **Settings** | Terms, privacy policy, contact details; **Backups**: **Create backup**, then download it |
| **Profile Settings** (avatar menu) | Name, phone, password, 2FA |

---

## 6. Tenant walkthrough

Sign in as `tenant1@demo.test` (Juan Dela Cruz).

| Page | What to show / test |
|---|---|
| **Dashboard** | Balance due, next due date, lease countdown, announcements |
| **My Leases** | Lease details for G-01; **Request renewal** (the lease ends in ~45 days); upload a stall photo |
| **Payments** (invoices) | Tick invoices → **Pay Selected** (or **Pay with PayMongo** on one invoice) opens the PayMongo checkout page; download the **receipt PDF** for a paid invoice |
| **Tickets** | Open a ticket with an attachment; reply in the thread (refreshes every 3 seconds) |
| **Documents** | Lease contract and other documents to download |
| **Notifications** (bell) | Reminders, announcements, ticket replies |

**Receipt check:** open a paid invoice's receipt PDF and scan the QR code (or open its link) —
the public `/verify-receipt/...` page confirms the receipt is genuine.

> **Online payment on localhost:** PayMongo confirms payments by calling the system's webhook,
> which can't reach a laptop. On the demo machine the checkout page opens, but the invoice stays
> unpaid. To show a paid invoice, record a cash payment as admin (**Payments → Record Cash
> Payment**), then show the receipt from the tenant side.

---

## 7. End-to-end demo scenario (about 10 minutes)

1. **Visitor:** browse `/stalls`, apply for an available stall, note the reference number.
2. **Visitor:** check `/application-status` with the reference and email → *Pending*.
3. **Admin:** **Applications** → approve it.
4. **Mailpit:** open the "set your password" email → follow the link → set a password.
5. **Admin:** **Leases** → assign the stall to the new tenant → first invoice is issued.
6. **Admin:** **Payments** → **Record Cash Payment** for that invoice.
7. **New tenant:** sign in → dashboard shows the active lease → download the receipt → scan the QR.
8. **Admin:** **Audit logs** shows each step above.

---

## 8. Running scheduled jobs by hand

On the real server these run automatically. To show them in the demo, run them directly:

| Command | What it does | Normally runs |
|---|---|---|
| `php artisan billing:generate-invoices` | Monthly invoices for active leases (due on the 6th) | 1st of the month, 00:00 |
| `php artisan stallm:notify-expiring-leases` | Lease-expiry email/SMS to tenants, in-app notice to admins | Daily, 07:00 |
| `php artisan stallm:send-reminders` | Payment reminders | Daily, 08:00 |
| `php artisan stallm:flag-overdue` | Marks unpaid past-due invoices overdue | Daily, 08:30 |
| `php artisan stallm:backup` | Encrypted database backup | Daily, 02:00 |

Check the results in Mailpit, the SMS catcher, and the admin dashboard.

---

## 9. Automated tests

```bash
php artisan test
```

All tests should pass. They use a temporary in-memory database, so they never touch the demo data.

---

## 10. Troubleshooting

| Problem | Fix |
|---|---|
| Pages look partly unstyled | `npm run build`, then refresh |
| "Table not found" or similar database error | `php artisan migrate` |
| No demo data | `php artisan db:seed --class=DemoSeeder` |
| Old settings or pages still showing | `php artisan optimize:clear` |
| Emails not appearing | Make sure `mailpit` is running and `.env` has `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025` |
| Admin 2FA code never arrives | Check http://127.0.0.1:8000/sms-catcher; or `php artisan stallm:2fa-reset admin@stallm.com` |
| Sign-in blocked after many attempts | Wait for the countdown, or `php artisan cache:clear` |
| A page breaks after a security change | Set `CSP_MODE=off` in `.env`, then `php artisan config:clear` |
