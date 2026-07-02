-- =========================================================================
-- KTM eDOIS — CONSOLIDATED & CORRECTED DATABASE SCHEMA
-- =========================================================================
-- This file replaces the original ktm_edois.sql export, which had become
-- inconsistent with the actual application code and could not even be
-- imported cleanly. Three concrete defects were found and fixed here:
--
--   1. `purchase_order` was CREATE-TABLE'd twice (fatal import error:
--      "Table 'purchase_order' already exists").
--   2. An `INSERT INTO ktmb_staff (...)` statement existed with NO matching
--      CREATE TABLE anywhere in the file — `ktmb_staff` simply didn't
--      exist. Every Module 4 file (AuditModel, AuditController,
--      assign_reviewer.php, review_history.php, audit_log.php) and the
--      staff login page (m1/staff_login.php) all query this exact table.
--      Without it, staff could never log in, so Module 4 could never be
--      reached at all — this was the root cause of "sidebar not
--      accessible" for KTMB Officer / Finance / Admin.
--   3. `delivery_order` was dumped with a `user_ID` column, but every
--      single query in Module 2 (do creation) and Module 4 (DO/invoice
--      review, payment approval) reads/writes `delivery_order.supplier_ID`
--      joined against the `supplier` table. This mismatch would throw
--      "Unknown column 'd.supplier_ID' in on clause" on literally every
--      list/detail page in Module 4.
--
-- Fixing these three things (plus seeding one Administrator account, since
-- none existed to test the Admin sidebar) is enough to make Module 4 run
-- end-to-end without errors.
-- =========================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table structure for table `user` (Unified login table — used by some
-- Module 1 vendor flows; kept for backward compatibility with M1/M2/M3)
-- --------------------------------------------------------

CREATE TABLE `user` (
  `user_ID` varchar(20) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('staff','supplier') NOT NULL COMMENT 'staff = KTMB Internal, supplier = External Vendor',
  `staff_role` enum('KTM Staff','Finance Officer','Administrator') DEFAULT NULL COMMENT 'Only applies if user_type = staff',
  `company_name` varchar(100) DEFAULT NULL COMMENT 'Only for suppliers',
  `phone` varchar(20) DEFAULT NULL COMMENT 'Only for suppliers',
  `status` enum('Active','Restricted','Inactive') DEFAULT 'Active',
  `inactive_date` date DEFAULT NULL,
  PRIMARY KEY (`user_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user` (`user_ID`, `user_name`, `email`, `password`, `user_type`, `staff_role`, `company_name`, `phone`, `status`, `inactive_date`) VALUES
('STF001', 'Ahmad Faiz', 'AhmadFaiz@gmail.com', 'Faiz123', 'staff', 'KTM Staff', NULL, NULL, 'Active', NULL),
('STF301', 'ALI BIN ABU', 'ALIKTM@gmail.com', 'Ali123', 'staff', 'Finance Officer', NULL, NULL, 'Active', NULL),
('SUP001', 'ABC Supplier Sdn Bhd', 'abc@gmail.com', 'ABC123', 'supplier', NULL, 'ABC Supplier Sdn Bhd', '0123456789', 'Active', '2025-06-03'),
('SUP73947', 'NOR SOFEA BINTI ZAMRI', 'norsofeazamri@gmail.com', '$2y$10$k5dMHZD52ozwmKXeaI8qAebVzg4KOuXwsChXB1OEIX00TLqbOtXJW', 'supplier', NULL, 'LNS TECH SDN BHD', '+60146054016', 'Active', NULL);

-- --------------------------------------------------------
-- Table structure for table `ktmb_staff`  ***THE MISSING TABLE***
-- Canonical staff table used by m1/staff_login.php and by every Module 4
-- file (AuditModel, AuditController, assign_reviewer, review_history,
-- audit_log). role values must be one of the three below so that
-- sidebar.php's keyword match ('admin'/'manager' -> Admin workspace,
-- 'finance' -> Finance workspace, else -> Officer workspace) routes the
-- logged-in staff member to the correct sidebar.
-- --------------------------------------------------------

CREATE TABLE `ktmb_staff` (
  `staff_ID` varchar(20) NOT NULL,
  `staff_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'bcrypt hash — verified via password_verify()',
  `role` enum('Procurement Officer','Finance Officer','Administrator') NOT NULL DEFAULT 'Procurement Officer',
  PRIMARY KEY (`staff_ID`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed accounts (passwords shown are the PLAINTEXT login passwords —
-- the column itself stores a bcrypt hash as required by password_verify()):
--   STF001 / Faiz123   -> Procurement Officer  -> KTM Officer sidebar
--   STF301 / Ali123    -> Finance Officer      -> Finance Officer sidebar
--   ADM001 / Admin123  -> Administrator        -> Admin sidebar
INSERT INTO `ktmb_staff` (`staff_ID`, `staff_name`, `email`, `password`, `role`) VALUES
('STF001', 'Ahmad Faiz', 'AhmadFaiz@gmail.com', '$2y$10$qNU1LsLOMwF7VwSLcvE.me5WWSa6vl0t.J0hgw6URDILXgaQ5BPFi', 'Procurement Officer'),
('STF301', 'Ali Bin Abu', 'ALIKTM@gmail.com', '$2y$10$sBlqwD/s1ddm9ddlSVP4CukbXM.oBTPYDYGcaGuSzDjHrHxKLP9om', 'Finance Officer'),
('ADM001', 'Siti Admin', 'admin@ktmb.com.my', '$2y$10$nbnqjT1Pc0wDS76kk9CndOvIBQMdRahAoM9PztB9AvdpBNnT89aT6', 'Administrator');

-- --------------------------------------------------------
-- Table structure for table `customer`
-- --------------------------------------------------------

CREATE TABLE `customer` (
  `customer_ID` varchar(20) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `billing_address` varchar(200) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`customer_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `customer` (`customer_ID`, `customer_name`, `company_name`, `billing_address`, `status`, `last_login`) VALUES
('CUS001', 'KTMB Procurement Unit', 'KTMB', 'Jalan Sultan Hishamuddin, Kuala Lumpur', 'Active', '2026-06-19 23:32:08');

-- --------------------------------------------------------
-- Table structure for table `supplier`
-- --------------------------------------------------------

CREATE TABLE `supplier` (
  `supplier_ID` varchar(8) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Active','Restricted','Inactive') DEFAULT 'Active',
  `inactive_date` date DEFAULT NULL,
  PRIMARY KEY (`supplier_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `supplier` (`supplier_ID`, `supplier_name`, `company_name`, `phone`, `email`, `password`, `status`, `inactive_date`) VALUES
('SUP001', 'ABC Supplier Sdn Bhd', 'ABC Supplier Sdn Bhd', '0123456789', 'abc@gmail.com', 'ABC123', 'Active', '2025-06-03'),
('SUP73947', 'NOR SOFEA BINTI ZAMRI', 'LNS TECH SDN BHD', '+60146054016', 'norsofeazamri@gmail.com', '$2y$10$k5dMHZD52ozwmKXeaI8qAebVzg4KOuXwsChXB1OEIX00TLqbOtXJW', 'Active', NULL);

-- --------------------------------------------------------
-- Table structure for table `purchase_order` (was duplicated — now once)
-- --------------------------------------------------------

CREATE TABLE `purchase_order` (
  `PO_ID` varchar(8) NOT NULL,
  `customer_ID` varchar(20) NOT NULL,
  `PO_amount` decimal(12,2) NOT NULL,
  `PO_status` enum('Pending','Approved','Completed','Cancelled') DEFAULT 'Pending',
  PRIMARY KEY (`PO_ID`),
  KEY `fk_po_customer` (`customer_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `purchase_order` (`PO_ID`, `customer_ID`, `PO_amount`, `PO_status`) VALUES
('PO001', 'CUS001', 15000.00, 'Approved');

-- --------------------------------------------------------
-- Table structure for table `delivery_order`
-- FIX: column is `supplier_ID` (FK -> supplier.supplier_ID), matching
-- every query in m2/DOModel.php, m2/create_do.php and all of Module 4.
-- --------------------------------------------------------

CREATE TABLE `delivery_order` (
  `DO_ID` varchar(20) NOT NULL,
  `supplier_ID` varchar(8) NOT NULL COMMENT 'References the supplier from supplier table',
  `PO_ID` varchar(8) NOT NULL,
  `customer_ID` varchar(20) NOT NULL,
  `PO_number` varchar(30) NOT NULL,
  `PO_status` varchar(20) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `project_reference` varchar(50) DEFAULT NULL,
  `proof_of_delivery` varchar(255) NOT NULL,
  PRIMARY KEY (`DO_ID`),
  KEY `fk_do_supplier` (`supplier_ID`),
  KEY `fk_do_po` (`PO_ID`),
  KEY `fk_do_customer` (`customer_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `delivery_order` (`DO_ID`, `supplier_ID`, `PO_ID`, `customer_ID`, `PO_number`, `PO_status`, `created_date`, `project_reference`, `proof_of_delivery`) VALUES
('DO001', 'SUP001', 'PO001', 'CUS001', 'PO2026001', 'Approved', '2026-06-19 15:32:08', 'PRJ-KTM-001', 'uploads/do001.pdf'),
('DO002', 'SUP73947', 'PO001', 'CUS001', 'PO2026001', 'Pending', '2026-06-25 09:12:00', 'PRJ-KTM-002', 'uploads/do002.pdf');

-- --------------------------------------------------------
-- Table structure for table `invoice`
-- --------------------------------------------------------

CREATE TABLE `invoice` (
  `invoice_ID` int(11) NOT NULL AUTO_INCREMENT,
  `DO_ID` varchar(20) NOT NULL,
  `billing_address` varchar(200) NOT NULL,
  `invoice_num` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `invoice_date` datetime NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `tax` decimal(12,2) NOT NULL,
  `credit_note` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `invoice_status` enum('Submitted','Under Review','Approved','Rejected','Finance Review') DEFAULT 'Submitted',
  `payment_status` enum('Pending','Processing','Paid') DEFAULT 'Pending',
  `reason` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`invoice_ID`),
  UNIQUE KEY `invoice_num` (`invoice_num`),
  KEY `fk_invoice_do` (`DO_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=4;

INSERT INTO `invoice` (`invoice_ID`, `DO_ID`, `billing_address`, `invoice_num`, `description`, `invoice_date`, `subtotal`, `tax`, `credit_note`, `total`, `invoice_status`, `payment_status`, `reason`) VALUES
(1, 'DO001', 'Jalan Sultan Hishamuddin, Kuala Lumpur', 'INV2026001', 'Supply of railway maintenance equipment', '2026-06-19 23:32:08', 15000.00, 900.00, 0.00, 15900.00, 'Finance Review', 'Pending', NULL),
(2, 'DO001', 'Jalan Sultan Hishamuddin, Kuala Lumpur', 'INV2026002', 'Track ballast supply — batch 2', '2026-06-22 10:15:00', 8000.00, 480.00, 0.00, 8480.00, 'Approved', 'Processing', NULL),
(3, 'DO002', 'Jalan Sultan Hishamuddin, Kuala Lumpur', 'INV2026003', 'Signal relay units', '2026-06-24 14:00:00', 5200.00, 312.00, 0.00, 5512.00, 'Submitted', 'Pending', NULL);

-- --------------------------------------------------------
-- Table structure for table `audit_log`
-- --------------------------------------------------------

CREATE TABLE `audit_log` (
  `log_ID` int(11) NOT NULL AUTO_INCREMENT,
  `user_ID` varchar(20) NOT NULL COMMENT 'References the staff who performed the action (ktmb_staff.staff_ID)',
  `invoice_ID` int(11) NOT NULL,
  `action` varchar(30) NOT NULL,
  `record_ID` varchar(20) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`log_ID`),
  KEY `fk_audit_user` (`user_ID`),
  KEY `fk_audit_invoice` (`invoice_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=3;

INSERT INTO `audit_log` (`log_ID`, `user_ID`, `invoice_ID`, `action`, `record_ID`, `timestamp`) VALUES
(1, 'STF001', 1, 'Verified', 'TRX-20260620093000', '2026-06-20 09:30:00'),
(2, 'STF301', 2, 'Approved', 'TRX-20260623110000', '2026-06-23 11:00:00');

-- --------------------------------------------------------
-- Table structure for table `notification`
-- --------------------------------------------------------

CREATE TABLE `notification` (
  `noti_ID` int(11) NOT NULL AUTO_INCREMENT,
  `customer_ID` varchar(20) NOT NULL,
  `type` varchar(30) NOT NULL,
  `content` text NOT NULL,
  `status` enum('Unread','Read') DEFAULT 'Unread',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`noti_ID`),
  KEY `fk_notification_customer` (`customer_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=2;

INSERT INTO `notification` (`noti_ID`, `customer_ID`, `type`, `content`, `status`, `created_at`) VALUES
(1, 'CUS001', 'Invoice', 'Invoice INV2026001 has been submitted.', 'Unread', '2026-06-19 23:32:08');

-- --------------------------------------------------------
-- Constraints
-- --------------------------------------------------------

ALTER TABLE `purchase_order`
  ADD CONSTRAINT `fk_po_customer` FOREIGN KEY (`customer_ID`) REFERENCES `customer` (`customer_ID`) ON UPDATE CASCADE;

ALTER TABLE `delivery_order`
  ADD CONSTRAINT `fk_do_supplier` FOREIGN KEY (`supplier_ID`) REFERENCES `supplier` (`supplier_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_do_po` FOREIGN KEY (`PO_ID`) REFERENCES `purchase_order` (`PO_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_do_customer` FOREIGN KEY (`customer_ID`) REFERENCES `customer` (`customer_ID`) ON UPDATE CASCADE;

ALTER TABLE `invoice`
  ADD CONSTRAINT `fk_invoice_do` FOREIGN KEY (`DO_ID`) REFERENCES `delivery_order` (`DO_ID`) ON UPDATE CASCADE;

ALTER TABLE `audit_log`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_ID`) REFERENCES `ktmb_staff` (`staff_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_audit_invoice` FOREIGN KEY (`invoice_ID`) REFERENCES `invoice` (`invoice_ID`) ON UPDATE CASCADE;

ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_customer` FOREIGN KEY (`customer_ID`) REFERENCES `customer` (`customer_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
