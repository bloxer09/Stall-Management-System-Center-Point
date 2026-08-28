WEB BASED STALL MANAGEMENT SYSTEM FOR CENTER POINT

A Capstone Project Presented to the Faculty of
West Prime Horizon Institute, Inc.
Pagadian City, Zamboanga del Sur

In Partial Fulfillment
of the Requirements for Capstone Project II
For the Degree Bachelor of Science in Information Technology
Academic Year 2025–2026

By

DANISLYN G. BALBASTRO
WINLY JOHN B. ALEGADO
JOHN LLOYD B. RAMOS
MARIAM C. JAMIRO
ARJIE C. JUMUAD


July 2026


CHAPTER V
SUMMARY, CONCLUSIONS, AND RECOMMENDATIONS


Summary of the Study

This study presented the design, development, testing, and deployment of the Web Based Stall Management System for Center Point, a comprehensive, full-stack web-based information system developed to address the operational inefficiencies of the manual stall management practices at Center Point in Pagadian City, Zamboanga del Sur.

Prior to the development of the system, Center Point relied entirely on manual processes for managing its stall operations across its floor levels. Tenant records, stall assignments, lease contracts, payment collections, and support communications were all handled using paper logbooks, handwritten receipts, and informal arrangements. Prospective tenants had no formal online channel for submitting rental applications, requiring in-person office visits. Administrative accounts had no layered authentication security. Management had no centralized financial reporting tool. There was no automated mechanism to notify administrators or tenants of approaching lease contract expiration dates.

To address all of these challenges, the researchers developed a comprehensive, full-stack web-based system using Laravel 13.x as the backend PHP framework and Livewire 3 as the reactive front-end component layer. The system was structured around the Software Development Life Cycle (SDLC) following the Waterfall Model, progressing through the phases of Planning, Analysis and Design, Coding and Development, Testing, Implementation, and Maintenance.

The system was built with three authenticated user roles — Administrator, Staff, and Tenant — plus a public guest access route for the stall reservation portal. It incorporates the following functional modules:

1. Authentication and Access Control — Secure login with Laravel Breeze scaffolding, Google OAuth 2.0 and Facebook OAuth social sign-in via laravel/socialite, email verification, and role-based access control enforced through a CheckRole middleware.

2. Two-Factor Authentication (2FA) — An optional per-account security layer for administrator and staff accounts. When enabled, the system generates a 6-digit OTP, stores it with an expiry timestamp, and dispatches it via Semaphore SMS. Access to privileged routes is blocked until the OTP is verified within the session.

3. Stall Location and Floor Plan Management — Organization of stalls by floor level or zone, and an interactive Livewire-powered floor plan grid editor enabling administrators to visually configure stall positions per floor.

4. Stall Management — Comprehensive management of stall records including stall number, description, display image, monthly rental price, grid layout position, and occupancy status (available, occupied, maintenance).

5. Lease Mapping and Contract Management — Linking tenants to stalls with defined contract periods, PDF contract document uploads, active lease tracking, and a lease renewal request workflow with administrator approval or rejection.

6. Lease Expiry Automated Notification — A scheduled daily Artisan command (stallm:notify-expiring-leases) that identifies leases expiring in 60 and 30 days and dispatches automated alerts to both the tenant and administrator via email and Semaphore SMS.

7. Automated Monthly Invoicing — A scheduled Artisan console command (stallm:generate-invoices) that automatically generates monthly rental invoices for all active leases without duplication, with multi-stage payment reminders dispatched via email and Semaphore SMS at seven days, three days, and on the payment due date.

8. Online Payment Processing — Integration of the PayMongo payment gateway supporting GCash, Maya, credit cards, and debit cards through checkout sessions and asynchronous webhook verification for real-time invoice status updates.

9. Manual Payment Recording — Administrative capability to record cash or office payments with custom reference identifiers.

10. PDF Receipt and Document Generation — Official A4 payment receipts generated using Dompdf upon payment confirmation, featuring tenant details, billing period, payment method, transaction reference, and an embedded SVG QR code linking to an online authenticity verification endpoint.

11. Tenant Document Repository — A secure, centralized section within the tenant portal where tenants can access and download all their documents including lease contracts, payment receipts, and billing statements through IDOR-protected file endpoints.

12. Utility Billing — Recording of metered utility readings (electricity, water) per tenant stall and computation of corresponding charges.

13. Public Stall Reservation Portal — A guest-accessible module displaying available stalls on a read-only floor plan and providing a formal online rental application form for prospective tenants. Submissions are stored and trigger an email confirmation.

14. Stall Application Management — An administrative interface for reviewing, approving, or rejecting submitted stall applications, with the ability to link approved applications to tenant accounts for lease assignment.

15. Advanced Financial Reporting and Analytics — Interactive revenue trend charts and occupancy rate visualizations rendered via Chart.js, an overdue payment aging report table, and exportable financial summaries in CSV and PDF formats.

16. Support Ticket and Real-Time Chat — A structured helpdesk powered by Livewire 3, enabling tenants to submit concerns or maintenance requests and engage in threaded real-time chat with administrative staff. Features include file attachments, typing indicators, and read receipt tracking.

17. In-App Notification Center — A real-time notification bell delivering in-app alerts for new invoices, payment confirmations, ticket replies, lease events, lease expiry warnings, and application status updates.

18. Audit Log System — Tamper-evident logging of all significant administrative actions including the responsible user, action performed, affected entity, timestamp, and IP address.

19. Analytics Dashboard — Key performance indicators for administrators including total revenue, stall occupancy rates, active lease counts, overdue invoices, pending applications, and pending support tickets.

The system was verified through automated PHPUnit 11 test cases covering authentication, Google and Facebook OAuth, Two-Factor Authentication, lease mapping, invoice generation, lease expiry notification, PayMongo webhook processing, stall reservation submission, document repository access control, advanced report generation, and support ticket management. End-to-end system testing was conducted to simulate the complete operational lifecycle from public stall reservation through lease termination.


Conclusions

Based on the results of the development, testing, and implementation of the Web Based Stall Management System for Center Point, the following conclusions are drawn:

1. The system successfully replaced the manual stall management processes at Center Point with a centralized, automated digital platform. The integration of a relational MySQL database with Laravel Eloquent ORM and enforced referential integrity eliminated the data inconsistencies and record duplication associated with the previous manual logbook approach.

2. The interactive Livewire-powered floor plan grid editor provided administrators with an intuitive and efficient tool for configuring and updating stall layouts per floor level, significantly reducing the time required to manage physical stall assignments and visualize floor occupancy.

3. The automated monthly invoicing system, driven by a scheduled Laravel Artisan command, reliably generated rental invoices for all active leases without duplication and dispatched multi-stage payment reminders through email and Semaphore SMS. This automation reduced the administrative workload associated with manual invoice preparation and follow-up, and improved payment compliance among tenants.

4. The lease expiry automated notification system, powered by a dedicated daily Artisan command, proactively informed both administrators and tenants of approaching lease contract expiration dates at 60 and 30 days in advance via email and SMS. This eliminated the risk of unmanaged contract expirations and gave tenants sufficient notice to submit renewal requests.

5. The integration of the PayMongo payment gateway with support for GCash, Maya, credit cards, and debit cards provided tenants with a convenient, secure, and flexible online payment experience. The asynchronous webhook verification mechanism ensured accurate and real-time invoice status updates without requiring manual payment confirmation by administrators.

6. The PDF receipt generation module using Dompdf produced professional-quality official receipts featuring embedded QR codes for online authenticity verification, replacing the need for handwritten receipts and providing tenants with a verifiable and portable record of their payments.

7. The tenant document repository provided tenants with secure, on-demand, self-service access to all their personal documents including lease contracts, payment receipts, and billing statements through IDOR-protected download endpoints. This significantly reduced document-related inquiries to administrative staff and improved tenant autonomy.

8. The public stall reservation portal provided prospective tenants with a formal, accessible, and convenient online channel for submitting rental applications without requiring an in-person office visit. The stall application management interface enabled administrators to systematically review and process applications, replacing informal and unstructured inquiry handling.

9. The advanced financial reporting and analytics module, featuring interactive Chart.js revenue and occupancy charts, an overdue payment aging table, and exportable CSV and PDF summaries, provided management with a comprehensive financial oversight tool that was entirely absent from the previous manual system. This supports evidence-based operational and strategic decision-making.

10. The optional Two-Factor Authentication mechanism for administrator and staff accounts, implemented via SMS OTP dispatched through the Semaphore gateway, significantly strengthened the security posture of the system by adding a verifiable second authentication layer to protect sensitive financial data, tenant records, and administrative functions.

11. The tenant self-service portal, combined with the document repository and in-app notification center, provided transparent, real-time, on-demand access to all relevant tenant information, significantly reducing repetitive inquiries to administrative staff and improving overall tenant satisfaction.

12. The support ticket and real-time chat module replaced informal communication channels with a documented, threaded helpdesk system. All tenant concerns, maintenance requests, and inquiries were formally recorded, tracked, and resolved within the platform.

13. The audit log system maintained a complete and tamper-evident record of all significant administrative actions, supporting internal accountability, security monitoring, and operational review.

14. The automated PHPUnit test suite confirmed that all critical system functions — including authentication, social login, Two-Factor Authentication, lease expiry notifications, invoice generation, PayMongo webhook processing, document repository access control, stall reservation submission, and ticket management — operated correctly and securely under both normal and adversarial conditions.

15. The three-tier role-based access control system (Administrator, Staff, Tenant, and unauthenticated Guest) enforced through middleware effectively restricted access to system features based on user responsibilities, preventing unauthorized actions and maintaining system security.

Overall, the Web Based Stall Management System for Center Point successfully achieved all of its stated general and specific objectives. The system demonstrated that a well-designed, comprehensive, and integrated web application built on a modern PHP framework can effectively address a broad range of operational, financial, security, and communication challenges in a local commercial stall management environment, improving efficiency, accuracy, transparency, security, and service quality across all stakeholder groups.


Recommendations

Based on the findings, conclusions, and limitations of the study, the researchers offer the following recommendations for future development and enhancement of the Web Based Stall Management System for Center Point:

1. Mobile Application Development — It is recommended that a dedicated mobile application for Android and iOS platforms be developed as a future enhancement. A native mobile app would provide tenants with push notification support for billing reminders, payment confirmations, lease expiry alerts, and ticket replies, further improving accessibility and user engagement beyond the current web-based interface.

2. Multi-Branch or Multi-Location Scalability — The system architecture should be evaluated and enhanced to support multi-branch configurations, enabling the same platform to manage stall operations across multiple separate commercial locations or buildings under the same administration, with centralized reporting and management.

3. Integration with Local Government Regulatory Systems — Future versions of the system could explore integration with local government units or business permit processing systems to automate the generation and tracking of business permit-related documents for tenant businesses operating within Center Point. This would further reduce the administrative burden on both management and tenants during annual business permit renewal periods.

4. Comprehensive User Acceptance Testing with a Larger Sample — It is recommended that a formal user acceptance testing (UAT) study be conducted with a broader and more representative sample of actual administrators, staff, and tenants at Center Point. Quantitative evaluation using a validated usability instrument such as the System Usability Scale (SUS) would provide statistically significant evidence of the system's usability, effectiveness, and user satisfaction, contributing to the existing body of literature on digital stall management systems in the Philippine context.
