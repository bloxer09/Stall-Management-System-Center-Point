# 🏪 STALLM - Commercial Stall Management System

[![Framework](https://img.shields.io/badge/Framework-Laravel%2013.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Reactivity-Livewire%203-4E56C6?style=flat-square&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Payment Gateway](https://img.shields.io/badge/Payments-PayMongo-00D2FF?style=flat-square)](https://paymongo.com)
[![Tests](https://img.shields.io/badge/PHPUnit-100%25%20Passing-brightgreen?style=flat-square&logo=phpunit&logoColor=white)](https://phpunit.de)

**STALLM** is a modern commercial stall management web application built exclusively on **Laravel 13.x** and **Livewire 3**. It streamlines floor plan layout design, tenant lease allocations, automated monthly invoicing, multi-channel online payment checkout via PayMongo, and real-time support chat.

---

## ✨ Key Features

### 🔐 Authentication & Security
- **Laravel Breeze Auth Scaffolding**: Modern, reactive authentication flow with Livewire components.
- **Google OAuth 2.0 Integration**: Social sign-in powered by `laravel/socialite`.
- **Role-Based Access Control (RBAC)**: Strict permission boundaries for `admin`, `staff`, and `user` roles via `CheckRole` middleware.
- **Private Document Protection**: IDOR-guarded file endpoints for lease contracts and ticket attachments (`FileController`).

### 🗺️ Visual Stall & Floor Plan Management
- **Interactive Floor Plan Grid Editor**: Reactive Livewire component (`Admin\StallGridEditor`) supporting drag-and-drop or click-to-place stall layouts per floor, with server-side bounds and floor-ownership checks.
- **Stall Lifecycle**: State tracking (`available`, `occupied`, `maintenance`) with custom monthly pricing and floor assignments.

### 📜 Tenant Lease & Allocation Management
- **Stall Allocations**: Assign tenants to stalls with contract start/end dates and contract document uploads (`Admin\LeaseMappingManager`).
- **Lease Extensions**: Tenants can submit one-click extension requests with admin approval or rejection workflows.

### 💳 Billing, Online Payments & PDF Receipts
- **Automated Monthly Invoices**: Artisan console command (`billing:generate-invoices`) generating monthly invoices for active leases with zero N+1 queries.
- **PayMongo Payment Gateway**: Multi-stall cart checkout supporting GCash, Maya, and Credit/Debit cards with webhook verification.
- **Manual Office Payments**: Capability for admins to record cash/office payments with custom reference IDs.
- **Scannable QR Code PDF Receipts**: Official PDF receipts generated via `Dompdf` featuring embedded SVG QR codes pointing to online authenticity verification endpoints (`/verify-receipt/{id}`).

### 💬 Support Ticketing & Threaded Chat
- **Real-Time Support Threads**: Threaded messaging system between tenants and administrative staff (`Admin\TicketCenter` and `User\TicketManager`).
- **Rich Messaging**: File attachments, reply threading, unread badge counters, and typing indicators.

### 🔔 In-App Notification Center & Artisan Scheduler
- **Navbar Notification Bell**: Live polling notification drawer (`NavbarNotificationBell`) with unread badge counters and mark-as-read actions.
- **Artisan Scheduler**: Automated monthly invoicing and multi-stage payment reminder dispatches (7 days, 3 days, due today).

---

## 🛠️ Technology Stack

| Component | Technology |
| :--- | :--- |
| **Backend Language** | PHP 8.2+ |
| **Framework** | Laravel 13.x |
| **Reactivity** | Livewire 3 |
| **Database** | MySQL / MariaDB (InnoDB, Foreign Keys & Migrations) |
| **Authentication** | Laravel Breeze + Socialite |
| **Payment Gateway** | PayMongo API (Checkout Sessions & Webhooks) |
| **Transactional Email** | Mailpit (Dev SMTP) / Laravel Mail |
| **SMS Gateway** | Semaphore SMS API |
| **PDF Renderer** | Dompdf 3.1 |
| **Testing Framework** | PHPUnit 11 / Laravel Testing |

---

## 📧 Email Testing with Mailpit

In local development, the application sends emails via standard SMTP to **Mailpit**:
- **SMTP Port**: `1025` (`127.0.0.1:1025`)
- **Web UI**: `http://localhost:8025` (to view sent emails, OTPs, reminders, and alerts)

To run Mailpit locally:
```bash
# Via Docker:
docker run -d --name mailpit -p 1025:1025 -p 8025:8025 axllent/mailpit

# Or download standalone binary / winget:
winget install axllent.mailpit
mailpit
```

## 🚀 Quick Start Guide

### 1. Install PHP Dependencies
```bash
composer install
```

### 2. Environment Setup
Copy `.env.example` to `.env` and generate application key:
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Run Database Migrations & Seeders
```bash
php artisan migrate:fresh --seed
```

### 4. Launch Local Application
```bash
php artisan serve
```
Visit `http://127.0.0.1:8000` in your web browser.

---

## 🎭 Demo Data & Accounts

Load a realistic demo dataset (3 floors, 30 stalls, 4 tenants with leases, 6 months of payments, an overdue bill, tickets, and stall applications). It is safe to run more than once and refuses to run when `APP_ENV=production`.

```bash
php artisan db:seed --class=DemoSeeder
```

| Account | Email | Password | What to show |
| :--- | :--- | :--- | :--- |
| Admin | `admin@stallm.com` | `password` | Needs-attention panel, applications, renewals |
| Tenant 1 | `tenant1@demo.test` | `password` | Lease ends in 45 days → Request renewal |
| Tenant 2 | `tenant2@demo.test` | `password` | Renewal already requested |
| Tenant 3 | `tenant3@demo.test` | `password` | Bill due next week |
| Tenant 4 | `tenant4@demo.test` | `password` | Overdue bill |

Before a live demo, run `php artisan cache:clear` so login and application rate limits from rehearsals are reset.

## ⏰ Cron Scheduler Setup (Production)

Configure a server cron job to execute the Laravel Scheduler every minute:

```bash
* * * * * cd /path/to/stallm && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 Running Automated Tests

Run the full Laravel test suite:

```bash
php artisan test
```
