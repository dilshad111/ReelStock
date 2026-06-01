-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: reelstock
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audits`
--

DROP TABLE IF EXISTS `audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_type` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `auditable_type` varchar(255) NOT NULL,
  `auditable_id` bigint(20) unsigned NOT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(1023) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audits_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `audits_user_id_user_type_index` (`user_id`,`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audits`
--

LOCK TABLES `audits` WRITE;
/*!40000 ALTER TABLE `audits` DISABLE KEYS */;
/*!40000 ALTER TABLE `audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cartage_bills`
--

DROP TABLE IF EXISTS `cartage_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cartage_bills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transporter_id` bigint(20) unsigned NOT NULL,
  `bill_to` varchar(255) NOT NULL DEFAULT 'M/S QUALITY CARTONS (Pvt.) LTD.',
  `bill_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Pending',
  `tax_type` varchar(255) DEFAULT NULL,
  `tax_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cartage_bills_transporter_id_foreign` (`transporter_id`),
  KEY `cartage_bills_approved_by_foreign` (`approved_by`),
  CONSTRAINT `cartage_bills_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `cartage_bills_transporter_id_foreign` FOREIGN KEY (`transporter_id`) REFERENCES `transporters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cartage_bills`
--

LOCK TABLES `cartage_bills` WRITE;
/*!40000 ALTER TABLE `cartage_bills` DISABLE KEYS */;
/*!40000 ALTER TABLE `cartage_bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cartage_entries`
--

DROP TABLE IF EXISTS `cartage_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cartage_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cartage_bill_id` bigint(20) unsigned NOT NULL,
  `parent_entry_id` bigint(20) unsigned DEFAULT NULL,
  `entry_date` date NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `shipping_address_id` bigint(20) unsigned NOT NULL,
  `vehicle_number` varchar(255) NOT NULL,
  `dc_number` varchar(255) DEFAULT NULL,
  `slip_no` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_return` tinyint(1) NOT NULL DEFAULT 0,
  `is_second_location` tinyint(1) NOT NULL DEFAULT 0,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `vehicle_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cartage_entries_cartage_bill_id_foreign` (`cartage_bill_id`),
  KEY `cartage_entries_customer_id_foreign` (`customer_id`),
  KEY `cartage_entries_shipping_address_id_foreign` (`shipping_address_id`),
  KEY `cartage_entries_parent_entry_id_foreign` (`parent_entry_id`),
  KEY `cartage_entries_vehicle_id_foreign` (`vehicle_id`),
  CONSTRAINT `cartage_entries_cartage_bill_id_foreign` FOREIGN KEY (`cartage_bill_id`) REFERENCES `cartage_bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cartage_entries_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `cartage_entries_parent_entry_id_foreign` FOREIGN KEY (`parent_entry_id`) REFERENCES `cartage_entries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cartage_entries_shipping_address_id_foreign` FOREIGN KEY (`shipping_address_id`) REFERENCES `shipping_addresses` (`id`),
  CONSTRAINT `cartage_entries_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cartage_entries`
--

LOCK TABLES `cartage_entries` WRITE;
/*!40000 ALTER TABLE `cartage_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `cartage_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cartage_increment_details`
--

DROP TABLE IF EXISTS `cartage_increment_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cartage_increment_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cartage_increment_log_id` bigint(20) unsigned NOT NULL,
  `shipping_address_id` bigint(20) unsigned NOT NULL,
  `old_rate` decimal(10,2) NOT NULL,
  `new_rate` decimal(10,2) NOT NULL,
  `amount_increase` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cartage_increment_details_cartage_increment_log_id_foreign` (`cartage_increment_log_id`),
  KEY `cartage_increment_details_shipping_address_id_foreign` (`shipping_address_id`),
  CONSTRAINT `cartage_increment_details_cartage_increment_log_id_foreign` FOREIGN KEY (`cartage_increment_log_id`) REFERENCES `cartage_increment_logs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cartage_increment_details_shipping_address_id_foreign` FOREIGN KEY (`shipping_address_id`) REFERENCES `shipping_addresses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cartage_increment_details`
--

LOCK TABLES `cartage_increment_details` WRITE;
/*!40000 ALTER TABLE `cartage_increment_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `cartage_increment_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cartage_increment_logs`
--

DROP TABLE IF EXISTS `cartage_increment_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cartage_increment_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_type` varchar(255) NOT NULL,
  `effective_date` date NOT NULL,
  `increment_type` varchar(255) DEFAULT NULL,
  `increment_value` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cartage_increment_logs`
--

LOCK TABLES `cartage_increment_logs` WRITE;
/*!40000 ALTER TABLE `cartage_increment_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `cartage_increment_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cartage_rates`
--

DROP TABLE IF EXISTS `cartage_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cartage_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shipping_address_id` bigint(20) unsigned NOT NULL,
  `vehicle_type` varchar(255) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cartage_rates_shipping_address_id_foreign` (`shipping_address_id`),
  CONSTRAINT `cartage_rates_shipping_address_id_foreign` FOREIGN KEY (`shipping_address_id`) REFERENCES `shipping_addresses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cartage_rates`
--

LOCK TABLES `cartage_rates` WRITE;
/*!40000 ALTER TABLE `cartage_rates` DISABLE KEYS */;
/*!40000 ALTER TABLE `cartage_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carton_types`
--

DROP TABLE IF EXISTS `carton_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carton_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `standard_code` varchar(50) NOT NULL,
  `preview_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carton_types_standard_code_unique` (`standard_code`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carton_types`
--

LOCK TABLES `carton_types` WRITE;
/*!40000 ALTER TABLE `carton_types` DISABLE KEYS */;
INSERT INTO `carton_types` VALUES (1,'Regular Slotted Carton','0201','/images/fefco/0201.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(2,'Slotted-Type Carton','0200','/images/fefco/0200.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(3,'Folder Type Carton','0427','/images/fefco/0427.png',0,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(4,'Half Slotted Carton','0201-HSC','/images/fefco/0201-HSC.png',0,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(5,'Overlap Slotted Carton','0202','/images/fefco/0202.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(6,'Full Overlap Slotted Carton','0203','/images/fefco/0203.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(7,'Center Special Slotted Carton','0204','/images/fefco/0204.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(8,'Center Special Overlap Slotted Carton','0205','/images/fefco/0205.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(9,'Center Special Full Overlap Slotted Carton','0206','/images/fefco/0206.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(10,'Special Slotted Carton','0207','/images/fefco/0207.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(11,'Slotted Carton With Cover','0208','/images/fefco/0208.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(12,'Slotted Tray Carton','0209','/images/fefco/0209.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(13,'Telescope-Type Carton','0300','/images/fefco/0300.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(14,'Full Telescope Carton','0301','/images/fefco/0301.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(15,'Telescope Carton With Separate Lid','0302','/images/fefco/0302.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(16,'Two-Piece Telescope Carton','0303','/images/fefco/0303.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(17,'Folder-Type Carton','0400','/images/fefco/0400.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(18,'One-Piece Folder Carton','0401','/images/fefco/0401.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(19,'Folder Carton With Locking Tabs','0403','/images/fefco/0403.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(20,'Five-Panel Folder Carton','0404','/images/fefco/0404.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(21,'Wrap-Around Folder Carton','0405','/images/fefco/0405.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(22,'Folder Tray Carton','0406','/images/fefco/0406.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(23,'Folder Carton With Hinged Lid','0409','/images/fefco/0409.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(24,'Slide-Type Carton','0500','/images/fefco/0500.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(25,'Sleeve and Drawer Carton','0501','/images/fefco/0501.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(26,'Slide Carton','0502','/images/fefco/0502.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(27,'Slide Tray Carton','0503','/images/fefco/0503.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(28,'Rigid-Type Carton','0600','/images/fefco/0600.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(29,'Rigid Carton','0601','/images/fefco/0601.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(30,'Rigid Shoulder Carton','0602','/images/fefco/0602.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(31,'Rigid Carton With Lid','0603','/images/fefco/0603.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(32,'Ready-Glued Carton','0700','/images/fefco/0700.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(33,'Pre-Glued Lock Bottom Carton','0701','/images/fefco/0701.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(34,'Auto-Bottom Carton','0703','/images/fefco/0703.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(35,'Ready-Glued Tray Carton','0711','/images/fefco/0711.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(36,'Ready-Glued Folder Carton','0713','/images/fefco/0713.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(37,'Interior Fitment','0900','/images/fefco/0900.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(38,'Partition Set','0901','/images/fefco/0901.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(39,'Divider Insert','0902','/images/fefco/0902.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(40,'Cell Divider','0903','/images/fefco/0903.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(41,'Pad Insert','0904','/images/fefco/0904.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57'),(42,'Separator Fitment','0905','/images/fefco/0905.png',1,'2026-05-31 12:01:57','2026-05-31 12:01:57');
/*!40000 ALTER TABLE `carton_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_name` varchar(255) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_department_name_unique` (`department_name`),
  KEY `departments_created_by_foreign` (`created_by`),
  KEY `departments_updated_by_foreign` (`updated_by`),
  CONSTRAINT `departments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fg_dispatches`
--

DROP TABLE IF EXISTS `fg_dispatches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fg_dispatches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `job_number` varchar(255) DEFAULT NULL,
  `dc_number` varchar(255) NOT NULL,
  `quantity_dispatched` decimal(12,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fg_dispatches_created_by_foreign` (`created_by`),
  KEY `fg_dispatches_customer_id_index` (`customer_id`),
  KEY `fg_dispatches_product_id_index` (`product_id`),
  KEY `fg_dispatches_job_number_index` (`job_number`),
  CONSTRAINT `fg_dispatches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fg_dispatches_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fg_dispatches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fg_dispatches`
--

LOCK TABLES `fg_dispatches` WRITE;
/*!40000 ALTER TABLE `fg_dispatches` DISABLE KEYS */;
/*!40000 ALTER TABLE `fg_dispatches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fg_receipts`
--

DROP TABLE IF EXISTS `fg_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fg_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `job_card_id` bigint(20) unsigned DEFAULT NULL,
  `job_number` varchar(255) NOT NULL,
  `production_date` date NOT NULL,
  `quantity_produced` decimal(12,2) NOT NULL,
  `carton_price` decimal(15,2) DEFAULT NULL,
  `wastage` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fg_receipts_created_by_foreign` (`created_by`),
  KEY `fg_receipts_customer_id_index` (`customer_id`),
  KEY `fg_receipts_product_id_index` (`product_id`),
  KEY `fg_receipts_job_number_index` (`job_number`),
  KEY `fg_receipts_job_card_id_foreign` (`job_card_id`),
  CONSTRAINT `fg_receipts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fg_receipts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fg_receipts_job_card_id_foreign` FOREIGN KEY (`job_card_id`) REFERENCES `job_cards` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fg_receipts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fg_receipts`
--

LOCK TABLES `fg_receipts` WRITE;
/*!40000 ALTER TABLE `fg_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `fg_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fg_stock_ledger`
--

DROP TABLE IF EXISTS `fg_stock_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fg_stock_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_type` enum('opening','receipt','dispatch','adjustment') NOT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `job_number` varchar(255) DEFAULT NULL,
  `quantity_in` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity_out` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(12,2) NOT NULL DEFAULT 0.00,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fg_stock_ledger_product_id_index` (`product_id`),
  KEY `fg_stock_ledger_customer_id_index` (`customer_id`),
  KEY `fg_stock_ledger_job_number_index` (`job_number`),
  KEY `fg_stock_ledger_transaction_type_index` (`transaction_type`),
  KEY `fg_stock_ledger_transaction_date_index` (`transaction_date`),
  CONSTRAINT `fg_stock_ledger_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fg_stock_ledger_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fg_stock_ledger`
--

LOCK TABLES `fg_stock_ledger` WRITE;
/*!40000 ALTER TABLE `fg_stock_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `fg_stock_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_card_items`
--

DROP TABLE IF EXISTS `job_card_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_card_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_card_id` bigint(20) unsigned NOT NULL,
  `rm_item_id` bigint(20) unsigned NOT NULL,
  `required_qty` decimal(15,2) NOT NULL,
  `consumed_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_card_items_job_card_id_foreign` (`job_card_id`),
  KEY `job_card_items_rm_item_id_foreign` (`rm_item_id`),
  CONSTRAINT `job_card_items_job_card_id_foreign` FOREIGN KEY (`job_card_id`) REFERENCES `job_cards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_card_items_rm_item_id_foreign` FOREIGN KEY (`rm_item_id`) REFERENCES `rm_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_card_items`
--

LOCK TABLES `job_card_items` WRITE;
/*!40000 ALTER TABLE `job_card_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_card_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_card_layers`
--

DROP TABLE IF EXISTS `job_card_layers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_card_layers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_card_id` bigint(20) unsigned DEFAULT NULL,
  `job_card_piece_id` bigint(20) unsigned DEFAULT NULL,
  `layer_type` varchar(50) NOT NULL,
  `paper_name` varchar(100) DEFAULT NULL,
  `gsm` int(11) NOT NULL DEFAULT 0,
  `flute_profile` varchar(20) NOT NULL DEFAULT 'Flat',
  `sequence` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_card_layers_job_card_id_foreign` (`job_card_id`),
  KEY `job_card_layers_job_card_piece_id_foreign` (`job_card_piece_id`),
  CONSTRAINT `job_card_layers_job_card_id_foreign` FOREIGN KEY (`job_card_id`) REFERENCES `job_cards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_card_layers_job_card_piece_id_foreign` FOREIGN KEY (`job_card_piece_id`) REFERENCES `job_card_pieces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_card_layers`
--

LOCK TABLES `job_card_layers` WRITE;
/*!40000 ALTER TABLE `job_card_layers` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_card_layers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_card_pieces`
--

DROP TABLE IF EXISTS `job_card_pieces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_card_pieces` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_card_id` bigint(20) unsigned NOT NULL,
  `piece_name` varchar(100) NOT NULL,
  `length_mm` decimal(10,2) DEFAULT NULL,
  `width_mm` decimal(10,2) DEFAULT NULL,
  `height_mm` decimal(10,2) DEFAULT NULL,
  `deckle_size` decimal(10,2) DEFAULT NULL,
  `sheet_length` decimal(10,2) DEFAULT NULL,
  `ups` int(11) NOT NULL DEFAULT 1,
  `machine_name` varchar(100) DEFAULT NULL,
  `target_speed` int(11) NOT NULL DEFAULT 0,
  `est_unit_weight` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `instructions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_card_pieces_job_card_id_foreign` (`job_card_id`),
  CONSTRAINT `job_card_pieces_job_card_id_foreign` FOREIGN KEY (`job_card_id`) REFERENCES `job_cards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_card_pieces`
--

LOCK TABLES `job_card_pieces` WRITE;
/*!40000 ALTER TABLE `job_card_pieces` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_card_pieces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_card_steps`
--

DROP TABLE IF EXISTS `job_card_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_card_steps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_card_id` bigint(20) unsigned NOT NULL,
  `step_name` varchar(255) NOT NULL,
  `sequence` int(11) NOT NULL DEFAULT 0,
  `status` enum('Pending','In-Progress','Completed') NOT NULL DEFAULT 'Pending',
  `produced_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `wastage_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_card_steps_job_card_id_foreign` (`job_card_id`),
  CONSTRAINT `job_card_steps_job_card_id_foreign` FOREIGN KEY (`job_card_id`) REFERENCES `job_cards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_card_steps`
--

LOCK TABLES `job_card_steps` WRITE;
/*!40000 ALTER TABLE `job_card_steps` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_card_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_cards`
--

DROP TABLE IF EXISTS `job_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_card_no` varchar(255) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `fg_product_id` bigint(20) unsigned NOT NULL,
  `planned_qty` decimal(15,2) NOT NULL,
  `length_mm` decimal(10,2) DEFAULT NULL,
  `width_mm` decimal(10,2) DEFAULT NULL,
  `height_mm` decimal(10,2) DEFAULT NULL,
  `uom` varchar(10) NOT NULL DEFAULT 'mm',
  `deckle_size` decimal(10,2) DEFAULT NULL,
  `sheet_length` decimal(10,2) DEFAULT NULL,
  `ups` int(11) NOT NULL DEFAULT 1,
  `carton_type` varchar(50) NOT NULL DEFAULT 'FEFCO 0201',
  `machine_name` varchar(100) DEFAULT NULL,
  `target_speed` int(11) NOT NULL DEFAULT 0,
  `printing_process` varchar(50) DEFAULT NULL,
  `pasting_closure` varchar(50) DEFAULT NULL,
  `printing_colors_count` int(11) NOT NULL DEFAULT 0,
  `pantone_colors` text DEFAULT NULL,
  `special_details` text DEFAULT NULL,
  `pieces_count` int(11) NOT NULL DEFAULT 1,
  `est_unit_weight` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `planned_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `status` enum('Open','In-Progress','Completed','Cancelled') NOT NULL DEFAULT 'Open',
  `specifications` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_cards_job_card_no_unique` (`job_card_no`),
  KEY `job_cards_customer_id_foreign` (`customer_id`),
  KEY `job_cards_fg_product_id_foreign` (`fg_product_id`),
  KEY `job_cards_created_by_foreign` (`created_by`),
  CONSTRAINT `job_cards_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `job_cards_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `job_cards_fg_product_id_foreign` FOREIGN KEY (`fg_product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_cards`
--

LOCK TABLES `job_cards` WRITE;
/*!40000 ALTER TABLE `job_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lot_sequences`
--

DROP TABLE IF EXISTS `lot_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lot_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prefix` varchar(255) NOT NULL DEFAULT 'LOT',
  `next_number` bigint(20) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lot_sequences`
--

LOCK TABLES `lot_sequences` WRITE;
/*!40000 ALTER TABLE `lot_sequences` DISABLE KEYS */;
INSERT INTO `lot_sequences` VALUES (1,'LOT',1,'2026-05-31 12:01:54','2026-05-31 12:01:54');
/*!40000 ALTER TABLE `lot_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `machine_operators`
--

DROP TABLE IF EXISTS `machine_operators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `machine_operators` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `operator_name` varchar(255) NOT NULL,
  `machine_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `machine_operators_operator_name_machine_id_unique` (`operator_name`,`machine_id`),
  KEY `machine_operators_machine_id_foreign` (`machine_id`),
  KEY `machine_operators_created_by_foreign` (`created_by`),
  KEY `machine_operators_updated_by_foreign` (`updated_by`),
  CONSTRAINT `machine_operators_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `machine_operators_machine_id_foreign` FOREIGN KEY (`machine_id`) REFERENCES `production_machines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `machine_operators_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `machine_operators`
--

LOCK TABLES `machine_operators` WRITE;
/*!40000 ALTER TABLE `machine_operators` DISABLE KEYS */;
/*!40000 ALTER TABLE `machine_operators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_resets_table',1),(3,'2019_08_19_000000_create_failed_jobs_table',1),(4,'2019_12_14_000001_create_personal_access_tokens_table',1),(5,'2025_11_12_143632_create_suppliers_table',1),(6,'2025_11_12_143654_create_paper_qualities_table',1),(7,'2025_11_12_143710_create_reels_table',1),(8,'2025_11_12_143718_create_reel_receipts_table',1),(9,'2025_11_12_143726_create_reel_issues_table',1),(10,'2025_11_12_143746_create_reel_returns_table',1),(11,'2025_11_12_143757_create_roles_table',1),(12,'2025_11_12_143810_add_role_id_to_users_table',1),(13,'2025_11_12_154535_make_item_code_unique_in_paper_qualities_table',1),(14,'2025_11_12_160000_add_rate_to_reel_receipts_table',1),(15,'2025_11_12_170536_add_status_to_reels_table',1),(16,'2025_11_18_121418_add_received_by_to_reel_receipts_table',1),(17,'2025_11_18_173020_create_user_permissions_table',1),(18,'2025_11_18_192119_create_customers_table',1),(19,'2025_11_20_180518_create_settings_table',1),(20,'2025_12_02_184500_add_returned_to_to_reel_returns_table',1),(21,'2025_12_02_200100_add_challan_no_to_reel_returns_table',1),(22,'2025_12_02_230100_add_return_fields_to_reel_issues_table',1),(23,'2025_12_02_233500_add_auto_return_id_to_reel_issues_table',1),(24,'2025_12_04_181724_add_vehicle_number_to_reel_returns_table',1),(25,'2025_12_04_182638_add_return_to_supplier_id_to_reel_returns_table',1),(26,'2025_12_05_082851_remove_unique_constraint_from_challan_no_in_reel_returns_table',1),(27,'2025_12_19_090749_create_audits_table',1),(28,'2025_12_22_132923_add_return_location_to_issues_and_returns_tables',1),(29,'2026_01_15_113437_create_stock_alerts_table',1),(30,'2026_01_15_123040_remove_gsm_from_stock_alerts_table',1),(31,'2026_01_22_133351_add_returned_to_supplier_status_to_reels_table',1),(32,'2026_01_22_135854_create_reconciliation_logs_table',1),(33,'2026_03_02_121014_drop_customers_table',1),(34,'2026_03_02_155000_create_reel_sequences_table',1),(35,'2026_04_18_131128_create_transport_tables',1),(36,'2026_04_20_115128_add_extra_fields_to_cartage_entries_table',1),(37,'2026_04_20_115859_add_slip_no_to_cartage_entries_table',1),(38,'2026_04_20_121432_add_logo_to_transporters_table',1),(39,'2026_04_20_123407_add_approval_and_tax_fields_to_cartage_bills_table',1),(40,'2026_04_21_105212_add_add_and_delete_to_user_permissions_table',1),(41,'2026_04_21_121858_add_vehicle_id_to_cartage_entries_table',1),(42,'2026_04_22_150000_create_finished_goods_tables',1),(43,'2026_04_22_161000_add_rate_to_products_table',1),(44,'2026_04_23_114424_remove_unique_constraint_from_products_table',1),(45,'2026_04_30_114845_add_carton_price_to_fg_receipts_table',1),(46,'2026_05_04_113433_create_vehicle_types_table',1),(47,'2026_05_04_113854_create_cartage_increment_logs_table',1),(48,'2026_05_04_113859_create_cartage_increment_details_table',1),(49,'2026_05_12_170000_create_qc_inspection_tables',1),(50,'2026_05_14_105745_create_paper_colors_table',1),(51,'2026_05_14_105749_add_min_max_fields_to_paper_qualities_table',1),(52,'2026_05_16_172359_create_raw_material_tables',1),(53,'2026_05_16_173127_create_production_tables',1),(54,'2026_05_16_174043_add_job_card_id_to_fg_receipts_table',1),(55,'2026_05_17_095359_create_unit_of_measures_table',1),(56,'2026_05_19_221213_upgrade_job_cards_table',1),(57,'2026_05_20_000000_add_reel_size_and_weight_to_qc_inspection_details_table',1),(58,'2026_05_28_000001_create_production_configuration_tables',1),(59,'2026_05_28_000002_create_carton_types_table',1),(60,'2026_05_28_000003_upsert_fefco_carton_types',1),(61,'2026_05_29_120000_add_standard_fields_to_paper_qualities_table',1),(62,'2026_05_29_130000_backfill_standard_values_in_paper_qualities_table',1),(63,'2026_05_29_140000_add_decision_type_to_qc_inspections_table',1),(64,'2026_05_31_210000_enhance_raw_material_categories_for_corrugated_cartons',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `optimization_rules`
--

DROP TABLE IF EXISTS `optimization_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `optimization_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parameter_name` varchar(255) NOT NULL,
  `condition_field` varchar(50) NOT NULL,
  `operator` varchar(5) NOT NULL,
  `condition_value` varchar(255) NOT NULL,
  `adjustment_type` varchar(50) NOT NULL,
  `adjustment_value` decimal(12,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `optimization_rules_created_by_foreign` (`created_by`),
  KEY `optimization_rules_updated_by_foreign` (`updated_by`),
  KEY `optimization_rules_condition_field_is_active_index` (`condition_field`,`is_active`),
  CONSTRAINT `optimization_rules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `optimization_rules_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `optimization_rules`
--

LOCK TABLES `optimization_rules` WRITE;
/*!40000 ALTER TABLE `optimization_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `optimization_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paper_colors`
--

DROP TABLE IF EXISTS `paper_colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paper_colors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paper_colors_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paper_colors`
--

LOCK TABLES `paper_colors` WRITE;
/*!40000 ALTER TABLE `paper_colors` DISABLE KEYS */;
/*!40000 ALTER TABLE `paper_colors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paper_qualities`
--

DROP TABLE IF EXISTS `paper_qualities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paper_qualities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quality` varchar(255) NOT NULL,
  `item_code` varchar(255) NOT NULL,
  `gsm_range` varchar(255) NOT NULL,
  `min_gsm` decimal(8,2) DEFAULT NULL,
  `standard_gsm` decimal(10,2) DEFAULT NULL,
  `max_gsm` decimal(10,2) DEFAULT NULL,
  `min_bursting` decimal(8,2) DEFAULT NULL,
  `standard_bursting` decimal(10,2) DEFAULT NULL,
  `max_bursting` decimal(10,2) DEFAULT NULL,
  `max_moisture` decimal(8,2) DEFAULT NULL,
  `min_moisture` decimal(10,2) DEFAULT NULL,
  `standard_moisture` decimal(10,2) DEFAULT NULL,
  `max_cobb` decimal(8,2) DEFAULT NULL,
  `min_cobb` decimal(10,2) DEFAULT NULL,
  `standard_cobb` decimal(10,2) DEFAULT NULL,
  `paper_color` varchar(255) DEFAULT NULL,
  `paper_color_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paper_qualities_item_code_unique` (`item_code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paper_qualities`
--

LOCK TABLES `paper_qualities` WRITE;
/*!40000 ALTER TABLE `paper_qualities` DISABLE KEYS */;
/*!40000 ALTER TABLE `paper_qualities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `printing_colors`
--

DROP TABLE IF EXISTS `printing_colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `printing_colors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ink_code` varchar(50) NOT NULL,
  `ink_name` varchar(255) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `printing_colors_ink_code_unique` (`ink_code`),
  KEY `printing_colors_created_by_foreign` (`created_by`),
  KEY `printing_colors_updated_by_foreign` (`updated_by`),
  CONSTRAINT `printing_colors_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `printing_colors_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `printing_colors`
--

LOCK TABLES `printing_colors` WRITE;
/*!40000 ALTER TABLE `printing_colors` DISABLE KEYS */;
/*!40000 ALTER TABLE `printing_colors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_logs`
--

DROP TABLE IF EXISTS `production_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_card_id` bigint(20) unsigned NOT NULL,
  `job_card_step_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `shift` varchar(255) DEFAULT NULL,
  `machine_no` varchar(255) DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `wastage` decimal(15,2) NOT NULL DEFAULT 0.00,
  `operator_name` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `production_logs_job_card_id_foreign` (`job_card_id`),
  KEY `production_logs_job_card_step_id_foreign` (`job_card_step_id`),
  KEY `production_logs_created_by_foreign` (`created_by`),
  CONSTRAINT `production_logs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `production_logs_job_card_id_foreign` FOREIGN KEY (`job_card_id`) REFERENCES `job_cards` (`id`),
  CONSTRAINT `production_logs_job_card_step_id_foreign` FOREIGN KEY (`job_card_step_id`) REFERENCES `job_card_steps` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_logs`
--

LOCK TABLES `production_logs` WRITE;
/*!40000 ALTER TABLE `production_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_machines`
--

DROP TABLE IF EXISTS `production_machines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_machines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `machine_name` varchar(255) NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `base_speed` decimal(12,2) DEFAULT NULL,
  `minimum_speed` decimal(12,2) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `production_machines_machine_name_department_id_unique` (`machine_name`,`department_id`),
  KEY `production_machines_department_id_foreign` (`department_id`),
  KEY `production_machines_created_by_foreign` (`created_by`),
  KEY `production_machines_updated_by_foreign` (`updated_by`),
  CONSTRAINT `production_machines_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_machines_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_machines_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_machines`
--

LOCK TABLES `production_machines` WRITE;
/*!40000 ALTER TABLE `production_machines` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_machines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `item_code` varchar(255) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `rate` decimal(10,2) DEFAULT NULL,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_customer_id_index` (`customer_id`),
  CONSTRAINT `products_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qc_inspection_details`
--

DROP TABLE IF EXISTS `qc_inspection_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qc_inspection_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `qc_inspection_id` bigint(20) unsigned NOT NULL,
  `reel_id` bigint(20) unsigned NOT NULL,
  `reel_no` varchar(255) DEFAULT NULL,
  `reel_size` decimal(8,2) DEFAULT NULL,
  `reel_weight` decimal(10,2) DEFAULT NULL,
  `gsm` decimal(8,2) DEFAULT NULL,
  `bursting` decimal(8,2) DEFAULT NULL,
  `moisture` decimal(8,2) DEFAULT NULL,
  `ash` decimal(8,2) DEFAULT NULL,
  `cobb` decimal(8,2) DEFAULT NULL,
  `is_passed` tinyint(1) NOT NULL DEFAULT 1,
  `failed_params` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`failed_params`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qc_inspection_details_qc_inspection_id_foreign` (`qc_inspection_id`),
  KEY `qc_inspection_details_reel_id_foreign` (`reel_id`),
  CONSTRAINT `qc_inspection_details_qc_inspection_id_foreign` FOREIGN KEY (`qc_inspection_id`) REFERENCES `qc_inspections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qc_inspection_details_reel_id_foreign` FOREIGN KEY (`reel_id`) REFERENCES `reels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qc_inspection_details`
--

LOCK TABLES `qc_inspection_details` WRITE;
/*!40000 ALTER TABLE `qc_inspection_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `qc_inspection_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qc_inspections`
--

DROP TABLE IF EXISTS `qc_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qc_inspections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lot_number` varchar(255) NOT NULL,
  `paper_quality_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `po_number` varchar(255) DEFAULT NULL,
  `grn_number` varchar(255) DEFAULT NULL,
  `received_date` date NOT NULL,
  `inspection_date` date NOT NULL,
  `inspector_name` varchar(255) NOT NULL,
  `qc_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `decision_type` varchar(30) NOT NULL DEFAULT 'lot_accept',
  `remarks` text DEFAULT NULL,
  `inspected_by` bigint(20) unsigned DEFAULT NULL,
  `avg_gsm` decimal(8,2) DEFAULT NULL,
  `avg_bursting` decimal(8,2) DEFAULT NULL,
  `avg_moisture` decimal(8,2) DEFAULT NULL,
  `avg_cobb` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qc_inspections_paper_quality_id_foreign` (`paper_quality_id`),
  KEY `qc_inspections_supplier_id_foreign` (`supplier_id`),
  KEY `qc_inspections_inspected_by_foreign` (`inspected_by`),
  KEY `qc_inspections_lot_number_index` (`lot_number`),
  CONSTRAINT `qc_inspections_inspected_by_foreign` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`),
  CONSTRAINT `qc_inspections_paper_quality_id_foreign` FOREIGN KEY (`paper_quality_id`) REFERENCES `paper_qualities` (`id`),
  CONSTRAINT `qc_inspections_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qc_inspections`
--

LOCK TABLES `qc_inspections` WRITE;
/*!40000 ALTER TABLE `qc_inspections` DISABLE KEYS */;
/*!40000 ALTER TABLE `qc_inspections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reconciliation_logs`
--

DROP TABLE IF EXISTS `reconciliation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reconciliation_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `run_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_reels_checked` int(11) NOT NULL DEFAULT 0,
  `discrepancies_found` int(11) NOT NULL DEFAULT 0,
  `corrections_made` int(11) NOT NULL DEFAULT 0,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `run_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reconciliation_logs_run_by_foreign` (`run_by`),
  CONSTRAINT `reconciliation_logs_run_by_foreign` FOREIGN KEY (`run_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reconciliation_logs`
--

LOCK TABLES `reconciliation_logs` WRITE;
/*!40000 ALTER TABLE `reconciliation_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `reconciliation_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reel_issues`
--

DROP TABLE IF EXISTS `reel_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reel_issues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reel_id` bigint(20) unsigned NOT NULL,
  `issue_date` date NOT NULL,
  `quantity_issued` decimal(10,2) NOT NULL,
  `return_to_stock_weight` decimal(10,2) NOT NULL DEFAULT 0.00,
  `return_location` varchar(255) DEFAULT NULL,
  `net_consumed_weight` decimal(10,2) NOT NULL DEFAULT 0.00,
  `issued_to` varchar(255) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `auto_return_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reel_issues_reel_id_foreign` (`reel_id`),
  KEY `reel_issues_auto_return_id_foreign` (`auto_return_id`),
  CONSTRAINT `reel_issues_auto_return_id_foreign` FOREIGN KEY (`auto_return_id`) REFERENCES `reel_returns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reel_issues_reel_id_foreign` FOREIGN KEY (`reel_id`) REFERENCES `reels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reel_issues`
--

LOCK TABLES `reel_issues` WRITE;
/*!40000 ALTER TABLE `reel_issues` DISABLE KEYS */;
/*!40000 ALTER TABLE `reel_issues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reel_receipts`
--

DROP TABLE IF EXISTS `reel_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reel_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reel_id` bigint(20) unsigned NOT NULL,
  `lot_number` varchar(255) DEFAULT NULL,
  `po_number` varchar(255) DEFAULT NULL,
  `grn_number` varchar(255) DEFAULT NULL,
  `receiving_date` date NOT NULL,
  `received_by` varchar(255) NOT NULL DEFAULT 'Afzal',
  `gsm` decimal(5,2) DEFAULT NULL,
  `bursting_strength` decimal(8,2) DEFAULT NULL,
  `qc_status` enum('approved','rejected','on_hold') NOT NULL DEFAULT 'on_hold',
  `rate_per_kg` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reel_receipts_reel_id_foreign` (`reel_id`),
  KEY `reel_receipts_lot_number_index` (`lot_number`),
  CONSTRAINT `reel_receipts_reel_id_foreign` FOREIGN KEY (`reel_id`) REFERENCES `reels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reel_receipts`
--

LOCK TABLES `reel_receipts` WRITE;
/*!40000 ALTER TABLE `reel_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `reel_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reel_returns`
--

DROP TABLE IF EXISTS `reel_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reel_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reel_id` bigint(20) unsigned NOT NULL,
  `challan_no` varchar(20) DEFAULT NULL,
  `vehicle_number` varchar(255) DEFAULT NULL,
  `return_to_supplier_id` bigint(20) unsigned DEFAULT NULL,
  `return_date` date NOT NULL,
  `remaining_weight` decimal(10,2) NOT NULL,
  `returned_to` enum('stock','supplier') NOT NULL DEFAULT 'stock',
  `return_location` varchar(255) DEFAULT NULL,
  `condition` enum('good','damaged','qc_required') NOT NULL DEFAULT 'good',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reel_returns_reel_id_foreign` (`reel_id`),
  KEY `reel_returns_return_to_supplier_id_foreign` (`return_to_supplier_id`),
  CONSTRAINT `reel_returns_reel_id_foreign` FOREIGN KEY (`reel_id`) REFERENCES `reels` (`id`),
  CONSTRAINT `reel_returns_return_to_supplier_id_foreign` FOREIGN KEY (`return_to_supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reel_returns`
--

LOCK TABLES `reel_returns` WRITE;
/*!40000 ALTER TABLE `reel_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `reel_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reel_sequences`
--

DROP TABLE IF EXISTS `reel_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reel_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prefix` varchar(10) NOT NULL DEFAULT 'RL',
  `next_number` bigint(20) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reel_sequences`
--

LOCK TABLES `reel_sequences` WRITE;
/*!40000 ALTER TABLE `reel_sequences` DISABLE KEYS */;
INSERT INTO `reel_sequences` VALUES (1,'RL',1,'2026-05-31 12:01:53','2026-05-31 12:01:53');
/*!40000 ALTER TABLE `reel_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reels`
--

DROP TABLE IF EXISTS `reels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reel_no` varchar(255) NOT NULL,
  `paper_quality_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `reel_size` decimal(8,2) NOT NULL,
  `original_weight` decimal(10,2) NOT NULL,
  `balance_weight` decimal(10,2) NOT NULL,
  `status` enum('in_stock','partially_used','fully_used','returned_to_supplier') NOT NULL DEFAULT 'in_stock',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reels_reel_no_unique` (`reel_no`),
  KEY `reels_paper_quality_id_foreign` (`paper_quality_id`),
  KEY `reels_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `reels_paper_quality_id_foreign` FOREIGN KEY (`paper_quality_id`) REFERENCES `paper_qualities` (`id`),
  CONSTRAINT `reels_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reels`
--

LOCK TABLES `reels` WRITE;
/*!40000 ALTER TABLE `reels` DISABLE KEYS */;
/*!40000 ALTER TABLE `reels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rm_categories`
--

DROP TABLE IF EXISTS `rm_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rm_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rm_categories_name_unique` (`name`),
  UNIQUE KEY `rm_categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rm_categories`
--

LOCK TABLES `rm_categories` WRITE;
/*!40000 ALTER TABLE `rm_categories` DISABLE KEYS */;
INSERT INTO `rm_categories` VALUES (1,'Paper & Board','paper-board','Paper and board grades used by reels inventory and production jobs.',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(2,'Adhesives & Chemicals','adhesives-chemicals','Starch, caustic, borax, and adhesive process chemicals.',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(3,'Inks & Coatings','inks-coatings','Printing inks, varnishes, and coating materials.',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(4,'Packaging Consumables','packaging-consumables','Bundling, labeling, wrapping, and dispatch consumables.',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(5,'Production Consumables','production-consumables','Consumables directly used on converting and finishing lines.',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(6,'Maintenance Consumables (MRO)','maintenance-consumables-mro','Maintenance, repair, operations, and cleaning consumables.',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57');
/*!40000 ALTER TABLE `rm_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rm_consumption_items`
--

DROP TABLE IF EXISTS `rm_consumption_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rm_consumption_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rm_consumption_id` bigint(20) unsigned NOT NULL,
  `rm_item_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rm_consumption_items_rm_consumption_id_foreign` (`rm_consumption_id`),
  KEY `rm_consumption_items_rm_item_id_foreign` (`rm_item_id`),
  CONSTRAINT `rm_consumption_items_rm_consumption_id_foreign` FOREIGN KEY (`rm_consumption_id`) REFERENCES `rm_consumptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rm_consumption_items_rm_item_id_foreign` FOREIGN KEY (`rm_item_id`) REFERENCES `rm_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rm_consumption_items`
--

LOCK TABLES `rm_consumption_items` WRITE;
/*!40000 ALTER TABLE `rm_consumption_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `rm_consumption_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rm_consumptions`
--

DROP TABLE IF EXISTS `rm_consumptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rm_consumptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_no` varchar(255) NOT NULL,
  `job_card_id` bigint(20) unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `issued_to` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rm_consumptions_voucher_no_unique` (`voucher_no`),
  KEY `rm_consumptions_created_by_foreign` (`created_by`),
  CONSTRAINT `rm_consumptions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rm_consumptions`
--

LOCK TABLES `rm_consumptions` WRITE;
/*!40000 ALTER TABLE `rm_consumptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `rm_consumptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rm_items`
--

DROP TABLE IF EXISTS `rm_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rm_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `paper_quality_id` bigint(20) unsigned DEFAULT NULL,
  `rm_category_id` bigint(20) unsigned DEFAULT NULL,
  `rm_subcategory_id` bigint(20) unsigned DEFAULT NULL,
  `unit_type` varchar(255) NOT NULL DEFAULT 'KG',
  `material_type` enum('Direct Material','Indirect Material','Consumable') NOT NULL DEFAULT 'Direct Material',
  `cost_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `opening_stock` decimal(15,2) NOT NULL DEFAULT 0.00,
  `min_stock_alert` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reorder_level` decimal(15,2) NOT NULL DEFAULT 0.00,
  `minimum_stock` decimal(15,2) NOT NULL DEFAULT 0.00,
  `maximum_stock` decimal(15,2) NOT NULL DEFAULT 0.00,
  `preferred_supplier_id` bigint(20) unsigned DEFAULT NULL,
  `gst_tax_code` varchar(50) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rm_items_code_unique` (`code`),
  KEY `rm_items_paper_quality_id_foreign` (`paper_quality_id`),
  KEY `rm_items_rm_subcategory_id_foreign` (`rm_subcategory_id`),
  KEY `rm_items_rm_category_id_rm_subcategory_id_index` (`rm_category_id`,`rm_subcategory_id`),
  KEY `rm_items_preferred_supplier_id_index` (`preferred_supplier_id`),
  CONSTRAINT `rm_items_paper_quality_id_foreign` FOREIGN KEY (`paper_quality_id`) REFERENCES `paper_qualities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rm_items_preferred_supplier_id_foreign` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rm_items_rm_category_id_foreign` FOREIGN KEY (`rm_category_id`) REFERENCES `rm_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rm_items_rm_subcategory_id_foreign` FOREIGN KEY (`rm_subcategory_id`) REFERENCES `rm_subcategories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rm_items`
--

LOCK TABLES `rm_items` WRITE;
/*!40000 ALTER TABLE `rm_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `rm_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rm_receipt_items`
--

DROP TABLE IF EXISTS `rm_receipt_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rm_receipt_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rm_receipt_id` bigint(20) unsigned NOT NULL,
  `rm_item_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `rate` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rm_receipt_items_rm_receipt_id_foreign` (`rm_receipt_id`),
  KEY `rm_receipt_items_rm_item_id_foreign` (`rm_item_id`),
  CONSTRAINT `rm_receipt_items_rm_item_id_foreign` FOREIGN KEY (`rm_item_id`) REFERENCES `rm_items` (`id`),
  CONSTRAINT `rm_receipt_items_rm_receipt_id_foreign` FOREIGN KEY (`rm_receipt_id`) REFERENCES `rm_receipts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rm_receipt_items`
--

LOCK TABLES `rm_receipt_items` WRITE;
/*!40000 ALTER TABLE `rm_receipt_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `rm_receipt_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rm_receipts`
--

DROP TABLE IF EXISTS `rm_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rm_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grn_no` varchar(255) NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rm_receipts_grn_no_unique` (`grn_no`),
  KEY `rm_receipts_supplier_id_foreign` (`supplier_id`),
  KEY `rm_receipts_created_by_foreign` (`created_by`),
  CONSTRAINT `rm_receipts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `rm_receipts_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rm_receipts`
--

LOCK TABLES `rm_receipts` WRITE;
/*!40000 ALTER TABLE `rm_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `rm_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rm_stock_ledger`
--

DROP TABLE IF EXISTS `rm_stock_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rm_stock_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rm_item_id` bigint(20) unsigned NOT NULL,
  `transaction_type` varchar(255) NOT NULL,
  `reference_id` bigint(20) unsigned NOT NULL,
  `quantity_in` decimal(15,2) NOT NULL DEFAULT 0.00,
  `quantity_out` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(15,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rm_stock_ledger_rm_item_id_transaction_date_index` (`rm_item_id`,`transaction_date`),
  CONSTRAINT `rm_stock_ledger_rm_item_id_foreign` FOREIGN KEY (`rm_item_id`) REFERENCES `rm_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rm_stock_ledger`
--

LOCK TABLES `rm_stock_ledger` WRITE;
/*!40000 ALTER TABLE `rm_stock_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `rm_stock_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rm_subcategories`
--

DROP TABLE IF EXISTS `rm_subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rm_subcategories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rm_category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rm_subcategories_rm_category_id_name_unique` (`rm_category_id`,`name`),
  UNIQUE KEY `rm_subcategories_rm_category_id_slug_unique` (`rm_category_id`,`slug`),
  CONSTRAINT `rm_subcategories_rm_category_id_foreign` FOREIGN KEY (`rm_category_id`) REFERENCES `rm_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rm_subcategories`
--

LOCK TABLES `rm_subcategories` WRITE;
/*!40000 ALTER TABLE `rm_subcategories` DISABLE KEYS */;
INSERT INTO `rm_subcategories` VALUES (1,2,'Corn Starch','corn-starch',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(2,2,'Tapioca Starch','tapioca-starch',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(3,2,'Borax','borax',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(4,2,'Caustic Soda','caustic-soda',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(5,2,'Adhesive Additives','adhesive-additives',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(6,2,'Preservatives','preservatives',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(7,3,'Water-Based Color Ink','water-based-color-ink',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(8,3,'Flexographic Ink','flexographic-ink',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(9,3,'Varnish','varnish',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(10,3,'Coating Chemicals','coating-chemicals',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(11,4,'PP Straps','pp-straps',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(12,4,'Stretch Film','stretch-film',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(13,4,'Labels','labels',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(14,4,'Stickers','stickers',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(15,4,'Corner Protectors','corner-protectors',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(16,5,'Stitching Wire','stitching-wire',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(17,5,'Printing Plates','printing-plates',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(18,5,'Cutting Dies','cutting-dies',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(19,5,'Glue Consumables','glue-consumables',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(20,6,'Lubricants','lubricants',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(21,6,'Grease','grease',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(22,6,'Machine Oils','machine-oils',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(23,6,'Cleaning Chemicals','cleaning-chemicals',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(24,6,'Maintenance Tools','maintenance-tools',1,'Active','2026-05-31 12:01:57','2026-05-31 12:01:57');
/*!40000 ALTER TABLE `rm_subcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_addresses`
--

DROP TABLE IF EXISTS `shipping_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipping_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `address_name` varchar(255) NOT NULL,
  `full_address` text NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipping_addresses_customer_id_foreign` (`customer_id`),
  CONSTRAINT `shipping_addresses_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_addresses`
--

LOCK TABLES `shipping_addresses` WRITE;
/*!40000 ALTER TABLE `shipping_addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `shipping_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_alerts`
--

DROP TABLE IF EXISTS `stock_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `paper_quality_id` bigint(20) unsigned NOT NULL,
  `reel_size` decimal(8,2) NOT NULL,
  `alert_type` enum('reels','weight') NOT NULL,
  `threshold_value` decimal(12,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_alerts_paper_quality_id_foreign` (`paper_quality_id`),
  CONSTRAINT `stock_alerts_paper_quality_id_foreign` FOREIGN KEY (`paper_quality_id`) REFERENCES `paper_qualities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_alerts`
--

LOCK TABLES `stock_alerts` WRITE;
/*!40000 ALTER TABLE `stock_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_supplier_id_unique` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transporters`
--

DROP TABLE IF EXISTS `transporters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transporters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transporters`
--

LOCK TABLES `transporters` WRITE;
/*!40000 ALTER TABLE `transporters` DISABLE KEYS */;
/*!40000 ALTER TABLE `transporters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unit_of_measures`
--

DROP TABLE IF EXISTS `unit_of_measures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unit_of_measures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unit_of_measures_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unit_of_measures`
--

LOCK TABLES `unit_of_measures` WRITE;
/*!40000 ALTER TABLE `unit_of_measures` DISABLE KEYS */;
INSERT INTO `unit_of_measures` VALUES (1,'Kg','active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(2,'Ton','active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(3,'Roll','active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(4,'Liter','active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(5,'Piece','active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(6,'Meter','active','2026-05-31 12:01:57','2026-05-31 12:01:57'),(7,'Sqr. Meter','active','2026-05-31 12:01:57','2026-05-31 12:01:57');
/*!40000 ALTER TABLE `unit_of_measures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `menu` varchar(255) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_add` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `can_see_amounts` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_permissions_user_id_foreign` (`user_id`),
  CONSTRAINT `user_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permissions`
--

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_types`
--

DROP TABLE IF EXISTS `vehicle_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicle_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_types`
--

LOCK TABLES `vehicle_types` WRITE;
/*!40000 ALTER TABLE `vehicle_types` DISABLE KEYS */;
INSERT INTO `vehicle_types` VALUES (1,'Suzuki','2026-05-31 12:01:54','2026-05-31 12:01:54'),(2,'Shehzore','2026-05-31 12:01:54','2026-05-31 12:01:54'),(3,'Mazda','2026-05-31 12:01:54','2026-05-31 12:01:54'),(4,'1x17 Container','2026-05-31 12:01:54','2026-05-31 12:01:54'),(5,'1x20 Container','2026-05-31 12:01:54','2026-05-31 12:01:54');
/*!40000 ALTER TABLE `vehicle_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_number` varchar(255) NOT NULL,
  `vehicle_type` varchar(255) NOT NULL,
  `transporter_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_vehicle_number_unique` (`vehicle_number`),
  KEY `vehicles_transporter_id_foreign` (`transporter_id`),
  CONSTRAINT `vehicles_transporter_id_foreign` FOREIGN KEY (`transporter_id`) REFERENCES `transporters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-31 22:10:26
