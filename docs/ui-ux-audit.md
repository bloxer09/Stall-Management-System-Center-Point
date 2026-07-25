# UI/UX and Accessibility Audit

## Evidence and strengths

Static review covered the application/guest layouts, public catalogue and reservation, tenant portal, all admin page shells, and Livewire component templates. The interface has responsive Tailwind grids, form validation text, empty states in several lists, dark-theme persistence, status badges, pagination in management lists, and a coherent base palette.

## Findings

| Priority | Finding | Why / expected improvement |
| --- | --- | --- |
| P1 | The current product uses three design languages: DaisyUI/base tokens in the primary app, legacy gray/indigo component styles, and a separate public reservation form. | Define semantic tokens and a small component set for page header, action bar, table, form, modal, badge, empty state, and feedback; creates a credible product surface. |
| P1 | Rounded `2xl`/`3xl` cards and medium shadows are repeated on nearly every container, including density-heavy operational pages. | Reserve elevation/radius for hierarchy, use quieter surfaces and spacing tiers; improves scanability and avoids a template-like appearance. |
| P1 | Staff are authorized for `/admin/*` but the main navigation only recognizes `isAdmin()`. | Render navigation from capabilities and show role context; prevents a confusing hidden-feature workflow. |
| P2 | Loading and failure feedback is inconsistent; static scan found little/no `wire:loading`, and provider failures are often suppressed. | Add disabled/busy states, skeletons for dashboard/list loads, recoverable error notices, and retry guidance. |
| P2 | Public location filter links are visually active but the home query ignores `location_id`; public catalogue has no pagination/search. | Make controls match behavior and expose accessible filter/result status. |
| P2 | Icon-only controls, custom dialogs, charts, and chat polling need keyboard and assistive-technology verification. | Add accessible names, focus restoration/traps, chart data tables/summaries, and reduced-motion testing. |
| P2 | Charts are visually rendered through a third-party script with no accessible textual equivalent. | Provide key metrics/table data adjacent to charts and do not make canvas the sole source of information. |

## Design-system direction

Keep the existing indigo/emerald operational character, but standardize a 4/8 px spacing scale, 8–12 px routine radius, restrained elevation, one text hierarchy, and semantic `success/warning/error/info` statuses. Use tables for administrative records, a clear action toolbar, filter chips only when stateful, and persistent mobile-safe primary actions. The recommendations are static-code findings; keyboard, screen-reader, contrast, and multi-device visual testing remain required before a final accessibility score.
