Republic of the Philippines
West Prime Horizon Institute, Inc.
Bachelor of Science in Information Technology
Pagadian City, Zamboanga del Sur


WEB BASED STALL MANAGEMENT SYSTEM FOR CENTER POINT

A Capstone Project Presented to the Faculty of
West Prime Horizon Institute, Inc.


In Partial Fulfillment of the Requirements for the Degree of
Bachelor of Science in Information Technology
Academic Year 2025–2026


WINLY JOHN B. ALEGADO
DANISLYN G. BALBASTRO
JOHN LLOYD B. RAMOS
MARIAM C. JAMIRO
ARJIE C. JUMUAD
Researchers


WAIEN WATAMAMA
Adviser


July 2026


CHAPTER I
INTRODUCTION


Background of the Study

In recent years, modernization has significantly influenced various sectors, including business management and administrative operations. While technology has improved efficiency in industries such as banking, education, and healthcare, some local commercial establishments still rely on traditional methods. One example is Center Point, a commercial establishment in Pagadian City that provides multiple stalls for rent to different vendors and small entrepreneurs across its floor levels.

Center Point plays an important role as a local business hub where small entrepreneurs operate daily. It supports the local economy by providing accessible spaces for vendors to sell goods and services. However, despite its significance, the management of stalls had been handled through manual processes such as handwritten records, paper-based receipts, and physical logbooks. Prospective tenants also had no formal channel for applying for a stall online, requiring them to physically visit the office for inquiries and applications.

These traditional methods had become increasingly inefficient as the number of tenants grew. Tracking stall occupancy, monitoring rental payments, managing tenant records, and handling lease contract expirations manually was time-consuming and prone to human error. Records could be misplaced, data could become inconsistent, and retrieving information was difficult. Moreover, tenants had limited visibility into their own accounts, relying entirely on staff to verify payment history and outstanding balances.

Another major challenge was the absence of a convenient and secure payment system, a structured communication channel for tenant concerns, a centralized document repository for tenant records, and a reliable mechanism for notifying tenants and administrators of approaching lease expiration dates. Administrative accounts also lacked a layered security mechanism to protect sensitive financial and tenant data.

To address these challenges, this study presents the design and development of the Web Based Stall Management System for Center Point. The system digitizes and streamlines all stall management processes through a web-based platform developed using Laravel 13.x and Livewire 3. It provides a tenant portal for self-service account access, an online payment gateway via PayMongo, automated monthly invoicing with multi-stage SMS and email reminders, a real-time support ticket chat system, a tenant document repository, advanced financial reporting and analytics, a public-facing stall reservation portal, lease expiry automated notifications, Facebook OAuth social login, and Two-Factor Authentication (2FA) for administrative and staff accounts.

By implementing these features, the system aims to enhance efficiency, accuracy, transparency, security, and overall service quality in managing stall operations at Center Point.


Project Context

The concept for this project was developed through direct observation of the daily operations at Center Point, where stall management processes were primarily handled manually. Staff relied heavily on physical records when managing tenant information, stall assignments, lease contracts, and payment tracking. When tenants requested to verify their payment status or account balance, staff had to manually search through logbooks, resulting in delays and inconvenience.

Tenants had no direct access to their own records. This lack of transparency led to repeated inquiries and dependency on staff for simple information such as payment history, outstanding balances, or copies of their lease contracts. The absence of a centralized digital system also made it difficult to monitor stall availability in real time across floor levels.

The reliance on manual payment collection, the lack of advance notifications for expiring leases, the absence of a public reservation channel for prospective tenants, and the lack of layered authentication security for administrative accounts were additional operational gaps that needed to be addressed.

To address all of these problems, the proposed system introduces a comprehensive digital solution built on Laravel 13.x and Livewire 3. Beyond the core tenant portal and PayMongo payment integration, the system also incorporates advanced financial reporting tools with exportable summaries, automated lease expiry notifications dispatched via Mailjet email and Semaphore SMS at 60 and 30 days before contract end dates, Facebook OAuth social login in addition to Google OAuth, a public-facing stall reservation portal for prospective tenants, a tenant document repository providing secure on-demand access to lease contracts, receipts, and billing statements, and optional Two-Factor Authentication via SMS OTP for administrator and staff accounts.

By implementing these enhancements, the system transforms the manual operations at Center Point into a more efficient, transparent, secure, and user-centered management process.


Software and Technologies Used

To ensure the system is robust, secure, and user-friendly, the researchers utilized a specific set of modern software development tools and frameworks. Each tool was selected based on its ability to handle the complex requirements of the stall rental management system.

- Laravel 13.x (PHP Framework): The primary backend framework enforcing the Model-View-Controller (MVC) pattern. Provides Eloquent ORM, middleware, Artisan CLI for scheduled commands, and a comprehensive application ecosystem.

- Livewire 3: Powers all reactive, server-rendered UI components including the floor plan grid editor, support ticket chat, notification bell, advanced reports interface, document repository, and reservation portal, without requiring a separate JavaScript framework.

- MySQL / MariaDB: The relational database management system storing all system data including tenant profiles, stall records, lease contracts, payment transactions, support tickets, audit logs, stall applications, and two-factor codes. Uses InnoDB with foreign key constraints and indexed columns.

- Laravel Breeze with Socialite: Authentication is handled by Laravel Breeze. Google OAuth 2.0 and Facebook OAuth 2.0 social logins are both implemented via the laravel/socialite package.

- PayMongo API: Enables online rental payments via GCash, Maya, credit cards, and debit cards through checkout sessions and asynchronous webhook listeners.

- Mailjet API: Dispatches transactional emails including payment confirmations, invoice reminders, lease expiry notifications, stall application confirmations, and account-related alerts.

- Semaphore SMS API: Sends SMS notifications for billing reminders, lease expiry alerts, Two-Factor Authentication OTP codes, and other account events.

- Dompdf: Generates official PDF receipts with embedded QR codes, and produces exportable financial summary PDF reports.

- Chart.js: A JavaScript charting library integrated into the advanced financial reporting module to render interactive monthly and annual revenue trend charts and occupancy rate visualizations.

- Laragon: The local development environment providing a fast and lightweight stack with Apache, PHP 8.2, and MySQL.

- Visual Studio Code: The primary code editor for writing, managing, and debugging the system source code.


Development and Testing Environment

The development and testing environment refers to the software, hardware, local server stacks, testing tools, and third-party API sandboxes utilized during the design, implementation, and quality assurance of the Web Based Stall Management System for Center Point.

Development Environment:
- Hardware Specification: Host workstation equipped with an Intel Core processor, 16GB RAM, and 512GB SSD running Windows 11 64-bit operating system.
- Local Server Stack: Laragon local development environment providing an integrated, high-performance stack with Apache 2.4 web server, PHP 8.2+ runtime environment, and MySQL 8.0 relational database server.
- Code Editor and IDE: Visual Studio Code equipped with PHP Intelephense, Laravel Extra Intellisense, Blade Snippets, Tailwind CSS IntelliSense, and Git integration for code editing, syntax verification, and version control.
- Dependency Management: Composer 2.x for PHP backend dependency resolution (Laravel framework, Livewire 3, Socialite, Dompdf, Mailjet SDK) and NPM for frontend asset building.
- Version Control System: Git repository tracking system for source code change management, branch isolation, and feature tracking.

Testing Environment:
- Automated Testing Framework: PHPUnit 11 integrated with Laravel Test Bench for executing unit tests, feature tests, HTTP endpoint assertions, database state assertions, and mock API service responses.
- API Testing & Webhook Sandbox: Postman and PayMongo Sandbox Environment for constructing and testing API requests, verifying JSON payloads, and simulating PayMongo webhook signature events (e.g., payment confirmation, checkout session completion).
- Third-Party API Test Gateways: 
  - Mailjet Test Mode for capturing and verifying transactional email dispatches.
  - Semaphore Sandbox API for testing SMS notification dispatches and 2FA OTP code delivery.
- Cross-Browser & Device Simulation: Google Chrome DevTools and Microsoft Edge for responsive UI testing across desktop, tablet, and mobile viewport dimensions, ensuring cross-browser compatibility and consistent rendering of Chart.js graphs and Livewire interactive components.


Purpose and Description

The primary purpose of this study is to develop the Web Based Stall Management System for Center Point — a comprehensive, full-stack digital platform that replaces all manual stall management processes with an integrated, automated, and user-centered system for administrators, staff, and tenants.

The system serves as a centralized platform where administrators can manage floor plan layouts, assign leases, monitor tenant records, track rental payments, review financial analytics, manage stall applications from prospective tenants, and configure system-wide settings. Staff members have limited access for transaction recording and tenant support. Tenants are provided with a self-service portal for account monitoring, online payments, support communication, and document access.

Key features of the system include:

- Interactive Floor Plan Grid Editor: A visual Livewire-powered editor for configuring stall layouts per floor level.
- Stall Management Module: Tracks stall identifiers, pricing, descriptions, images, grid positions, and occupancy status (Available, Occupied, Maintenance).
- Lease Mapping and Contract Management: Assigns tenants to stalls with defined contract periods, PDF contract uploads, and a renewal request workflow.
- Lease Expiry Automated Notifications: Automatically notifies both administrators and tenants via email and SMS at 60 and 30 days before lease contract expiration dates.
- Automated Monthly Invoicing: Generates invoices for active leases via a scheduled Artisan command with multi-stage email and SMS payment reminders.
- Online Payment Integration (PayMongo): Secure multi-method digital payments via GCash, Maya, credit cards, and debit cards.
- QR Code PDF Receipts: Official receipts with embedded QR codes for online authenticity verification.
- Tenant Document Repository: A secure, centralized download center in the tenant portal for lease contracts, payment receipts, and billing statements.
- Advanced Financial Reporting and Analytics: Interactive revenue trend charts (Chart.js), occupancy rate analytics, overdue payment aging reports, and exportable CSV and PDF financial summaries.
- Public Stall Reservation Portal: A guest-accessible page where prospective tenants can view available stalls and submit a formal application online.
- Support Ticket System with Real-Time Chat: Threaded messaging with file attachments, typing indicators, and read receipts.
- In-App Notification Center: Real-time notification bell with unread badge counters.
- Google OAuth and Facebook OAuth Social Login: Social sign-in via both Google and Facebook accounts.
- Two-Factor Authentication (2FA): Optional SMS OTP-based 2FA for administrator and staff accounts, enforced once enabled per account.
- Utility Billing Module: Records metered utility readings and computes associated charges.
- Audit Log System: Tamper-evident record of all significant administrative actions.

Overall, the system transforms manual stall management into a comprehensive, efficient, secure, and organized digital process.


Objectives of the Study

General Objective

To design and develop the Web Based Stall Management System for Center Point using Laravel 13.x and Livewire 3 that enhances operational efficiency, transparency, security, and user accessibility through automated billing, digital payments, real-time communication, advanced analytics, and comprehensive tenant self-service features.

Specific Objectives

To realize this project, the following specific objectives were achieved:

1. To design a structured relational database for managing tenant, stall, lease, payment, utility, support ticket, stall application, and two-factor authentication data.
2. To develop an interactive floor plan grid editor that allows administrators to configure stall layouts per floor level.
3. To implement a stall management module that provides real-time updates on stall availability and occupancy status.
4. To integrate an online payment gateway using PayMongo for secure rental payment transactions via GCash, Maya, and cards.
5. To create a tenant portal where users can view stall details, active lease contracts, payment history, and account status.
6. To implement a tenant document repository providing secure, on-demand access to lease contracts, payment receipts, and billing statements.
7. To develop an automated monthly invoicing system using a Laravel Artisan scheduler with multi-stage SMS and email payment reminders.
8. To implement automated lease expiry notification dispatching via Mailjet email and Semaphore SMS at 60 and 30 days before contract end dates.
9. To generate official PDF receipts with embedded QR codes for online authenticity verification using Dompdf.
10. To develop a support ticket module with real-time threaded chat, file attachments, and read receipts for structured tenant-management communication.
11. To implement a utility billing module for recording metered utility readings and computing associated charges.
12. To develop an advanced financial reporting and analytics module with interactive charts, aging reports, and exportable CSV and PDF summaries.
13. To create a public-facing stall reservation portal for prospective tenants to view available stalls and submit online applications.
14. To implement Google OAuth 2.0 and Facebook OAuth 2.0 social login via laravel/socialite.
15. To implement optional Two-Factor Authentication (2FA) via SMS OTP for administrator and staff accounts.
16. To implement an audit logging system that records administrative actions with timestamps and IP addresses for operational accountability.
17. To evaluate the system's usability, functionality, and performance through user acceptance testing.


Significance of the Study

The development and implementation of the Web Based Stall Management System for Center Point yields substantial benefits for various stakeholders involved in commercial stall operations in Pagadian City, Zamboanga del Sur. Specifically, this study is significant to the following groups:

- Center Point Management and Administrators — Provides a centralized, automated control panel to manage floor plan layouts, stall inventory, tenant profiles, lease contracts, and utility billing. Automates monthly invoicing and dispatches multi-stage payment reminders and 60/30-day lease expiry alerts, drastically reducing administrative workload, billing computation errors, and unmanaged lease expirations. Advanced financial reporting tools with exportable CSV and PDF summaries empower management with real-time financial visibility and data-driven decision-making capabilities.

- Administrative and Office Staff — Streamlines daily operational tasks by replacing manual paper logbooks and handwritten receipts with digital workflows. The automated PayMongo payment webhook integration, online receipt generation with QR code verification, and structured Livewire support ticket chat system allow staff to process payments, track tenant concerns, and manage stall applications efficiently without administrative bottlenecks.

- Stall Tenants — Enhances transparency and convenience by providing a 24/7 self-service tenant portal. Tenants can monitor active lease terms, view billing statements, pay rent online via GCash, Maya, or cards, download official receipts and contractual documents from a secure document repository, and receive automated email and SMS reminders for upcoming payments and lease contract end dates.

- Prospective Tenants and Applicants — Eliminates the requirement to physically visit the administrative office for stall rental inquiries. The public-facing stall reservation portal allows prospective vendors to view real-time stall availability across floor levels and submit formal online applications with supporting documents conveniently from any location.

- Future Researchers and IT Practitioners — Serves as a comprehensive reference and foundation for academic research regarding modern web-based property and stall rental management systems. Demonstrates the practical integration of Laravel 13.x, Livewire 3, online payment gateways (PayMongo), multi-channel messaging (Mailjet email and Semaphore SMS), OAuth 2.0 social authentication, and Two-Factor Authentication (2FA) in local commercial establishments.


Scope and Limitations

This study covers the design, development, testing, and deployment of the Web Based Stall Management System for Center Point in Pagadian City, covering tenant registration, stall assignment, lease contract management, automated billing, online payment processing, utility billing, support ticket communication, advanced financial reporting, public stall reservation, tenant document repository, lease expiry notifications, social authentication, Two-Factor Authentication, and audit logging.

The system includes three types of users with distinct access levels:

- Administrator — Full system control including stall management, lease allocation, payment management, utility billing, support ticket oversight, advanced financial reports, stall application management, audit log viewing, and system settings including 2FA configuration.
- Staff — Limited access for payment recording, monitoring tenant accounts, responding to support tickets, and managing stall applications.
- Tenant — Can access their own portal, view stall and lease details, pay rent online, access the document repository, submit support tickets, and receive in-app, SMS, and email notifications.
- Guest (Unauthenticated) — Can access the public stall reservation portal to view available stalls and submit a rental application.

The system supports online payments through PayMongo (GCash, Maya, credit and debit cards), automated invoicing, multi-stage payment reminders, lease expiry alerts, social logins via Google and Facebook, optional SMS OTP-based 2FA for privileged accounts, and exportable financial reports in CSV and PDF formats.

However, the system has certain limitations. It requires a stable internet connection for online payment processing, webhook handling, email and SMS dispatching, and real-time notification updates. Service disruptions from PayMongo, Mailjet, or Semaphore may temporarily affect the corresponding features. The system does not handle advanced accounting functions such as tax computation or multi-currency support. It is limited to stall rental management and does not include inventory tracking for individual tenant businesses. The floor plan grid editor is designed for structured grid-based layouts and may not accommodate highly irregular physical floor configurations. The 2FA feature requires tenants to have a registered and verified phone number and relies on SMS delivery by the Semaphore gateway.


Definition of Terms

The following terminology is used to conceptualize and operationalize the study:

| Term / Concept | Operational Definition |
| :--- | :--- |
| **Laravel 13.x** | A modern PHP web application framework following the Model-View-Controller (MVC) pattern, serving as the primary backend framework of the system. |
| **Livewire 3** | A full-stack reactive component framework for Laravel enabling real-time server-side UI interactions without a separate JavaScript framework. |
| **Center Point** | The commercial establishment in Pagadian City, Zamboanga del Sur, that serves as the client of this study, offering stalls for rent across its floor levels. |
| **Stall** | A designated physical space within Center Point that is rented by a vendor for business operations. |
| **Tenant** | An individual or business entity that rents a stall and is registered in the system with access to the tenant portal. |
| **Tenant Portal** | The system interface allowing tenants to access their stall details, lease information, payment history, document repository, and support tickets. |
| **Stall Mapping (Lease Contract)** | A system record linking a tenant to a stall for a defined contract period with start and end dates and a PDF contract document. |
| **Lease Expiry Notification** | An automated alert sent via Mailjet email and Semaphore SMS to both the tenant and administrator at 60 and 30 days before a lease contract's end date. |
| **Online Stall Reservation** | A public-facing feature allowing prospective tenants to submit a formal rental application online, including business information and a preferred stall selection. |
| **Stall Application** | A record in the system representing a formal online rental application submitted by a prospective tenant through the public reservation portal. |
| **Tenant Document Repository** | A secure section of the tenant portal where tenants can access and download their lease contracts, payment receipts, billing statements, and other related documents. |
| **Advanced Financial Reporting** | A system module providing interactive revenue trend charts, occupancy rate analytics, overdue payment aging reports, and exportable financial summaries in CSV and PDF formats. |
| **PayMongo** | A Philippine payment gateway integrated into the system for secure online payment transactions via GCash, Maya, credit cards, and debit cards. |
| **Automated Invoicing** | The system's capability to automatically generate monthly rental invoices for all active leases using a scheduled Laravel Artisan command. |
| **PDF Receipt** | An official payment receipt generated using Dompdf, featuring an embedded QR code for online authenticity verification. |
| **QR Code Verification** | A feature allowing anyone to scan a QR code on a receipt to confirm its authenticity through an online verification endpoint. |
| **Support Ticket** | A digital concern or request submitted by a tenant, handled through a threaded real-time chat interface with file attachment support. |
| **Utility Billing** | A module recording metered utility readings per stall and computing corresponding billing charges. |
| **Audit Log** | A tamper-evident record of all significant administrative actions, including user identity, action, affected entity, timestamp, and IP address. |
| **Role-Based Access Control (RBAC)** | A security mechanism restricting system access based on the user's assigned role (Administrator, Staff, or Tenant). |
| **Google OAuth 2.0** | A social login protocol allowing users to sign in using their Google account, implemented via the laravel/socialite package. |
| **Facebook OAuth** | A social login protocol allowing users to sign in using their Facebook account, also implemented via the laravel/socialite package. |
| **Two-Factor Authentication (2FA)** | An optional additional security layer for administrator and staff accounts requiring a one-time SMS OTP code sent via Semaphore after the initial password login. |
| **OTP (One-Time Password)** | A temporary, single-use numeric code sent to a user's registered mobile number via Semaphore SMS as part of the Two-Factor Authentication process. |
| **Artisan Scheduler** | A Laravel feature automating the execution of console commands on a defined schedule, used for monthly invoice generation, payment reminders, and lease expiry notifications. |
| **Mailjet** | A transactional email service used to send billing reminders, payment confirmations, lease expiry alerts, and stall application notifications. |
| **Semaphore SMS** | An SMS gateway used to send billing reminders, lease expiry alerts, and Two-Factor Authentication OTP codes. |
| **Dompdf** | A PHP HTML-to-PDF library used to generate official payment receipts and exportable financial report documents. |
| **Chart.js** | A JavaScript charting library used in the advanced financial reporting module to render interactive revenue trend and occupancy rate charts. |
| **Dashboard** | The main administrative control panel displaying key performance indicators such as total revenue, occupied stalls, active leases, pending applications, and pending support tickets. |
| **Surcharge** | An additional fee applied to tenants for late payment of rental invoices beyond the defined due date. |
