# Implementation Plan - Lodgify-Inspired DaisyUI Overhaul, Dark Mode & Live Ticket Chat Restoration

Rework the STALLM platform UI using a clean, modern **Lodgify-inspired property management aesthetic** powered strictly by **DaisyUI** components and themes. Add full **Dark Mode** support and restore **Real-Time Live Ticket Chat** with polling and dual view modes (Table & Split Live Board).

---

## User Review Required

> [!IMPORTANT]
> - **DaisyUI Semantic Color Adoption**: Legacy hardcoded tailwind color classes (e.g. `bg-slate-900`, `bg-indigo-600`, `bg-white`, `text-gray-900`) will be replaced with DaisyUI theme tokens (`bg-base-100`, `bg-base-200`, `bg-base-300`, `text-base-content`, `border-base-200`, `btn-primary`, `badge-primary`) so that Dark Mode seamlessly updates every page, card, modal, and table.
> - **Theme Switcher**: A theme toggle (Light / Dark mode) will be added to the top navigation header, persisting theme choice in browser `localStorage`.
> - **Live Ticket Chat**: In Admin Ticket Center & throughout the platform, clicking a support ticket opens a **Messenger-style floating mini chat tab** docked at the bottom right of the screen. Admins can view table records while simultaneously chatting live with tenants in a floating, minimizable chat widget with automatic 3-second Livewire polling (`wire:poll.3s`).

---

## Proposed Changes

---

### Layouts & Core Theme System

#### [MODIFY] [tailwind.config.js](file:///c:/laragon/www/stallm/tailwind.config.js)
- Ensure DaisyUI light and dark themes are active (`themes: ["light", "dark"]`).

#### [MODIFY] [resources/css/app.css](file:///c:/laragon/www/stallm/resources/css/app.css)
- Add base theme variable overrides if needed and custom scrollbar dark-mode compatibility.

#### [MODIFY] [resources/views/layouts/app.blade.php](file:///c:/laragon/www/stallm/resources/views/layouts/app.blade.php)
- Add head script to read `localStorage.getItem('theme')` on initial page load to prevent FOUC (flash of unstyled light content).
- Overhaul layout drawer, main container background (`bg-base-200`), sidebar styling using DaisyUI base themes (`bg-base-100` / `bg-base-300`), active route indicators, and mobile overlay.

#### [MODIFY] [resources/views/layouts/navigation.blade.php](file:///c:/laragon/www/stallm/resources/views/layouts/navigation.blade.php)
- Add DaisyUI Theme Switcher (Light / Dark mode toggle switch/dropdown with Alpine.js / Livewire persistence).
- Refine brand logo, search bar, notification bell drawer, and user profile menu dropdown.

---

### Support Ticket System (Live Chat & Table Restoration)

#### [MODIFY] [resources/views/livewire/admin/ticket-center.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/admin/ticket-center.blade.php)
- Add dual-view mode switcher tab: **Table View** & **Live Chat Board (Split Screen)**.
- Add `wire:poll.3s` to the active ticket thread container so incoming tenant messages update automatically in real-time.
- Format messages with DaisyUI `chat chat-start` / `chat chat-end` bubbles, attachment downloads, status toggles, and tenant info drawer.

#### [MODIFY] [app/Livewire/Admin/TicketCenter.php](file:///c:/laragon/www/stallm/app/Livewire/Admin/TicketCenter.php)
- Support active ticket selection, dual view mode state (`$viewMode = 'table'|'chat'`), auto-marking messages as read, and message sending.

#### [MODIFY] [resources/views/livewire/user/ticket-manager.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/user/ticket-manager.blade.php)
- Add `wire:poll.3s` to the active ticket thread so admin replies update live without manual page refresh.
- Refine chat layout using DaisyUI components (`card`, `chat`, `chat-bubble`, `badge`, `textarea`, `btn`).

---

### Admin Dashboard & Management Views

#### [MODIFY] [resources/views/livewire/admin/analytics-dashboard.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/admin/analytics-dashboard.blade.php)
- Rework stats cards with DaisyUI `stats shadow bg-base-100`, revenue charts, quick filter pills, dark-mode friendly graph containers.

#### [MODIFY] [resources/views/livewire/admin/stall-manager.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/admin/stall-manager.blade.php)
- Lodgify-style stall catalog cards and data table with search, status filters, floor badges, and modal forms.

#### [MODIFY] [resources/views/livewire/admin/floor-manager.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/admin/floor-manager.blade.php)
- Rework floor level cards, capacity progress indicators, and edit modals.

#### [MODIFY] [resources/views/livewire/admin/stall-grid-editor.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/admin/stall-grid-editor.blade.php)
- Visual floor plan editor styling, stall grid tiles, legend, and controls using DaisyUI theme variables.

#### [MODIFY] [resources/views/livewire/admin/lease-mapping-manager.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/admin/lease-mapping-manager.blade.php)
- Rework tenant lease table, lease status badges, contract viewer modal, assign lease dialog.

#### [MODIFY] [resources/views/livewire/admin/payment-manager.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/admin/payment-manager.blade.php)
- Rework payment ledger table, search bar, office cash payment modal, invoice preview modal.

#### [MODIFY] [resources/views/livewire/admin/utility-billing-manager.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/admin/utility-billing-manager.blade.php)
- Utility billing calculator, reading inputs, billing breakdown modal, status badges.

#### [MODIFY] [resources/views/livewire/admin/stall-application-manager.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/admin/stall-application-manager.blade.php)
- Stall applications approval table, document reviewer modal, decision actions.

#### [MODIFY] [resources/views/livewire/admin/advanced-reports.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/admin/advanced-reports.blade.php) & log viewers
- Clean report filter panel, export options, and log view tables.

---

### Tenant Portal Views

#### [MODIFY] [resources/views/livewire/user/my-leases.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/user/my-leases.blade.php)
- Lodgify-style lease overview cards, active contract badges, extension request modal.

#### [MODIFY] [resources/views/livewire/user/invoices-checkout.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/user/invoices-checkout.blade.php)
- Billing statement cards, payment method selectors (Maya, PayMongo, GCash), receipt dialog.

#### [MODIFY] [resources/views/livewire/user/document-repository.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/user/document-repository.blade.php)
- Lease agreements & payment receipt files grid/table.

---

### Public Marketplace & Auth Modals

#### [MODIFY] [resources/views/pages/home.blade.php](file:///c:/laragon/www/stallm/resources/views/pages/home.blade.php) & [resources/views/livewire/public/stall-reservation.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/public/stall-reservation.blade.php)
- Hero section, property/stall catalog cards (Lodgify aesthetic: soft shadows, badge tags, price tags, image carousel/placeholder), floor filter pills, reservation modal.

#### [MODIFY] [resources/views/livewire/auth/auth-modals.blade.php](file:///c:/laragon/www/stallm/resources/views/livewire/auth/auth-modals.blade.php)
- Clean DaisyUI modal windows for Login & Registration.

---

## Verification Plan

### Automated Tests
- Run PHP syntax check on modified Blade and Livewire files:
  ```powershell
  php artisan view:cache
  ```

### Manual Verification
1. **Dark Mode Verification**:
   - Click Theme Switcher in the top navbar to toggle between Light and Dark themes.
   - Verify every page (Admin Dashboard, Stall Catalog, Floor Levels, Tenant Leases, Payments, Support Chat, Analytics, User Portal) renders cleanly in dark mode without unreadable text or mismatched white background boxes.
2. **Live Ticket Chat Verification**:
   - Navigate to Support Chat in both Admin and Tenant windows.
   - Open a support ticket and send a reply from Tenant to Admin.
   - Verify Admin sees the chat thread in real-time (`wire:poll.3s`) and can switch between Table View and Live Chat Board.
   - Send a reply from Admin and verify Tenant receives the message live.
3. **Lodgify Aesthetic Consistency**:
   - Check cards, stats, tables, navigation sidebar, badges, and modals across the application to ensure a unified Lodgify look with DaisyUI styling.
