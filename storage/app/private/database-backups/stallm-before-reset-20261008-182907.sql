-- Backup of stallm before user-requested reset
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `stall_location_id` bigint unsigned DEFAULT NULL,
  `emailed` tinyint(1) NOT NULL DEFAULT '0',
  `posted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_stall_location_id_foreign` (`stall_location_id`),
  KEY `announcements_posted_by_foreign` (`posted_by`),
  CONSTRAINT `announcements_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `announcements_stall_location_id_foreign` FOREIGN KEY (`stall_location_id`) REFERENCES `stall_locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_created_at_index` (`created_at`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('1','10','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 10:21:27','2026-10-08 10:21:27');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('2','1','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 10:38:48','2026-10-08 10:38:48');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('3','7','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 10:41:54','2026-10-08 10:41:54');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('4','1','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 11:03:22','2026-10-08 11:03:22');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('5','7','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 11:06:15','2026-10-08 11:06:15');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('6','1','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 11:28:12','2026-10-08 11:28:12');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('7','7','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 11:32:01','2026-10-08 11:32:01');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('8','7','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 11:40:30','2026-10-08 11:40:30');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('9','1','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 13:29:50','2026-10-08 13:29:50');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('10','7','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 13:33:54','2026-10-08 13:33:54');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('11','7','Renewal requested for Lease #1 until 2027-05-21','StallMapping','1','','127.0.0.1','2026-10-08 13:34:39','2026-10-08 13:34:39');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('12','1','Rejected renewal for Lease #1','StallMapping','1','test','127.0.0.1','2026-10-08 13:36:34','2026-10-08 13:36:34');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('13','7','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 13:47:51','2026-10-08 13:47:51');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('14','7','Tenant updated photo for Stall G-01','Stall','1',NULL,'127.0.0.1','2026-10-08 14:44:20','2026-10-08 14:44:20');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('15','7','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 14:46:08','2026-10-08 14:46:08');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('16','1','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 15:16:57','2026-10-08 15:16:57');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('17','1','Created database backup backup-2026-10-08-175810.sql.enc','Backup',NULL,NULL,'127.0.0.1','2026-10-08 17:58:10','2026-10-08 17:58:10');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('18','1','Downloaded database backup backup-2026-10-08-175810.sql.enc','Backup',NULL,NULL,'127.0.0.1','2026-10-08 17:58:14','2026-10-08 17:58:14');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('19','1','Security: login succeeded','Security',NULL,'method: password','127.0.0.1','2026-10-08 18:03:57','2026-10-08 18:03:57');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('20','1','Approved stall application from Carmen Villanueva (tenant account created)','StallApplication','1',NULL,'127.0.0.1','2026-10-08 18:05:26','2026-10-08 18:05:26');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('21','1','Assigned Stall #24 to User #11 (Pending Initial Payment)','StallMapping','5',NULL,'127.0.0.1','2026-10-08 18:05:41','2026-10-08 18:05:41');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('22','11','Security: login failed','Security',NULL,'email: carmen.villanueva@example.com','127.0.0.1','2026-10-08 18:06:23','2026-10-08 18:06:23');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('23','11','Security: login failed','Security',NULL,'email: carmen.villanueva@example.com','127.0.0.1','2026-10-08 18:06:32','2026-10-08 18:06:32');
INSERT INTO `audit_logs` (`id`,`user_id`,`action`,`entity_type`,`entity_id`,`details`,`ip_address`,`created_at`,`updated_at`) VALUES ('24','1','Updated System Settings & Branding','Setting','1',NULL,'127.0.0.1','2026-10-08 18:27:55','2026-10-08 18:27:55');

DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `item_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rent',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('1','1','Monthly Stall Rental - September 2026','6500.00','rent','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('2','2','Monthly Stall Rental - August 2026','6500.00','rent','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('3','3','Monthly Stall Rental - July 2026','6500.00','rent','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('4','4','Monthly Stall Rental - June 2026','6500.00','rent','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('5','5','Monthly Stall Rental - May 2026','6500.00','rent','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('6','6','Monthly Stall Rental - April 2026','6500.00','rent','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('7','7','Monthly Stall Rental - September 2026','6500.00','rent','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('8','8','Monthly Stall Rental - August 2026','6500.00','rent','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('9','9','Monthly Stall Rental - July 2026','6500.00','rent','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('10','10','Monthly Stall Rental - June 2026','6500.00','rent','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('11','11','Monthly Stall Rental - May 2026','6500.00','rent','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('12','12','Monthly Stall Rental - April 2026','6500.00','rent','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('13','13','Monthly Stall Rental - September 2026','6500.00','rent','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('14','14','Monthly Stall Rental - August 2026','6500.00','rent','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('15','15','Monthly Stall Rental - July 2026','6500.00','rent','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('16','16','Monthly Stall Rental - June 2026','6500.00','rent','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('17','17','Monthly Stall Rental - May 2026','6500.00','rent','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('18','18','Monthly Stall Rental - April 2026','6500.00','rent','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('19','19','Monthly Stall Rental - September 2026','6500.00','rent','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('20','20','Monthly Stall Rental - August 2026','6500.00','rent','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('21','21','Monthly Stall Rental - July 2026','6500.00','rent','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('22','22','Monthly Stall Rental - June 2026','6500.00','rent','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('23','23','Monthly Stall Rental - May 2026','6500.00','rent','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('24','24','Monthly Stall Rental - April 2026','6500.00','rent','2026-10-06 23:38:43','2026-10-06 23:38:43');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('25','25','Monthly Stall Rental - October 2026','6500.00','rent','2026-10-06 23:38:43','2026-10-06 23:38:43');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('26','26','Monthly Stall Rental - October 2026','6500.00','rent','2026-10-06 23:38:43','2026-10-06 23:38:43');
INSERT INTO `invoice_items` (`id`,`invoice_id`,`description`,`amount`,`item_type`,`created_at`,`updated_at`) VALUES ('27','27','Initial Stall Lease Deposit & 1st Month Rent - Stall #F-02','5000.00','rent','2026-10-08 18:05:41','2026-10-08 18:05:41');

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verification_token` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `stall_id` bigint unsigned NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','cancelled','overdue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `due_date` date NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `overdue_notified_at` timestamp NULL DEFAULT NULL,
  `billing_period` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  UNIQUE KEY `invoices_verification_token_unique` (`verification_token`),
  UNIQUE KEY `unique_stall_billing_period` (`stall_id`,`billing_period`),
  KEY `invoices_user_id_status_index` (`user_id`,`status`),
  KEY `invoices_due_date_index` (`due_date`),
  CONSTRAINT `invoices_stall_id_foreign` FOREIGN KEY (`stall_id`) REFERENCES `stalls` (`id`),
  CONSTRAINT `invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('1','INV-202609-0001','f05aec9d-58ad-4e44-a587-f40ec26194b8','7','1','6500.00','paid','2026-09-06','2026-09-04 10:30:00',NULL,'2026-09','Monthly Stall Rental - September 2026','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('2','INV-202608-0001','ed0e28be-32be-4e28-8490-ce976bd7f183','7','1','6500.00','paid','2026-08-06','2026-08-04 10:30:00',NULL,'2026-08','Monthly Stall Rental - August 2026','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('3','INV-202607-0001','b941daf3-5208-4821-9c8f-aa730d09fbd0','7','1','6500.00','paid','2026-07-06','2026-07-04 10:30:00',NULL,'2026-07','Monthly Stall Rental - July 2026','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('4','INV-202606-0001','93cc7646-a9e9-448d-b095-51b3dcd39a4c','7','1','6500.00','paid','2026-06-06','2026-06-04 10:30:00',NULL,'2026-06','Monthly Stall Rental - June 2026','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('5','INV-202605-0001','bc3cbfad-07ab-4a23-935e-264f82af2901','7','1','6500.00','paid','2026-05-06','2026-05-04 10:30:00',NULL,'2026-05','Monthly Stall Rental - May 2026','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('6','INV-202604-0001','bd754018-b91e-42e8-9c0f-2251fe9aa8b6','7','1','6500.00','paid','2026-04-06','2026-04-04 10:30:00',NULL,'2026-04','Monthly Stall Rental - April 2026','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('7','INV-202609-0002','8b3883bc-a480-43e2-9aa7-3be56e9c28a4','8','2','6500.00','paid','2026-09-06','2026-09-04 10:30:00',NULL,'2026-09','Monthly Stall Rental - September 2026','2026-10-06 23:38:40','2026-10-06 23:38:41');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('8','INV-202608-0002','26a39f43-95a8-4741-99a3-afbe5cf80562','8','2','6500.00','paid','2026-08-06','2026-08-04 10:30:00',NULL,'2026-08','Monthly Stall Rental - August 2026','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('9','INV-202607-0002','d4731794-cb90-448c-991c-95860fcbd1ca','8','2','6500.00','paid','2026-07-06','2026-07-04 10:30:00',NULL,'2026-07','Monthly Stall Rental - July 2026','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('10','INV-202606-0002','8b7874dc-f632-4a58-a99b-82b4311b3490','8','2','6500.00','paid','2026-06-06','2026-06-04 10:30:00',NULL,'2026-06','Monthly Stall Rental - June 2026','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('11','INV-202605-0002','292fcf17-7d68-4634-b638-486fd2e002dc','8','2','6500.00','paid','2026-05-06','2026-05-04 10:30:00',NULL,'2026-05','Monthly Stall Rental - May 2026','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('12','INV-202604-0002','4d6eb7e8-81f6-4835-94a4-7b3e0be13c5b','8','2','6500.00','paid','2026-04-06','2026-04-04 10:30:00',NULL,'2026-04','Monthly Stall Rental - April 2026','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('13','INV-202609-0003','96f1e3dd-5181-4142-9930-1427a6375fc3','9','3','6500.00','paid','2026-09-06','2026-09-04 10:30:00',NULL,'2026-09','Monthly Stall Rental - September 2026','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('14','INV-202608-0003','711d1862-a387-4710-97a6-ed3bdf86cf41','9','3','6500.00','paid','2026-08-06','2026-08-04 10:30:00',NULL,'2026-08','Monthly Stall Rental - August 2026','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('15','INV-202607-0003','f7f52715-3aae-41de-942a-b3cad8cba432','9','3','6500.00','paid','2026-07-06','2026-07-04 10:30:00',NULL,'2026-07','Monthly Stall Rental - July 2026','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('16','INV-202606-0003','a4a77fb7-e813-42ee-ad1b-e5638b37690c','9','3','6500.00','paid','2026-06-06','2026-06-04 10:30:00',NULL,'2026-06','Monthly Stall Rental - June 2026','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('17','INV-202605-0003','df1ac3aa-8492-42b9-9ee5-6ee9020d9631','9','3','6500.00','paid','2026-05-06','2026-05-04 10:30:00',NULL,'2026-05','Monthly Stall Rental - May 2026','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('18','INV-202604-0003','b9f0b957-5e74-4109-860b-bc5ea9002afd','9','3','6500.00','paid','2026-04-06','2026-04-04 10:30:00',NULL,'2026-04','Monthly Stall Rental - April 2026','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('19','INV-202609-0004','7e07a93c-63db-4255-a737-12bab1dd98f2','10','4','6500.00','paid','2026-09-06','2026-09-04 10:30:00',NULL,'2026-09','Monthly Stall Rental - September 2026','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('20','INV-202608-0004','886282a3-651e-40d6-9733-0daa843d8a35','10','4','6500.00','paid','2026-08-06','2026-08-04 10:30:00',NULL,'2026-08','Monthly Stall Rental - August 2026','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('21','INV-202607-0004','ede9f795-b10a-4bcf-a2ee-cd67629bc5a3','10','4','6500.00','paid','2026-07-06','2026-07-04 10:30:00',NULL,'2026-07','Monthly Stall Rental - July 2026','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('22','INV-202606-0004','80d2753d-358f-4ceb-911b-c70c840d18b2','10','4','6500.00','paid','2026-06-06','2026-06-04 10:30:00',NULL,'2026-06','Monthly Stall Rental - June 2026','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('23','INV-202605-0004','a10305b6-2eda-4277-b335-6f09f5d2d9b3','10','4','6500.00','paid','2026-05-06','2026-05-04 10:30:00',NULL,'2026-05','Monthly Stall Rental - May 2026','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('24','INV-202604-0004','6edfcaea-a251-4e88-ad63-d9bd148705e2','10','4','6500.00','paid','2026-04-06','2026-04-04 10:30:00',NULL,'2026-04','Monthly Stall Rental - April 2026','2026-10-06 23:38:42','2026-10-06 23:38:43');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('25','INV-202610-0003','d330bd75-4f86-4dcf-b7cd-c02fc83d2909','9','3','6500.00','pending','2026-10-14',NULL,NULL,'2026-10','Monthly Stall Rental - October 2026','2026-10-06 23:38:43','2026-10-07 02:30:15');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('26','INV-202610-0004','bcb65aa3-6efd-4a49-b95a-ff84a5f6d29e','10','4','6500.00','pending','2026-09-27',NULL,NULL,'2026-10','Monthly Stall Rental - October 2026','2026-10-06 23:38:43','2026-10-07 02:30:15');
INSERT INTO `invoices` (`id`,`invoice_number`,`verification_token`,`user_id`,`stall_id`,`total_amount`,`status`,`due_date`,`paid_at`,`overdue_notified_at`,`billing_period`,`notes`,`created_at`,`updated_at`) VALUES ('27','INV-202610-0024','19f83fdd-e30f-4742-a750-d8528403f745','11','24','5000.00','pending','2026-10-15',NULL,NULL,'2026-10','Initial Stall Lease Deposit & 1st Month Rent - Stall #F-02','2026-10-08 18:05:41','2026-10-08 18:05:41');

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('1','2026_01_01_000001_create_users_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('2','2026_01_01_000002_create_stall_locations_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('3','2026_01_01_000003_create_stalls_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('4','2026_01_01_000004_create_stall_mappings_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('5','2026_01_01_000005_create_payments_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('6','2026_01_01_000006_create_tickets_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('7','2026_01_01_000007_create_ticket_messages_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('8','2026_01_01_000008_create_ticket_participants_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('9','2026_01_01_000009_create_settings_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('10','2026_01_01_000010_create_audit_logs_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('11','2026_01_01_000011_create_notifications_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('12','2026_01_01_000012_create_utility_readings_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('13','2026_01_01_000013_create_password_reset_tokens_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('14','2026_01_01_000014_create_stall_applications_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('15','2026_01_01_000015_create_two_factor_codes_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('16','2026_01_01_000016_add_two_factor_columns_to_users_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('17','2026_07_25_000001_create_invoices_items_and_transactions_tables','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('18','2026_07_25_000002_add_assigned_to_to_tickets_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('19','2026_07_26_000001_add_missing_indexes','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('20','2026_07_26_000002_migrate_payments_to_invoices','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('21','2026_07_26_000003_alter_two_factor_codes_code_column','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('22','2026_07_26_000004_add_user_id_and_rejection_reason_to_stall_applications_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('23','2026_07_26_000005_make_stall_id_nullable_in_tickets_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('24','2026_07_26_125559_create_jobs_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('25','2026_08_24_111131_remove_staff_role_from_users_table','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('26','2026_08_30_000001_update_lease_and_stall_statuses_for_payment_flow','1');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('27','2026_10_06_000001_drop_utility_readings_table','2');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('28','2026_10_06_000002_add_verification_token_to_invoices_table','3');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('29','2026_10_06_000003_add_renewal_fields_to_stall_mappings_table','4');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('30','2026_10_06_000004_add_overdue_notified_at_to_invoices_table','5');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('31','2026_10_07_000001_convert_legacy_overdue_invoice_status','6');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('32','2026_10_07_000002_add_termination_fields_to_stall_mappings_table','7');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('33','2026_10_07_000003_replace_utility_clause_in_terms_setting','7');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('34','2026_10_07_000004_create_announcements_table','7');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('35','2026_10_07_000005_add_deactivated_at_to_users_table','7');
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES ('36','2026_10_07_000006_create_stall_waitlist_table','7');

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`),
  KEY `idx_notifications_user_read` (`user_id`,`is_read`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `notifications` (`id`,`user_id`,`type`,`title`,`message`,`link`,`is_read`,`created_at`,`updated_at`) VALUES ('1','1','ticket','New Ticket #TKT-00003','Andres Bonifacio Lim submitted a new ticket: help me','http://127.0.0.1:8000/admin/tickets','1','2026-10-08 10:26:02','2026-10-08 15:18:46');
INSERT INTO `notifications` (`id`,`user_id`,`type`,`title`,`message`,`link`,`is_read`,`created_at`,`updated_at`) VALUES ('2','1','renewal','Lease renewal requested','Juan Dela Cruz asked to renew Stall G-01 until May 21, 2027.','http://127.0.0.1:8000/admin/mappings','1','2026-10-08 13:34:39','2026-10-08 15:18:46');
INSERT INTO `notifications` (`id`,`user_id`,`type`,`title`,`message`,`link`,`is_read`,`created_at`,`updated_at`) VALUES ('3','7','renewal','Lease renewal not approved','Your lease renewal request for Stall G-01 was not approved. Reason: test','http://127.0.0.1:8000/user/leases','0','2026-10-08 13:36:34','2026-10-08 13:36:34');

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payment_transactions`;
CREATE TABLE `payment_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `gateway` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paymongo',
  `gateway_transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','successful','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payload` json DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_transactions_invoice_id_foreign` (`invoice_id`),
  KEY `payment_transactions_gateway_transaction_id_index` (`gateway_transaction_id`),
  KEY `payment_transactions_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `payment_transactions_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `payment_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('1','1','7','office_cash','DEMO-INV-202609-0001','6500.00','successful',NULL,'2026-09-04 10:30:00','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('2','2','7','paymongo','DEMO-INV-202608-0001','6500.00','successful',NULL,'2026-08-04 10:30:00','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('3','3','7','office_cash','DEMO-INV-202607-0001','6500.00','successful',NULL,'2026-07-04 10:30:00','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('4','4','7','paymongo','DEMO-INV-202606-0001','6500.00','successful',NULL,'2026-06-04 10:30:00','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('5','5','7','office_cash','DEMO-INV-202605-0001','6500.00','successful',NULL,'2026-05-04 10:30:00','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('6','6','7','paymongo','DEMO-INV-202604-0001','6500.00','successful',NULL,'2026-04-04 10:30:00','2026-10-06 23:38:40','2026-10-06 23:38:40');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('7','7','8','office_cash','DEMO-INV-202609-0002','6500.00','successful',NULL,'2026-09-04 10:30:00','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('8','8','8','paymongo','DEMO-INV-202608-0002','6500.00','successful',NULL,'2026-08-04 10:30:00','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('9','9','8','office_cash','DEMO-INV-202607-0002','6500.00','successful',NULL,'2026-07-04 10:30:00','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('10','10','8','paymongo','DEMO-INV-202606-0002','6500.00','successful',NULL,'2026-06-04 10:30:00','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('11','11','8','office_cash','DEMO-INV-202605-0002','6500.00','successful',NULL,'2026-05-04 10:30:00','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('12','12','8','paymongo','DEMO-INV-202604-0002','6500.00','successful',NULL,'2026-04-04 10:30:00','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('13','13','9','office_cash','DEMO-INV-202609-0003','6500.00','successful',NULL,'2026-09-04 10:30:00','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('14','14','9','paymongo','DEMO-INV-202608-0003','6500.00','successful',NULL,'2026-08-04 10:30:00','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('15','15','9','office_cash','DEMO-INV-202607-0003','6500.00','successful',NULL,'2026-07-04 10:30:00','2026-10-06 23:38:41','2026-10-06 23:38:41');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('16','16','9','paymongo','DEMO-INV-202606-0003','6500.00','successful',NULL,'2026-06-04 10:30:00','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('17','17','9','office_cash','DEMO-INV-202605-0003','6500.00','successful',NULL,'2026-05-04 10:30:00','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('18','18','9','paymongo','DEMO-INV-202604-0003','6500.00','successful',NULL,'2026-04-04 10:30:00','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('19','19','10','office_cash','DEMO-INV-202609-0004','6500.00','successful',NULL,'2026-09-04 10:30:00','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('20','20','10','paymongo','DEMO-INV-202608-0004','6500.00','successful',NULL,'2026-08-04 10:30:00','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('21','21','10','office_cash','DEMO-INV-202607-0004','6500.00','successful',NULL,'2026-07-04 10:30:00','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('22','22','10','paymongo','DEMO-INV-202606-0004','6500.00','successful',NULL,'2026-06-04 10:30:00','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('23','23','10','office_cash','DEMO-INV-202605-0004','6500.00','successful',NULL,'2026-05-04 10:30:00','2026-10-06 23:38:42','2026-10-06 23:38:42');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('24','24','10','paymongo','DEMO-INV-202604-0004','6500.00','successful',NULL,'2026-04-04 10:30:00','2026-10-06 23:38:43','2026-10-06 23:38:43');
INSERT INTO `payment_transactions` (`id`,`invoice_id`,`user_id`,`gateway`,`gateway_transaction_id`,`amount`,`status`,`payload`,`paid_at`,`created_at`,`updated_at`) VALUES ('25','26','10','paymongo','cs_d481e574eb5035bf70946062','6500.00','pending','{\"id\": \"cs_d481e574eb5035bf70946062\", \"type\": \"checkout_session\", \"attributes\": {\"status\": \"active\", \"billing\": {\"name\": null, \"email\": null, \"phone\": null, \"address\": {\"city\": null, \"line1\": null, \"line2\": null, \"state\": null, \"country\": null, \"postal_code\": null}}, \"livemode\": false, \"merchant\": \"MARK GUINANDAL SAGGA\", \"metadata\": null, \"payments\": [], \"cancel_url\": \"http://127.0.0.1:8000/user/payments?cancel=1\", \"client_key\": \"cs_d481e574eb5035bf70946062_client_96e9f57aa3c8e245ad74d81d\", \"collection\": {\"customer_info\": {\"name\": {\"state\": \"auto\"}, \"email\": {\"state\": \"auto\"}, \"address\": {\"state\": \"auto\"}, \"mobile_phone\": {\"state\": \"auto\"}}}, \"created_at\": 1791426215, \"line_items\": [{\"name\": \"Stall G-04 Invoice (#INV-202610-0004)\", \"amount\": 650000, \"images\": [], \"currency\": \"PHP\", \"quantity\": 1, \"description\": \"Monthly Stall Rental - October 2026\"}], \"public_key\": \"pk_test_uSRQksFaXXgkunqJcgJFdqRv\", \"updated_at\": 1791426215, \"customer_id\": null, \"description\": \"STALLM Invoices Payment\", \"success_url\": \"http://127.0.0.1:8000/user/payments?success=1\", \"checkout_url\": \"https://checkout.paymongo.com/d481e574eb5035bf70946062\", \"pass_on_fees\": false, \"customer_email\": null, \"payment_intent\": {\"id\": \"pi_BFrkt17WWVM8zTa8Gty3xoxo\", \"type\": \"payment_intent\", \"attributes\": {\"amount\": 650000, \"status\": \"awaiting_payment_method\", \"currency\": \"PHP\", \"livemode\": false, \"metadata\": null, \"payments\": [], \"client_key\": \"pi_BFrkt17WWVM8zTa8Gty3xoxo_client_ri43kknYSFxoYHhD8CXaX5kL\", \"created_at\": 1791426215, \"updated_at\": 1791426215, \"description\": \"STALLM Invoices Payment\", \"next_action\": null, \"capture_type\": \"automatic\", \"original_amount\": 650000, \"last_payment_error\": null, \"setup_future_usage\": null, \"statement_descriptor\": \"MARK GUINANDAL SAGGA\", \"payment_method_allowed\": [\"paymaya\", \"card\", \"gcash\"], \"payment_method_options\": {\"card\": {\"request_three_d_secure\": \"any\"}}}}, \"organization_id\": \"org_pBT2NdFbLHuMbN9EVHs5Sg1M\", \"show_line_items\": true, \"reference_number\": null, \"show_description\": true, \"send_email_receipt\": true, \"payment_method_types\": [\"gcash\", \"paymaya\", \"card\"], \"billing_information_fields_editable\": \"enabled\"}}',NULL,'2026-10-08 10:23:36','2026-10-08 10:23:36');

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_setting_key_unique` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`created_at`,`updated_at`) VALUES ('1','platform_name','Center Point Commercial Complex','2026-08-30 00:50:53','2026-08-30 00:50:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`created_at`,`updated_at`) VALUES ('2','platform_address','Pagadian City, Zamboanga del Sur','2026-08-30 00:50:53','2026-08-30 00:50:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`created_at`,`updated_at`) VALUES ('3','support_email','support@stallm.com','2026-08-30 00:50:53','2026-08-30 00:50:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`created_at`,`updated_at`) VALUES ('4','support_phone','09171112233','2026-08-30 00:50:53','2026-08-30 00:50:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`created_at`,`updated_at`) VALUES ('5','privacy_policy','Center Point Commercial Stall Management Privacy Policy\n\n1. Data Collection\nWe collect tenant names, contact numbers, email addresses, and stall lease information required for property administration.\n\n2. Data Protection\nTenant payment transactions and personal credentials are encrypted using industry-standard protocols.\n\n3. Contact Information\nFor privacy inquiries, contact admin@stallm.com.','2026-08-30 00:50:53','2026-08-30 00:50:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`created_at`,`updated_at`) VALUES ('6','terms_conditions','Center Point Commercial Stall Rental Terms and Conditions\n\n1. Lease Agreement\nTenants agree to comply with monthly rental payment schedules and building floor plan regulations.\n\n2. Payments\nRent is billed monthly. Invoices not paid by their due date are flagged as overdue.\n\n3. Termination\nLease termination or renewal requests must be submitted at least 30 days prior to contract expiration.','2026-08-30 00:50:53','2026-10-08 10:22:41');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`created_at`,`updated_at`) VALUES ('7','landing_bg_image','branding/AdnF935Ylyzea3yPLiy7hyqbbcFFhzlVFZALfnZl.png','2026-10-08 18:27:55','2026-10-08 18:27:55');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`created_at`,`updated_at`) VALUES ('8','sms_enabled','1','2026-10-08 18:27:55','2026-10-08 18:27:55');

DROP TABLE IF EXISTS `stall_applications`;
CREATE TABLE `stall_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `applicant_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `applicant_email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `applicant_phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `business_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `business_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preferred_stall_id` bigint unsigned DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `document` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stall_applications_preferred_stall_id_foreign` (`preferred_stall_id`),
  KEY `stall_applications_reviewed_by_foreign` (`reviewed_by`),
  KEY `stall_applications_status_index` (`status`),
  KEY `stall_applications_user_id_foreign` (`user_id`),
  CONSTRAINT `stall_applications_preferred_stall_id_foreign` FOREIGN KEY (`preferred_stall_id`) REFERENCES `stalls` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stall_applications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stall_applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `stall_applications` (`id`,`user_id`,`applicant_name`,`applicant_email`,`applicant_phone`,`business_name`,`business_type`,`preferred_stall_id`,`message`,`document`,`status`,`rejection_reason`,`reviewed_by`,`reviewed_at`,`created_at`,`updated_at`) VALUES ('1','11','Carmen Villanueva','carmen.villanueva@example.com','09181112222','Carmen\'s Kakanin','Food','24','Interested in a one-year lease.',NULL,'approved',NULL,'1','2026-10-08 18:05:26','2026-10-06 23:38:43','2026-10-08 18:05:26');
INSERT INTO `stall_applications` (`id`,`user_id`,`applicant_name`,`applicant_email`,`applicant_phone`,`business_name`,`business_type`,`preferred_stall_id`,`message`,`document`,`status`,`rejection_reason`,`reviewed_by`,`reviewed_at`,`created_at`,`updated_at`) VALUES ('2',NULL,'Ramon Bautista','ramon.bautista@example.com','09182223333','RB Phone Accessories','Electronics','15','Interested in a one-year lease.',NULL,'pending',NULL,NULL,NULL,'2026-10-06 23:38:43','2026-10-06 23:38:43');
INSERT INTO `stall_applications` (`id`,`user_id`,`applicant_name`,`applicant_email`,`applicant_phone`,`business_name`,`business_type`,`preferred_stall_id`,`message`,`document`,`status`,`rejection_reason`,`reviewed_by`,`reviewed_at`,`created_at`,`updated_at`) VALUES ('3',NULL,'Liza Mercado','liza.mercado@example.com','09183334444','Liza Ukay-Ukay','Apparel','6','Interested in a one-year lease.',NULL,'rejected','This stall is reserved for food and beverage tenants.','1','2026-10-05 02:37:52','2026-10-06 23:38:43','2026-10-07 02:37:52');

DROP TABLE IF EXISTS `stall_locations`;
CREATE TABLE `stall_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `grid_rows` int NOT NULL DEFAULT '6',
  `grid_cols` int NOT NULL DEFAULT '8',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stall_locations_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `stall_locations` (`id`,`name`,`description`,`sort_order`,`grid_rows`,`grid_cols`,`created_at`,`updated_at`) VALUES ('1','Ground Floor','High foot-traffic retail arcade by the main entrance.','1','6','8','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stall_locations` (`id`,`name`,`description`,`sort_order`,`grid_rows`,`grid_cols`,`created_at`,`updated_at`) VALUES ('2','Second Floor','Specialty shops, services, and boutiques.','2','5','6','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stall_locations` (`id`,`name`,`description`,`sort_order`,`grid_rows`,`grid_cols`,`created_at`,`updated_at`) VALUES ('3','Food Court','Food counters with shared dining area.','3','4','5','2026-10-06 23:38:38','2026-10-06 23:38:38');

DROP TABLE IF EXISTS `stall_mappings`;
CREATE TABLE `stall_mappings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `stall_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `payment_status` enum('pending_initial','good_standing','past_due','settled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'good_standing',
  `initial_invoice_id` bigint unsigned DEFAULT NULL,
  `contract_document` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `renewal_status` enum('none','requested','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `requested_end_date` date DEFAULT NULL,
  `renewal_note` text COLLATE utf8mb4_unicode_ci,
  `renewal_rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `termination_reason` text COLLATE utf8mb4_unicode_ci,
  `terminated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stall_mappings_user_id_is_active_index` (`user_id`,`is_active`),
  KEY `stall_mappings_stall_id_is_active_index` (`stall_id`,`is_active`),
  KEY `stall_mappings_initial_invoice_id_foreign` (`initial_invoice_id`),
  CONSTRAINT `stall_mappings_initial_invoice_id_foreign` FOREIGN KEY (`initial_invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stall_mappings_stall_id_foreign` FOREIGN KEY (`stall_id`) REFERENCES `stalls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stall_mappings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `stall_mappings` (`id`,`user_id`,`stall_id`,`start_date`,`end_date`,`is_active`,`payment_status`,`initial_invoice_id`,`contract_document`,`renewal_status`,`requested_end_date`,`renewal_note`,`renewal_rejection_reason`,`created_at`,`updated_at`,`termination_reason`,`terminated_at`) VALUES ('1','7','1','2026-03-01','2026-11-21','1','good_standing',NULL,NULL,'rejected','2027-05-21',NULL,'test','2026-10-06 23:38:40','2026-10-08 13:36:34',NULL,NULL);
INSERT INTO `stall_mappings` (`id`,`user_id`,`stall_id`,`start_date`,`end_date`,`is_active`,`payment_status`,`initial_invoice_id`,`contract_document`,`renewal_status`,`requested_end_date`,`renewal_note`,`renewal_rejection_reason`,`created_at`,`updated_at`,`termination_reason`,`terminated_at`) VALUES ('2','8','2','2026-03-01','2027-02-04','1','good_standing',NULL,NULL,'requested','2028-02-04','Business is growing — we would like to stay another year.',NULL,'2026-10-06 23:38:40','2026-10-07 02:30:15',NULL,NULL);
INSERT INTO `stall_mappings` (`id`,`user_id`,`stall_id`,`start_date`,`end_date`,`is_active`,`payment_status`,`initial_invoice_id`,`contract_document`,`renewal_status`,`requested_end_date`,`renewal_note`,`renewal_rejection_reason`,`created_at`,`updated_at`,`termination_reason`,`terminated_at`) VALUES ('3','9','3','2026-03-01','2027-10-07','1','good_standing',NULL,NULL,'none',NULL,NULL,NULL,'2026-10-06 23:38:40','2026-10-07 02:30:15',NULL,NULL);
INSERT INTO `stall_mappings` (`id`,`user_id`,`stall_id`,`start_date`,`end_date`,`is_active`,`payment_status`,`initial_invoice_id`,`contract_document`,`renewal_status`,`requested_end_date`,`renewal_note`,`renewal_rejection_reason`,`created_at`,`updated_at`,`termination_reason`,`terminated_at`) VALUES ('4','10','4','2026-03-01','2027-04-25','1','good_standing',NULL,NULL,'none',NULL,NULL,NULL,'2026-10-06 23:38:40','2026-10-07 02:30:15',NULL,NULL);
INSERT INTO `stall_mappings` (`id`,`user_id`,`stall_id`,`start_date`,`end_date`,`is_active`,`payment_status`,`initial_invoice_id`,`contract_document`,`renewal_status`,`requested_end_date`,`renewal_note`,`renewal_rejection_reason`,`created_at`,`updated_at`,`termination_reason`,`terminated_at`) VALUES ('5','11','24','2026-10-08','2027-10-08','0','pending_initial','27',NULL,'none',NULL,NULL,NULL,'2026-10-08 18:05:41','2026-10-08 18:05:41',NULL,NULL);

DROP TABLE IF EXISTS `stall_waitlist`;
CREATE TABLE `stall_waitlist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stall_id` bigint unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stall_waitlist_stall_id_email_unique` (`stall_id`,`email`),
  CONSTRAINT `stall_waitlist_stall_id_foreign` FOREIGN KEY (`stall_id`) REFERENCES `stalls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stalls`;
CREATE TABLE `stalls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stall_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `status` enum('available','reserved','occupied','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `price` decimal(10,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grid_row` int DEFAULT NULL,
  `grid_col` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stalls_stall_number_unique` (`stall_number`),
  KEY `stalls_location_id_foreign` (`location_id`),
  CONSTRAINT `stalls_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `stall_locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('1','G-01','1','occupied','6500.00','Ground Floor unit G-01','stalls/stall_8hARc3G1j50wqxQiQw8h6cmLnyw4oJSRUK7td1aA.png','1','1','2026-10-06 23:38:38','2026-10-08 14:44:20');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('2','G-02','1','occupied','6500.00','Ground Floor unit G-02',NULL,'1','2','2026-10-06 23:38:38','2026-10-08 13:38:20');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('3','G-03','1','occupied','6500.00','Ground Floor unit G-03',NULL,'1','3','2026-10-06 23:38:38','2026-10-08 13:38:43');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('4','G-04','1','occupied','6500.00','Ground Floor unit G-04',NULL,'1','4','2026-10-06 23:38:38','2026-10-08 18:04:49');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('5','G-05','1','reserved','6500.00','Ground Floor unit G-05',NULL,'1','6','2026-10-06 23:38:38','2026-10-08 18:04:19');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('6','G-06','1','available','6500.00','Ground Floor unit G-06',NULL,'2','7','2026-10-06 23:38:38','2026-10-08 18:04:47');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('7','G-07','1','available','6500.00','Ground Floor unit G-07',NULL,'2','1','2026-10-06 23:38:38','2026-10-08 18:04:32');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('8','G-08','1','available','6500.00','Ground Floor unit G-08',NULL,'2','4','2026-10-06 23:38:38','2026-10-08 18:04:16');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('9','G-09','1','available','6500.00','Ground Floor unit G-09',NULL,'2','3','2026-10-06 23:38:38','2026-10-08 18:04:36');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('10','G-10','1','available','6500.00','Ground Floor unit G-10',NULL,'2','5','2026-10-06 23:38:38','2026-10-08 18:04:39');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('11','G-11','1','available','6500.00','Ground Floor unit G-11',NULL,'2','6','2026-10-06 23:38:38','2026-10-08 18:04:41');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('12','G-12','1','available','6500.00','Ground Floor unit G-12',NULL,'2','2','2026-10-06 23:38:38','2026-10-08 18:04:34');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('13','G-13','1','available','6500.00','Ground Floor unit G-13',NULL,'1','8','2026-10-06 23:38:38','2026-10-08 18:04:58');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('14','G-14','1','available','6500.00','Ground Floor unit G-14',NULL,'1','7','2026-10-06 23:38:38','2026-10-08 18:04:56');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('15','S-01','2','available','4500.00','Second Floor unit S-01',NULL,'1','1','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('16','S-02','2','available','4500.00','Second Floor unit S-02',NULL,'1','2','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('17','S-03','2','maintenance','4500.00','Second Floor unit S-03',NULL,'1','4','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('18','S-04','2','available','4500.00','Second Floor unit S-04',NULL,'1','5','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('19','S-05','2','available','4500.00','Second Floor unit S-05',NULL,'2','1','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('20','S-06','2','available','4500.00','Second Floor unit S-06',NULL,'2','2','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('21','S-07','2','available','4500.00','Second Floor unit S-07',NULL,'2','4','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('22','S-08','2','available','4500.00','Second Floor unit S-08',NULL,'2','5','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('23','F-01','3','available','5000.00','Food Court unit F-01',NULL,'1','1','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('24','F-02','3','reserved','5000.00','Food Court unit F-02',NULL,'1','2','2026-10-06 23:38:38','2026-10-08 18:05:41');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('25','F-03','3','available','5000.00','Food Court unit F-03',NULL,'1','4','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('26','F-04','3','available','5000.00','Food Court unit F-04',NULL,'1','5','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('27','F-05','3','available','5000.00','Food Court unit F-05',NULL,'2','2','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('28','F-06','3','available','5000.00','Food Court unit F-06',NULL,'2','3','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('29','F-07','3','available','5000.00','Food Court unit F-07',NULL,'2','5','2026-10-06 23:38:38','2026-10-06 23:38:38');
INSERT INTO `stalls` (`id`,`stall_number`,`location_id`,`status`,`price`,`description`,`image`,`grid_row`,`grid_col`,`created_at`,`updated_at`) VALUES ('30','F-08','3','available','5000.00','Food Court unit F-08',NULL,'3','1','2026-10-06 23:38:38','2026-10-06 23:38:38');

DROP TABLE IF EXISTS `ticket_messages`;
CREATE TABLE `ticket_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reply_to_message_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_messages_user_id_foreign` (`user_id`),
  KEY `ticket_messages_reply_to_message_id_foreign` (`reply_to_message_id`),
  KEY `ticket_messages_ticket_id_index` (`ticket_id`),
  CONSTRAINT `ticket_messages_reply_to_message_id_foreign` FOREIGN KEY (`reply_to_message_id`) REFERENCES `ticket_messages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ticket_messages_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `ticket_messages` (`id`,`ticket_id`,`user_id`,`message`,`attachment_path`,`reply_to_message_id`,`created_at`,`updated_at`) VALUES ('1','1','7','The ceiling light above my stall keeps flickering.',NULL,NULL,'2026-10-06 23:38:43','2026-10-06 23:38:43');
INSERT INTO `ticket_messages` (`id`,`ticket_id`,`user_id`,`message`,`attachment_path`,`reply_to_message_id`,`created_at`,`updated_at`) VALUES ('2','1','1','Thanks for reporting — maintenance will check it this afternoon.',NULL,NULL,'2026-10-06 23:38:43','2026-10-06 23:38:43');
INSERT INTO `ticket_messages` (`id`,`ticket_id`,`user_id`,`message`,`attachment_path`,`reply_to_message_id`,`created_at`,`updated_at`) VALUES ('3','2','9','Requesting a copy of my lease contract.',NULL,NULL,'2026-10-06 23:38:43','2026-10-06 23:38:43');
INSERT INTO `ticket_messages` (`id`,`ticket_id`,`user_id`,`message`,`attachment_path`,`reply_to_message_id`,`created_at`,`updated_at`) VALUES ('4','3','10','help me',NULL,NULL,'2026-10-08 10:26:02','2026-10-08 10:26:02');

DROP TABLE IF EXISTS `ticket_participants`;
CREATE TABLE `ticket_participants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `last_read_message_id` bigint unsigned DEFAULT NULL,
  `last_read_at` datetime DEFAULT NULL,
  `typing_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_participants_ticket_id_user_id_unique` (`ticket_id`,`user_id`),
  KEY `ticket_participants_user_id_foreign` (`user_id`),
  KEY `ticket_participants_last_read_message_id_foreign` (`last_read_message_id`),
  CONSTRAINT `ticket_participants_last_read_message_id_foreign` FOREIGN KEY (`last_read_message_id`) REFERENCES `ticket_messages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ticket_participants_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `ticket_participants` (`id`,`ticket_id`,`user_id`,`last_read_message_id`,`last_read_at`,`typing_at`,`created_at`,`updated_at`) VALUES ('1','3','10','4','2026-10-08 10:26:12',NULL,'2026-10-08 10:26:02','2026-10-08 10:26:12');
INSERT INTO `ticket_participants` (`id`,`ticket_id`,`user_id`,`last_read_message_id`,`last_read_at`,`typing_at`,`created_at`,`updated_at`) VALUES ('2','2','1','3','2026-10-08 11:05:07',NULL,'2026-10-08 11:04:45','2026-10-08 11:05:07');
INSERT INTO `ticket_participants` (`id`,`ticket_id`,`user_id`,`last_read_message_id`,`last_read_at`,`typing_at`,`created_at`,`updated_at`) VALUES ('3','1','7','1','2026-10-08 15:43:43',NULL,'2026-10-08 15:06:33','2026-10-08 15:43:43');

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `stall_id` bigint unsigned DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `issue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','in_progress','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tickets_stall_id_foreign` (`stall_id`),
  KEY `tickets_user_id_status_index` (`user_id`,`status`),
  KEY `tickets_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `tickets_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_stall_id_foreign` FOREIGN KEY (`stall_id`) REFERENCES `stalls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `tickets` (`id`,`user_id`,`stall_id`,`assigned_to`,`issue`,`status`,`created_at`,`updated_at`) VALUES ('1','7','1','1','The ceiling light above my stall keeps flickering.','open','2026-10-06 23:38:43','2026-10-06 23:38:43');
INSERT INTO `tickets` (`id`,`user_id`,`stall_id`,`assigned_to`,`issue`,`status`,`created_at`,`updated_at`) VALUES ('2','9','3','1','Requesting a copy of my lease contract.','closed','2026-10-06 23:38:43','2026-10-06 23:38:43');
INSERT INTO `tickets` (`id`,`user_id`,`stall_id`,`assigned_to`,`issue`,`status`,`created_at`,`updated_at`) VALUES ('3','10','4',NULL,'help me','open','2026-10-08 10:26:02','2026-10-08 10:26:02');

DROP TABLE IF EXISTS `two_factor_codes`;
CREATE TABLE `two_factor_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `two_factor_codes_user_id_code_index` (`user_id`,`code`),
  KEY `idx_2fa_expires_at` (`expires_at`),
  CONSTRAINT `two_factor_codes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `two_factor_codes` (`id`,`user_id`,`code`,`expires_at`,`created_at`,`updated_at`) VALUES ('6','1','214703c1c6b4fad0cb1c1bc9498f6231debedf8dbee11657251293f7a6be5792','2026-10-08 18:07:17','2026-10-08 17:57:17','2026-10-08 17:57:17');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','user') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `profile_picture` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deactivated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  UNIQUE KEY `users_google_id_unique` (`google_id`),
  UNIQUE KEY `users_facebook_id_unique` (`facebook_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `users` (`id`,`name`,`email`,`phone`,`password`,`role`,`two_factor_enabled`,`two_factor_confirmed_at`,`profile_picture`,`google_id`,`facebook_id`,`email_verified_at`,`phone_verified_at`,`remember_token`,`created_at`,`updated_at`,`deactivated_at`) VALUES ('1','Center Point Admin','admin@stallm.com','09171112233','$2y$12$JWYLfD9OMnK54NwYoeVyAeQQHpgoQ6bZt0NkChWyV/HaPFuic9TqW','admin','0',NULL,NULL,NULL,NULL,'2026-08-30 01:10:08',NULL,'ePF9GRrGPaWlcYp0Y63w2jb6rkmug6An0fUxAUvlQVFvpJlP01Yp6acNym0j','2026-08-30 00:50:51','2026-08-30 01:10:08',NULL);
INSERT INTO `users` (`id`,`name`,`email`,`phone`,`password`,`role`,`two_factor_enabled`,`two_factor_confirmed_at`,`profile_picture`,`google_id`,`facebook_id`,`email_verified_at`,`phone_verified_at`,`remember_token`,`created_at`,`updated_at`,`deactivated_at`) VALUES ('7','Juan Dela Cruz','tenant1@demo.test','09170000001','$2y$12$xEb6ICbmmnMne.X8pwMIqOO6oQvc3DJYfNhWb7vm/.ZZ7kwPiP78y','user','0',NULL,NULL,NULL,NULL,'2026-10-07 02:37:51',NULL,NULL,'2026-10-06 23:38:39','2026-10-07 02:37:51',NULL);
INSERT INTO `users` (`id`,`name`,`email`,`phone`,`password`,`role`,`two_factor_enabled`,`two_factor_confirmed_at`,`profile_picture`,`google_id`,`facebook_id`,`email_verified_at`,`phone_verified_at`,`remember_token`,`created_at`,`updated_at`,`deactivated_at`) VALUES ('8','Maria Clara Reyes','tenant2@demo.test','09170000002','$2y$12$paSD0A9SzStstoN7/7qEb.gSoEgtGFywd.j.PyFTytad2yWXZJeRm','user','0',NULL,NULL,NULL,NULL,'2026-10-07 02:37:51',NULL,NULL,'2026-10-06 23:38:39','2026-10-07 02:37:51',NULL);
INSERT INTO `users` (`id`,`name`,`email`,`phone`,`password`,`role`,`two_factor_enabled`,`two_factor_confirmed_at`,`profile_picture`,`google_id`,`facebook_id`,`email_verified_at`,`phone_verified_at`,`remember_token`,`created_at`,`updated_at`,`deactivated_at`) VALUES ('9','Jose Rizal Santos','tenant3@demo.test','09170000003','$2y$12$VN/yKjaKQZSNkLlG7w43u.PCujaJUP8NdT.gqqgY4rquBDBeWblc.','user','0',NULL,NULL,NULL,NULL,'2026-10-07 02:37:51',NULL,NULL,'2026-10-06 23:38:39','2026-10-07 02:37:51',NULL);
INSERT INTO `users` (`id`,`name`,`email`,`phone`,`password`,`role`,`two_factor_enabled`,`two_factor_confirmed_at`,`profile_picture`,`google_id`,`facebook_id`,`email_verified_at`,`phone_verified_at`,`remember_token`,`created_at`,`updated_at`,`deactivated_at`) VALUES ('10','Andres Bonifacio Lim','tenant4@demo.test','09170000004','$2y$12$D5en/LmsSy6xUMuj5kQ9TOhxS3eCbZDqqRR/UrhntGEwZxHNR/Ho6','user','0',NULL,NULL,NULL,NULL,'2026-10-07 02:37:52',NULL,NULL,'2026-10-06 23:38:39','2026-10-07 02:37:52',NULL);
INSERT INTO `users` (`id`,`name`,`email`,`phone`,`password`,`role`,`two_factor_enabled`,`two_factor_confirmed_at`,`profile_picture`,`google_id`,`facebook_id`,`email_verified_at`,`phone_verified_at`,`remember_token`,`created_at`,`updated_at`,`deactivated_at`) VALUES ('11','Carmen Villanueva','carmen.villanueva@example.com','09181112222','$2y$12$oXbL8igfAsuzlOGGSd018.w.8idyozEOZnMCO2sjJH4mV5KdIph4K','user','0',NULL,NULL,NULL,NULL,'2026-10-08 18:05:26',NULL,NULL,'2026-10-08 18:05:26','2026-10-08 18:05:26',NULL);
SET FOREIGN_KEY_CHECKS=1;
