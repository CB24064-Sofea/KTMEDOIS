-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Jun 30, 2026 at 03:50 AM
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
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_ID` int(11) NOT NULL,
  `staff_ID` varchar(10) NOT NULL,
  `invoice_ID` int(11) NOT NULL,
  `action` varchar(30) NOT NULL,
  `record_ID` varchar(20) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_ID`, `staff_ID`, `invoice_ID`, `action`, `record_ID`, `timestamp`) VALUES
(1, 'STF001', 1, 'Verified', 'INV2026001', '2026-06-30 08:20:47');

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
('CUS001', 'KTMB Procurement Unit', 'KTMB', 'Jalan Sultan Hishamuddin, Kuala Lumpur', 'Active', '2026-06-30 08:20:47');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_order`
--

CREATE TABLE `delivery_order` (
  `DO_ID` varchar(20) NOT NULL,
  `supplier_ID` varchar(8) NOT NULL,
  `PO_ID` varchar(8) NOT NULL,
  `customer_ID` varchar(20) NOT NULL,
  `PO_number` varchar(30) NOT NULL,
  `PO_status` varchar(20) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `project_reference` varchar(50) DEFAULT NULL,
  `proof_of_delivery` varchar(255) NOT NULL,
  `delivery_status` varchar(20) NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_order`
--

INSERT INTO `delivery_order` (`DO_ID`, `supplier_ID`, `PO_ID`, `customer_ID`, `PO_number`, `PO_status`, `created_date`, `project_reference`, `proof_of_delivery`, `delivery_status`) VALUES
('DO001', 'SUP001', 'PO001', 'CUS001', 'PO2026001', 'Rejected', '2026-06-30 00:20:47', 'PRJ-KTM-001', 'uploads/do001.pdf', 'Pending'),
('DO002', 'SUP001', 'PO001', 'CUS001', 'NUM-PO001', 'Approved', '2026-06-30 01:24:43', 'PRJ2002', 'uploads/do_do002.jpeg', 'Pending'),
('DO003', 'SUP001', 'PO001', 'CUS001', 'NUM-PO001', 'Approved', '2026-06-30 01:29:39', 'PRJ2002', 'uploads/do_do003.jpeg', 'Pending');

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
(1, 'DO001', 'Jalan Sultan Hishamuddin, Kuala Lumpur', 'INV2026001', 'Supply of railway maintenance equipment', '2026-06-30 08:20:47', 15000.00, 900.00, 0.00, 15900.00, 'Submitted', 'Pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ktmb_staff`
--

CREATE TABLE `ktmb_staff` (
  `staff_ID` varchar(10) NOT NULL,
  `staff_name` varchar(100) NOT NULL,
  `role` enum('Procurement Officer','Finance Officer','Administrator','Manager') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ktmb_staff`
--

INSERT INTO `ktmb_staff` (`staff_ID`, `staff_name`, `role`) VALUES
('STF001', 'Ahmad Faiz', 'Procurement Officer');

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
(1, 'CUS001', 'Invoice', 'Invoice INV2026001 has been submitted.', 'Unread', '2026-06-30 08:20:47');

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
  `status` enum('Active','Restricted','Inactive') DEFAULT 'Active',
  `inactive_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`supplier_ID`, `supplier_name`, `company_name`, `phone`, `email`, `status`, `inactive_date`) VALUES
('SUP001', 'ABC Supplier Sdn Bhd', 'ABC Supplier Sdn Bhd', '0123456789', 'abc@gmail.com', 'Active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `UserID` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `inactive_date` date DEFAULT NULL,
  `role` varchar(50) DEFAULT 'Staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UserID`, `email`, `username`, `password`, `company_name`, `phone`, `status`, `inactive_date`, `role`) VALUES
(1, 'abc@gmail.com', 'SUP001', 'password123', 'ABC Supplier Sdn Bhd', '0123456789', 'Active', NULL, 'Supplier'),
(2, 'staff@ktmb.com.my', 'STF001', 'password123', 'KTMB', NULL, 'Active', NULL, 'Procurement Officer');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_ID`),
  ADD KEY `fk_audit_staff` (`staff_ID`),
  ADD KEY `fk_audit_invoice` (`invoice_ID`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_ID`);

--
-- Indexes for table `delivery_order`
--
ALTER TABLE `delivery_order`
  ADD PRIMARY KEY (`DO_ID`),
  ADD KEY `fk_do_supplier` (`supplier_ID`),
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
-- Indexes for table `ktmb_staff`
--
ALTER TABLE `ktmb_staff`
  ADD PRIMARY KEY (`staff_ID`);

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
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `email` (`email`);

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
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `fk_audit_invoice` FOREIGN KEY (`invoice_ID`) REFERENCES `invoice` (`invoice_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_audit_staff` FOREIGN KEY (`staff_ID`) REFERENCES `ktmb_staff` (`staff_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `delivery_order`
--
ALTER TABLE `delivery_order`
  ADD CONSTRAINT `fk_do_customer` FOREIGN KEY (`customer_ID`) REFERENCES `customer` (`customer_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_do_po` FOREIGN KEY (`PO_ID`) REFERENCES `purchase_order` (`PO_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_do_supplier` FOREIGN KEY (`supplier_ID`) REFERENCES `supplier` (`supplier_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `fk_invoice_do` FOREIGN KEY (`DO_ID`) REFERENCES `delivery_order` (`DO_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_customer` FOREIGN KEY (`customer_ID`) REFERENCES `customer` (`customer_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `purchase_order`
--
ALTER TABLE `purchase_order`
  ADD CONSTRAINT `fk_po_customer` FOREIGN KEY (`customer_ID`) REFERENCES `customer` (`customer_ID`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
