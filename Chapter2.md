CHAPTER II
REVIEW OF RELATED LITERATURE


This chapter presents the related literature and studies that support the development of the Web Based Stall Management System for Center Point. The review focuses on rental and property management systems, automated billing, online payment processing, tenant self-service portals, real-time communication systems, digital document repositories, and security authentication mechanisms. These studies establish the theoretical and empirical foundations that justify the transition from manual processes to digital platforms in improving operational efficiency, transparency, security, and service quality.

The reviewed studies are categorized into international and local studies, followed by a synthesis that identifies research gaps addressed by the system.


International Related Studies

Magno, Pelayo, and Soberano (2024) developed an apartment rental management system aimed at improving transaction tracking and overall task organization within rental operations. The study emphasized that traditional rental practices often rely on manual record-keeping, which can result in inaccurate data, missed payments, and inefficient workflows. Their findings demonstrated that the implementation of a digital system significantly enhanced data accuracy, minimized operational errors, and improved overall efficiency. In addition, the system enabled real-time monitoring of billing and payment transactions, which helped reduce disputes between tenants and property managers.

However, the study primarily focuses on transaction automation and data management without extensive discussion on user-centered workflow design or adaptability to different types of rental environments. This indicates the need for a system that not only automates billing and record-keeping but also ensures usability and flexibility for specific business contexts. This supports the development of the proposed system, which integrates automated monthly invoicing, multi-stage payment reminders, advanced financial analytics, and centralized data management within a structured and user-friendly platform built on Laravel 13.x.

Buradkar et al. (2022) designed a property rental management system that transforms traditional rental operations into a digital environment. The system incorporates both a tenant-facing application and a backend database to manage rental transactions and records efficiently. The study highlighted the importance of integrating frontend and backend components to ensure seamless data access and processing. It also emphasized the role of tenant portals in improving accessibility, transparency, and user engagement.

Despite these contributions, the study places greater emphasis on system architecture and integration rather than on workflow efficiency and role-based interaction within the organization. This suggests the need for a more comprehensive system that not only connects system components but also supports structured operational processes with clearly defined user roles. This supports the proposed system's tenant portal feature and document repository architecture, which together enable users to access information and manage operations while maintaining a structured role-based workflow.

A study published in the International Journal of Creative Research Thoughts (2024) examined rental house management systems and identified the limitations of traditional manual processes. The study noted that manual systems are often inefficient, prone to errors, and lack effective communication mechanisms between stakeholders. It emphasized that modern rental management platforms should incorporate automated billing and payment processing, maintenance request tracking, and integrated communication systems.

While the study clearly identifies essential system features, it focuses more on general system capabilities rather than on implementation strategies tailored to specific business environments. These findings support the proposed system, particularly its automated billing functionality, Artisan-scheduled invoice generation, lease expiry notification automation, and support ticket module, which together enhance communication efficiency and service management.

According to a global market report (2024), digital payment systems in rental management allow tenants to complete transactions electronically, access payment history, and receive notifications, while administrators can efficiently monitor transactions and generate reports. The study further emphasized that automation in payment processing reduces administrative workload and minimizes human errors, making it an essential component of modern rental systems.

However, the report focuses primarily on payment technologies and does not fully address how these systems integrate with broader management workflows. This indicates the need for a more unified platform that combines payment processing with other operational functions. This supports the integration of the PayMongo payment gateway and the advanced financial reporting and analytics module in the proposed system, which together enhance convenience, financial visibility, and operational decision-making.

Research on automated property management systems highlights that digital platforms enable real-time updates, recurring billing, and tenant access to financial records through dedicated portals. These systems improve transparency and encourage timely payments by allowing tenants to monitor their accounts and settle balances conveniently.

Despite these advantages, the discussion is largely centered on financial transparency and automation, with limited attention to internal coordination, communication processes, and document accessibility. This aligns with the proposed system's tenant portal, automated invoicing, document repository, and lease expiry notification features, which work together to improve transparency, reduce reliance on manual verification, and ensure tenants are proactively informed of important account events.


Local Related Studies

Magno et al. (2024), conducted in Cebu, Philippines, examined the implementation of a digital apartment rental management system and its impact on operational efficiency. The study revealed that manual systems often lead to delays, inconsistencies, and inaccuracies in billing and tenant management. In contrast, the adoption of an automated system significantly improved data accuracy, streamlined processes, and enhanced overall performance. The study also found that tenants responded positively to the increased transparency and accessibility that a digital platform provides.

However, the study focuses mainly on system effectiveness and outcomes rather than on detailed system design, user interaction patterns, and security mechanisms for privileged user accounts. These findings support the development of the proposed system, particularly in addressing similar operational challenges in local commercial establishments such as Center Point in Pagadian City, Zamboanga del Sur.

Recent studies on rental management systems in the Philippine context indicate a growing trend toward automation, cloud-based solutions, and integrated communication tools to improve efficiency and scalability. While these studies highlight technological advancements, they provide limited discussion on how such systems can be adapted to small-scale or localized business environments such as commercial stall arcades. This supports the proposed system's use of the Laravel framework with Eloquent ORM and a MySQL relational database, which provide a scalable yet maintainable architecture for local deployment.

Studies on automated rent collection systems emphasize that digital payment solutions eliminate manual processes, reduce delays, and improve tenant satisfaction. The availability of local digital payment options such as GCash and Maya further reinforces the viability of integrating online payment gateways in systems targeting Filipino users. However, these studies often focus on payment systems as standalone solutions rather than as part of an integrated management platform. This reinforces the importance of integrating PayMongo within the proposed system as part of a holistic management platform that also includes advanced financial reporting, tenant document access, and automated lease management.

Research in the area of authentication security and multi-factor authentication systems indicates that implementing additional verification layers such as OTP-based Two-Factor Authentication significantly reduces the risk of unauthorized access to sensitive administrative systems. This is particularly relevant for web-based platforms handling financial data, tenant records, and contractual documents. This supports the implementation of optional SMS OTP-based Two-Factor Authentication for administrator and staff accounts within the proposed system.


Synthesis

The reviewed international and local studies consistently demonstrate that digital rental and property management systems significantly improve operational efficiency, data accuracy, billing transparency, and communication between tenants and administrators. Studies by Magno et al. (2024), Buradkar et al. (2022), and other related research highlight the importance of automation, centralized data management, and system integration in modern rental operations. Similarly, research on digital payment systems, automated billing, multi-factor authentication, and tenant portal design emphasizes the role of technology in reducing manual errors, accelerating payment processing, improving account security, and enhancing user experience.

However, the literature also reveals several important gaps. Many existing systems focus primarily on automation, payment processing, and general system integration, with limited emphasis on structured tenant document repositories, automated lease expiry notification pipelines, comprehensive financial analytics with exportable reports, public-facing reservation portals for prospective tenants, and layered security mechanisms such as Two-Factor Authentication for privileged user accounts. There is also insufficient integration of social authentication options beyond Google OAuth, despite the widespread use of Facebook among the target user base in the Philippine context.

These gaps justify the development of the Web Based Stall Management System for Center Point. The system addresses all identified gaps by providing a comprehensive, user-centered digital platform that integrates: stall and floor plan management; tenant self-service access through a dedicated portal with a document repository; automated monthly invoice generation via Laravel Artisan scheduler with multi-stage email and Semaphore SMS reminders; automated lease expiry notifications at 60 and 30 days in advance; secure online payment processing through the PayMongo gateway with GCash, Maya, and card support; official PDF receipts with QR code authenticity verification using Dompdf; a public stall reservation portal for prospective tenants; advanced financial reporting with interactive Chart.js visualizations and exportable CSV and PDF summaries; a structured support ticket system with real-time threaded chat and file attachments; a utility billing module; a full audit log system for administrative accountability; Google OAuth and Facebook OAuth social logins; and optional SMS OTP-based Two-Factor Authentication for administrator and staff accounts. By combining these features within a scalable Laravel 13.x and Livewire 3 architecture with PHPUnit-tested components, the proposed system extends existing studies and delivers a comprehensive, maintainable solution tailored to the operational needs of Center Point.


References

Articles

International Journal of Creative Research Thoughts. (2024). Rental house management system study. Retrieved from https://www.ijcrt.org/papers/IJCRT24A5568.pdf

Market Research Reports. (2024). Global online rent payment system market report. Retrieved from https://www.marketresearchreports.com/mrrpb5/global-online-rent-payment-system-market-research-report-2024

PMarket Research. (2025). Rental payment software market trends. Retrieved from https://pmarketresearch.com/worldwide-rental-software-market-research-2024-by-type-application-participants-and-countries-forecast-to-2030/

Archive Market Research. (2026). Rental management system industry analysis. Retrieved from https://www.archivemarketresearch.com/reports/rental-management-system-28796

Better HouseKeeper. (2025). Automated rent collection systems. Retrieved from https://betterhousekeeper.com/2025/07/01/why-automated-rent-collection-is-the-future-of-rental-property-management

Research on Property Management Systems. (2023). Tenant portal and automation study. Retrieved from https://www.researchgate.net/publication/366163745_Property_Management_-_An_Industry_of_Fragmented_Technology_Solutions


Journals

Magno, J. M., Pelayo, R. S., & Soberano, K. T. (2024). Apartment rental management system for real-time transaction and task organization. International Journal of Multidisciplinary Research and Analysis. Retrieved from https://www.researchgate.net/publication/382384541_Apartment_Rental_Management_System_for_Real-Time_Transaction_and_Task_Organization

Buradkar, K., Kori, S., Ruikar, S., & Galfat, V. (2022). Property rental management system. International Journal of Computer Science and Mobile Computing. Retrieved from https://www.researchgate.net/publication/365875447_Property_Rental_Management_System
