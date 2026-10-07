# Documentation Update & Defense Deck — Design

Date: 2026-10-08 · Defense: Saturday 2026-10-10

## Goal

Bring the capstone manuscript up to date with the system as built, restructured to the
reference manuscript's layout, and produce a defense slide deck from the school template.

## Inputs → outputs

| Input | Role |
|---|---|
| `docs/current.docx` | Source manuscript — its title page, fonts and custom styles (`HeadingCustom`, `BodyTextCustom`, `Normal`) are kept |
| `docs/base_docu.docx` | Structure reference only (section order, back matter) — no text or styling copied |
| `docs/ppt_template.pptx` | Slide design (13.33 × 7.5 in; title slide + content slide designs) |
| The running system + `docs/diagrams/*.drawio` | Facts, screenshots, figures |

Outputs (originals untouched):
- `docs/updated.docx` — updated manuscript
- `docs/presentation.pptx` — defense deck
- `docs/assets/` — screenshots and diagram PNGs used by both
- `docs/diagrams/use-case.drawio`, `docs/diagrams/content-diagram.drawio` — new figures

## Approach

Scripted and repeatable (scripts in `docs/tools/`, rerun if the system changes):
1. **Capture** — Brave headless driven over the Chrome DevTools Protocol by a Node script
   (no new downloads) against a throwaway SQLite copy of the system seeded with DemoSeeder,
   served by `php artisan serve` on port 8001 with `CSP_MODE=off`. Logs in with the seeded demo
   accounts, screenshots each screen at 1440×900, and renders `.drawio` pages to PNG through
   the diagrams.net viewer.
2. **Manuscript** — python-docx edits a copy of `current.docx`; new paragraphs reuse the
   document's own styles; figures inserted with numbered captions.
3. **Deck** — python-pptx fills the template, cloning its content-slide design for extra slides.
4. **Check** — PowerPoint and Word (COM via PowerShell) export slides to PNG and the manuscript
   to PDF; every slide and the new/changed pages are inspected; Word refreshes the TOC fields.

## Manuscript changes

- **Global:** remove utility billing and the Staff role everywhere (objectives, modules,
  significance, scope, definition of terms, requirements, synthesis). Roles are Administrator
  and Tenant (plus public visitors).
- **Chapter I:** objectives, purpose, significance, scope/limitations and Definition of Terms
  rewritten for the current feature set; software list with versions from `composer.json` /
  `package.json`.
- **Chapter II:** unchanged except the synthesis wording above.
- **Chapter III:** functional and non-functional requirements rebuilt from the system; ERD
  image replaces the old database figure; Modules Described rewritten; architecture and
  hardware/software tables kept and corrected.
- **Chapter IV:** Coding and Debugging gains a subsection per feature (lease termination,
  announcements, application status, tenant accounts, statements, waitlist, calendar, global
  search, backups) and one for security hardening; Testing reports the automated suite
  (340 tests, grouped by area); Implementation gains the deploy checklist.
- **Chapter V → "Results and Discussions":** Functionality and Testing Results; Results of
  System Implementation; Implementation Flow; Problems Encountered During Implementation;
  figures — System Flowchart for Admin, System Flowchart for Tenant, Use Case Diagram, Content
  Diagram, Entity Relationship Diagram; Conclusion; Recommendations.
- **Back matter (reference order):** Transmittal Letter (placeholder), Photo Documentation
  (placeholder), User GUI Guide (~20 numbered screenshots with captions), System User Guide
  Manual (introduction, system requirements, logging in, administrator procedures, tenant
  procedures, troubleshooting table, FAQ, contact), References (kept), Personal Information
  (placeholders × 6).
- **Review marks:** every placeholder and every rewritten passage the team must verify is
  highlighted yellow.

## Deck (≈18 slides)

Title · Background · General Objective · Specific Objectives · Scope · Limitation · System
Architecture · ERD · Feature: Public Portal · Feature: Admin Dashboard & Leases · Feature:
Billing & Payments · Feature: Tenant Portal · Feature: Administration · Security · Results and
Discussion (340 tests + flowchart) · Conclusion · Recommendation · Thank you.
At most ~6 bullets per slide; one screenshot per feature slide; adviser name is a highlighted
placeholder.

## Constraints

- Originals (`current.docx`, `base_docu.docx`, `ppt_template.pptx`) never modified.
- No new software installed; nothing downloaded except the diagrams.net viewer script the
  diagram rendering already used.
- Screenshots contain only seeded demo data (no real tenant data).
- Facts in the manuscript come from the code (routes, components, tests), not invention.

## Out of scope

Rewriting Chapter II's literature, new references, real photos, signatures, personal data.
