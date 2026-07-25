# Architecture

## Structure

Laravel routes are intentionally thin: page routes return a Blade shell and the page mounts a Livewire component. `app/Models` holds Eloquent relationships; `app/Services` wraps providers and document generation; `app/Console/Commands` contains recurring work. There are no policies, form-request classes, repositories, jobs, events, observers, API resources, or custom exception handling in the audited Laravel tree.

```text
Browser -> web routes/middleware -> Blade layout -> Livewire action
                                              -> Eloquent models -> MySQL
                                              -> Services -> PayMongo/Mailjet/Semaphore/QR API
Scheduler -> artisan commands -> models/services -> notifications and external APIs
```

## Flows

### Authentication and authorization

Guest login/register uses `Auth::attempt` or `User::create`; Google/Facebook OAuth can create or link an account. `CheckRole` restricts `/user` to `user` and `/admin` to `admin,staff`. `RequiresTwoFactor` gates admin/staff routes only when both `ENABLE_2FA` and a user flag are enabled. Authorization is implemented inline with role checks rather than policies; file and receipt downloads contain direct ownership checks.

### Reservation / booking

The public catalogue queries available stalls. A visitor submits `StallApplication`; admins review its status. This is an application workflow, not an atomic reservation: selecting a preferred stall neither reserves it nor prevents concurrent applications.

### Lease, transaction, and reporting

Staff create a `StallMapping`, mark its stall occupied, and may attach a contract. Monthly scheduling creates pending `Payment` rows. Tenants select invoices, receive a PayMongo checkout URL, and a webhook marks matching session IDs paid. Utility readings create additional pending payments. Analytics/reports aggregate stall and payment rows, generate CSV/PDF, and render Chart.js data.

## Integration boundaries and bottlenecks

PayMongo, Mailjet, Semaphore, and a public QR-code endpoint are synchronous HTTP dependencies. Dompdf permits remote resources. Notification, email, SMS, invoice generation, and reports run in request/command processes instead of queued jobs. Monthly trend rendering makes 12 aggregate queries; invoice generation performs one existence query per mapping. Neither caches nor queues are used despite a database queue configuration.

## Architecture decisions needed

Treat payment confirmation, lease allocation, files, audit logging, and authorization as domain boundaries. Introduce policies and small action/service classes only where they centralize a cross-cutting invariant; avoid a repository layer until there is a demonstrated need to swap or compose persistence.
