# Performance Audit

## Evidence

The production Vite build succeeds: CSS is 144.56 kB (21.46 kB gzip) and the application JS entry is 0.05 kB (0.07 kB gzip). This is static analysis, not load testing; no production query timings, database size, cache telemetry, queue metrics, or browser waterfall was available.

## Findings

| Priority | Finding | Why / expected improvement |
| --- | --- | --- |
| P1 | `GenerateMonthlyInvoices` loads all active mappings then runs `exists()` once per mapping. | Replace with a period key plus a unique constraint and a set-based/idempotent command; removes N+1 work and duplicate-race exposure. |
| P1 | Every analytics/report render runs repeated aggregates; month trend loops 12 separate payment queries. | Use one grouped range query, cache bounded dashboard aggregates, and invalidate on payment change; lowers DB load and response variance. |
| P1 | Exports and several lists load all rows (`get()`), notably payment/analytics CSV. | Stream cursor/chunked exports, apply date filters, and queue large reports; prevents memory exhaustion. |
| P2 | Reporting loads Chart.js from a CDN while a local `public/assets/js/chart.min.js` exists; legacy assets coexist with Vite output. | Use one versioned build path; improves cache control, offline resilience, and request consistency. |
| P2 | Email/SMS/QR/PDF work runs synchronously. | Queue retries and backoff; prevents provider latency/outage from blocking web requests and scheduled commands. |
| P2 | Home catalogue and select lists fetch complete collections; no public pagination/filter application. | Paginate and apply validated filters; controls response size as inventory grows. |
| P3 | Images are stored without visible resize, optimization, variants, or CDN configuration. | Create bounded WebP/AVIF derivatives and cache headers; reduces bandwidth and layout delay. |

Existing eager loading is generally appropriate in reports, documents, tickets, and downloads. Missing indexes are detailed in [database.md](database.md). There is no demonstrated reason to introduce a repository/cache abstraction broadly; target the cited read paths first.
