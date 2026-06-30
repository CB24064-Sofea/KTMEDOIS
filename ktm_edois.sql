-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jun 30, 2026 at 04:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ktm_edois`
--

-- --------------------------------------------------------

--
-- Table structure for table `user` (Unified for KTMB Staff & Suppliers)
--

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

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_ID`, `user_name`, `email`, `password`, `user_type`, `staff_role`, `company_name`, `phone`, `status`, `inactive_date`) VALUES
('STF001', 'Ahmad Faiz', 'AhmadFaiz@gmail.com', 'Faiz123', 'staff', 'KTM Staff', NULL, NULL, 'Active', NULL),
('STF301', 'ALI BIN ABU', 'ALIKTM@gmail.com', 'Ali123', 'staff', 'Finance Officer', NULL, NULL, 'Active', NULL),
('SUP001', 'ABC Supplier Sdn Bhd', 'abc@gmail.com', 'ABC123', 'supplier', NULL, 'ABC Supplier Sdn Bhd', '0123456789', 'Active', '2025-06-03'),
('SUP73947', 'NOR SOFEA BINTI ZAMRI', 'norsofeazamri@gmail.com', '$2y$10$k5dMHZD52ozwmKXeaI8qAebVzg4KOuXwsChXB1OEIX00TLqbOtXJW', 'supplier', NULL, 'LNS TECH SDN BHD', '+60146054016', 'Active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_ID` varchar(20) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `billing_address` varchar(200) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_ID`, `customer_name`, `company_name`, `billing_address`, `status`, `last_login`) VALUES
('CUS001', 'KTMB Procurement Unit', 'KTMB', 'Jalan Sultan Hishamuddin, Kuala Lumpur', 'Active', '2026-06-19 23:32:08');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order`
--

CREATE TABLE `purchase_order` (
  `PO_ID` varchar(8) NOT NULL,
  `customer_ID` varchar(20) NOT NULL,
  `PO_amount` decimal(12,2) NOT NULL,
  `PO_status` enum('Pending','Approved','Completed','Cancelled') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order`
--

INSERT INTO `purchase_order` (`PO_ID`, `customer_ID`, `PO_amount`, `PO_status`) VALUES
('PO001', 'CUS001', 15000.00, 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_order`
--

CREATE TABLE `delivery_order` (
  `DO_ID` varchar(20) NOT NULL,
  `user_ID` varchar(20) NOT NULL COMMENT 'References the supplier from user table',
  `PO_ID` varchar(8) NOT NULL,
  `customer_ID` varchar(20) NOT NULL,
  `PO_number` varchar(30) NOT NULL,
  `PO_status` varchar(20) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `project_reference` varchar(50) DEFAULT NULL,
  `proof_of_delivery` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_order`
--

INSERT INTO `delivery_order` (`DO_ID`, `user_ID`, `PO_ID`, `customer_ID`, `PO_number`, `PO_status`, `created_date`, `project_reference`, `proof_of_delivery`) VALUES
('DO001', 'SUP001', 'PO001', 'CUS001', 'PO2026001', 'Approved', '2026-06-19 15:32:08', 'PRJ-KTM-001', 'uploads/do001.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `invoice_ID` int(11) NOT NULL,
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
  `reason` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice`
--

INSERT INTO `invoice` (`invoice_ID`, `DO_ID`, `billing_address`, `invoice_num`, `description`, `invoice_date`, `subtotal`, `tax`, `credit_note`, `total`, `invoice_status`, `payment_status`, `reason`) VALUES
(1, 'DO001', 'Jalan Sultan Hishamuddin, Kuala Lumpur', 'INV2026001', 'Supply of railway maintenance equipment', '2026-06-19 23:32:08', 15000.00, 900.00, 0.00, 15900.00, 'Submitted', 'Pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_ID` int(11) NOT NULL,
  `user_ID` varchar(20) NOT NULL COMMENT 'References the staff who performed the action',
  `invoice_ID` int(11) NOT NULL,
  `action` varchar(30) NOT NULL,
  `record_ID` varchar(20) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `ktmb_staff` (`staff_ID`, `staff_name`, `email`, `password`, `role`) VALUES
('STF001', 'Ahmad Faiz', 'AhmadFaiz@gmail.com', 'Faiz123', 'Procurement Officer'),
('STF301', 'ALI BIN ABU', 'ALIKTM@gmail.com', 'Ali123', 'Finance Officer');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `noti_ID` int(11) NOT NULL,
  `customer_ID` varchar(20) NOT NULL,
  `type` varchar(30) NOT NULL,
  `content` text NOT NULL,
  `status` enum('Unread','Read') DEFAULT 'Unread',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`noti_ID`, `customer_ID`, `type`, `content`, `status`, `created_at`) VALUES
(1, 'CUS001', 'Invoice', 'Invoice INV2026001 has been submitted.', 'Unread', '2026-06-19 23:32:08');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order`
--

CREATE TABLE `purchase_order` (
  `PO_ID` varchar(8) NOT NULL,
  `customer_ID` varchar(20) NOT NULL,
  `PO_amount` decimal(12,2) NOT NULL,
  `PO_status` enum('Pending','Approved','Completed','Cancelled') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order`
--

INSERT INTO `purchase_order` (`PO_ID`, `customer_ID`, `PO_amount`, `PO_status`) VALUES
('PO001', 'CUS001', 15000.00, 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `supplier_ID` varchar(8) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Active','Restricted','Inactive') DEFAULT 'Active',
  `inactive_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`supplier_ID`, `supplier_name`, `company_name`, `phone`, `email`, `password`, `status`, `inactive_date`) VALUES
('SUP001', 'ABC Supplier Sdn Bhd', 'ABC Supplier Sdn Bhd', '0123456789', 'abc@gmail.com', 'ABC123', 'Active', '2025-06-03'),
('SUP73947', 'NOR SOFEA BINTI ZAMRI', 'LNS TECH SDN BHD', '+60146054016', 'norsofeazamri@gmail.com', '$2y$10$k5dMHZD52ozwmKXeaI8qAebVzg4KOuXwsChXB1OEIX00TLqbOtXJW', 'Active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_ID`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_ID`);

--
-- Indexes for table `purchase_order`
--
ALTER TABLE `purchase_order`
  ADD PRIMARY KEY (`PO_ID`),
  ADD KEY `fk_po_customer` (`customer_ID`);

--
-- Indexes for table `delivery_order`
--
ALTER TABLE `delivery_order`
  ADD PRIMARY KEY (`DO_ID`),
  ADD KEY `fk_do_user` (`user_ID`),
  ADD KEY `fk_do_po` (`PO_ID`),
  ADD KEY `fk_do_customer` (`customer_ID`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`invoice_ID`),
  ADD UNIQUE KEY `invoice_num` (`invoice_num`),
  ADD KEY `fk_invoice_do` (`DO_ID`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_ID`),
  ADD KEY `fk_audit_user` (`user_ID`),
  ADD KEY `fk_audit_invoice` (`invoice_ID`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`noti_ID`),
  ADD KEY `fk_notification_customer` (`customer_ID`);

--
-- Indexes for table `purchase_order`
--
ALTER TABLE `purchase_order`
  ADD PRIMARY KEY (`PO_ID`),
  ADD KEY `fk_po_customer` (`customer_ID`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`supplier_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `invoice_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `noti_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `UserID` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `purchase_order`
--
ALTER TABLE `purchase_order`
  ADD CONSTRAINT `fk_po_customer` FOREIGN KEY (`customer_ID`) REFERENCES `customer` (`customer_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `delivery_order`
--
ALTER TABLE `delivery_order`
  ADD CONSTRAINT `fk_do_user` FOREIGN KEY (`user_ID`) REFERENCES `user` (`user_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_do_po` FOREIGN KEY (`PO_ID`) REFERENCES `purchase_order` (`PO_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_do_customer` FOREIGN KEY (`customer_ID`) REFERENCES `customer` (`customer_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `fk_invoice_do` FOREIGN KEY (`DO_ID`) REFERENCES `delivery_order` (`DO_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_ID`) REFERENCES `user` (`user_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_audit_invoice` FOREIGN KEY (`invoice_ID`) REFERENCES `invoice` (`invoice_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_customer` FOREIGN KEY (`customer_ID`) REFERENCES `customer` (`customer_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;