# 🏪 STALLM - Commercial Stall Management System

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Framework](https://img.shields.io/badge/Framework-Custom%20PSR--4%20MVC-007ACC?style=flat-square)](https://github.com/bloxer09/Stall-Management-System-Center-Point)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Styling](https://img.shields.io/badge/Styling-Tailwind%20CSS%20%2B%20DaisyUI-38B2AC?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Payment Gateway](https://img.shields.io/badge/Payments-PayMongo%20(GCash%20%2F%20Maya%20%2F%20Cards)-00D2FF?style=flat-square)](https://paymongo.com)
[![Tests](https://img.shields.io/badge/PHPUnit-100%25%20Passing-brightgreen?style=flat-square&logo=phpunit&logoColor=white)](https://phpunit.de)

**STALLM** is a full-featured, high-performance web platform designed for commercial arcade operators, market administrators, and real estate managers. Built on a zero-dependency custom PSR-4 MVC framework, STALLM streamlines floor plan layout design, tenant lease allocations, automated monthly invoicing, multi-channel online payment processing, support ticketing, and metered utility billing.

---

## ✨ Key Features

### 🔐 Authentication & Security
- **Modal-Based Authentication**: Seamless homepage sign-in and registration modals (`/?login=1`, `/?register=1`).
- **Google OAuth 2.0**: First-time social sign-ups enforce Terms & Privacy agreement and mandatory password creation, enabling both social and credential logins.
- **Multi-Channel 2FA OTP**: Email and SMS 2FA code verification with expiration timers and rate-limited resend cooldowns.
- **Role-Based Access Control (RBAC)**: Strict permission boundaries for `admin`, `staff`, and `user` (tenant) roles.
- **Private Document Protection**: IDOR-guarded file endpoints for contract documents and ticket attachments (`FileController`).

### 🗺️ Visual Stall & Floor Plan Management
- **Interactive Floor Plan Grid Editor**: Drag-and-drop / click-to-position stall grid layout builder supporting custom floor locations, rows, and columns.
- **Stall Status Lifecycle**: Real-time stall state tracking (`available`, `occupied`, `maintenance`) with custom pricing and floor assignments.

### 📜 Tenant Lease & Allocation Management
- **Stall Allocations**: Assign tenants to stalls with contract start/end dates, contract document uploads, and active lease tracking.
- **Automated Lease Renewal Workflow**: Tenants can request one-click lease extensions with admin approval or rejection workflows.

### 💳 Billing, Online Payments & PDF Receipts
- **Automated Monthly Invoicing**: High-performance, zero-N+1 query invoice generation for active lease mappings.
- **PayMongo Payment Gateway**: Integration with GCash, Maya, and Credit/Debit cards with PayMongo webhook verification.
- **Multi-Stall Cart Checkout**: Combine multiple pending invoices into a single aggregate PayMongo payment session.
- **Manual Office Payment Recording**: Admin capability to record cash/office payments with custom reference IDs and notes.
- **Scannable QR Code PDF Receipts**: Official PDF receipts generated via `Dompdf` featuring embedded SVG QR codes pointing to online authenticity verification endpoints (`/payment/verify-receipt/{id}`).

### 💬 Support Ticketing & Threaded Chat
- **Real-Time Support Threads**: Threaded messaging system between tenants and administrative staff.
- **Rich Messaging**: File attachments, reply threading, unread message indicators (`ticket_participants`), and typing status indicators.

### 🔔 In-App Notification Center & Automated Cron
- **Notification Drawer**: Navbar bell dropdown with unread badge counters and live update polling for due date alerts, payment confirmations, and support replies.
- **CLI Automated Cron**: Standalone CLI script (`bin/cron-invoicing.php`) for scheduled monthly invoicing and multi-stage email/SMS payment reminder dispatches (7 days, 3 days, due today).

### 📊 Financial Analytics & Utility Billing
- **Interactive Analytics Dashboard**: Chart.js financial analytics rendering 12-month collection trends, payment method breakdowns, and stall occupancy performance metrics.
- **Metered Utility Billing**: Sub-meter reading tracker for electricity (kWh) and water (cu.m) per stall, auto-calculating consumption costs.
- **Database Backup Management**: One-click SQL backup creation, download, and file management in Admin Settings (`/admin/settings/backups`).

---

## 🛠️ Technology Stack

| Component | Technology |
| :--- | :--- |
| **Backend Language** | PHP 8.2+ |
| **Architecture** | Custom PSR-4 Object-Oriented MVC Framework |
| **Database** | MySQL / MariaDB (InnoDB, Foreign Key Cascades & Indexes) |
| **Frontend Framework** | Vanilla JS (ES6+), HTML5, DaisyUI 4, Tailwind CSS 3 |
| **Icons & Fonts** | Lucide Icons, Google Fonts (Inter) |
| **Payment Gateway** | PayMongo API (Checkout Sessions & Webhooks) |
| **Transactional Email** | Mailjet API v3 |
| **SMS Gateway** | Semaphore SMS API |
| **PDF Renderer** | Dompdf 3.1 |
| **Testing Framework** | PHPUnit 11.5 |

---

## 🚀 Quick Start Guide

### Prerequisites
- **PHP** >= 8.2 with PDO, OpenSSL, cURL, and Mbstring extensions enabled.
- **MySQL / MariaDB** Server.
- **Composer** (PHP Package Manager).

### 1. Clone the Repository
```bash
git clone https://github.com/bloxer09/Stall-Management-System-Center-Point.git
cd Stall-Management-System-Center-Point
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Database Setup
1. Create a MySQL database (e.g., `stallm`).
2. Import the consolidated master schema file:
   ```bash
   mysql -u root -p stallm < database.sql
   ```

### 4. Environment Configuration
Copy `.env.example` to `.env` and fill in your database credentials and API keys:
```bash
cp .env.example .env
```

Edit `.env`:
```ini
APP_NAME="StallManage"
APP_URL="http://localhost:8000"
APP_BASE_PATH=""
APP_DEBUG=1

DB_HOST=127.0.0.1
DB_NAME=stallm
DB_USER=root
DB_PASS=your_password

PAYMONGO_SECRET_KEY=sk_test_xxx
PAYMONGO_WEBHOOK_TOKEN=your_webhook_secret

GOOGLE_CLIENT_ID=your_google_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

MAILJET_API_KEY=your_mailjet_api_key
MAILJET_API_SECRET=your_mailjet_api_secret
MAILJET_FROM_EMAIL=no-reply@yourdomain.com
```

### 5. Launch the Application
Run the built-in PHP development server pointing to the `/public` directory:
```bash
php -S localhost:8000 -t public
```
Visit `http://localhost:8000` in your web browser.

---

## ⏰ Cron Job Setup (Production)

To automate monthly invoicing and payment reminders, configure a server cron job to execute the CLI background script on the 1st of every month:

```bash
0 0 1 * * /usr/bin/php /path/to/Stall-Management-System-Center-Point/bin/cron-invoicing.php >> /path/to/storage/logs/cron.log 2>&1
```

---

## 🧪 Running Automated Tests

Run the full PHPUnit test suite to verify system integrity:

```bash
composer test
```
*or*
```bash
vendor/bin/phpunit
```

---

## 📁 Directory Structure

```text
Stall-Management-System-Center-Point/
├── bin/                      # CLI Background Cron Scripts
│   └── cron-invoicing.php
├── public/                   # Public Web Root (index.php, htaccess, assets, uploads)
│   ├── index.php             # Application Front Controller & Router
│   └── uploads/              # Uploaded Images & Attachments
├── src/                      # Application Source Code (PSR-4 App\)
│   ├── Config/               # Database & App Configurations
│   ├── Controllers/          # Controller Handlers (Auth, Stall, Payment, Analytics, etc.)
│   ├── Core/                 # Framework Core (Router, Session, Auth, ErrorHandler, Controller)
│   ├── Models/               # Data Models & PDO Queries
│   ├── Services/             # External API Services (Mailjet, Semaphore, QrCode, FileUpload)
│   └── Views/                # Page Views, Layouts & Shared UI Components
├── storage/                  # Backups, Generated Receipts, and Daily Log Files
├── tests/                    # PHPUnit Test Suites
├── database.sql              # Master Consolidated MySQL Database Schema
├── composer.json             # PHP Dependencies & Autoload Configuration
└── phpunit.xml               # PHPUnit Configuration
```

---

## 🔒 Security Practices

- **Prepared Statements**: 100% of database interactions use PDO parameterised queries to prevent SQL Injection.
- **XSS Protection**: Sanitized output escaping via `htmlspecialchars` across all template views.
- **CSRF Tokens**: Form and AJAX submissions are verified against cryptographically secure session CSRF tokens.
- **Document Ownership Checks**: Private files (contract PDFs and ticket attachments) are verified against the active session's user ID before serving.
- **Production Pre-Flight**: Set `APP_DEBUG=0` and `SESSION_SECURE_COOKIE=1` in production environments under HTTPS.

---

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).
