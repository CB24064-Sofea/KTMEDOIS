-- =====================================================
-- KTM eDOIS DATABASE
-- Kereta Api Tanah Melayu Electronic Delivery Order &
-- Invoice System
-- Version: 1.0
-- =====================================================

DROP DATABASE IF EXISTS ktm_edois;
CREATE DATABASE ktm_edois;
USE ktm_edois;

-- =====================================================
-- TABLE: SUPPLIER
-- MODULE 1 : Vendor Registry Integration
-- =====================================================

CREATE TABLE supplier (
    supplier_ID VARCHAR(8) PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    company_name VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    status ENUM('Active','Restricted','Inactive') DEFAULT 'Active',
    inactive_date DATE
);

-- =====================================================
-- TABLE: CUSTOMER
-- =====================================================

CREATE TABLE customer (
    customer_ID VARCHAR(20) PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    company_name VARCHAR(100),
    billing_address VARCHAR(200),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    last_login DATETIME
);

-- =====================================================
-- TABLE: KTMB_STAFF
-- =====================================================

CREATE TABLE ktmb_staff (
    staff_ID VARCHAR(10) PRIMARY KEY,
    staff_name VARCHAR(100) NOT NULL,
    role ENUM(
        'Procurement Officer',
        'Finance Officer',
        'Administrator',
        'Manager'
    ) NOT NULL
);

-- =====================================================
-- TABLE: PURCHASE_ORDER
-- =====================================================

CREATE TABLE purchase_order (
    PO_ID VARCHAR(8) PRIMARY KEY,
    customer_ID VARCHAR(20) NOT NULL,
    PO_amount DECIMAL(12,2) NOT NULL,
    PO_status ENUM(
        'Pending',
        'Approved',
        'Completed',
        'Cancelled'
    ) DEFAULT 'Pending',

    CONSTRAINT fk_po_customer
    FOREIGN KEY (customer_ID)
    REFERENCES customer(customer_ID)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
);

-- =====================================================
-- TABLE: DELIVERY_ORDER
-- MODULE 2 : Manage DO Submission
-- =====================================================

CREATE TABLE delivery_order (

    DO_ID VARCHAR(20) PRIMARY KEY,

    supplier_ID VARCHAR(8) NOT NULL,

    PO_ID VARCHAR(8) NOT NULL,

    customer_ID VARCHAR(20) NOT NULL,

    PO_number VARCHAR(30) NOT NULL,

    PO_status VARCHAR(20) NOT NULL,

    created_date TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP,

    project_reference VARCHAR(50),

    proof_of_delivery VARCHAR(255) NOT NULL,

    CONSTRAINT fk_do_supplier
    FOREIGN KEY (supplier_ID)
    REFERENCES supplier(supplier_ID)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    CONSTRAINT fk_do_po
    FOREIGN KEY (PO_ID)
    REFERENCES purchase_order(PO_ID)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    CONSTRAINT fk_do_customer
    FOREIGN KEY (customer_ID)
    REFERENCES customer(customer_ID)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
);

-- =====================================================
-- TABLE: INVOICE
-- MODULE 3 : Manage Invoice & Claim
-- =====================================================

CREATE TABLE invoice (

    invoice_ID INT AUTO_INCREMENT PRIMARY KEY,

    DO_ID VARCHAR(20) NOT NULL,

    billing_address VARCHAR(200) NOT NULL,

    invoice_num VARCHAR(20)
    UNIQUE NOT NULL,

    description TEXT,

    invoice_date DATETIME NOT NULL,

    subtotal DECIMAL(12,2) NOT NULL,

    tax DECIMAL(12,2) NOT NULL,

    credit_note DECIMAL(12,2)
    DEFAULT 0.00,

    total DECIMAL(12,2) NOT NULL,

    invoice_status ENUM(
        'Submitted',
        'Under Review',
        'Approved',
        'Rejected',
        'Finance Review'
    ) DEFAULT 'Submitted',

    payment_status ENUM(
        'Pending',
        'Processing',
        'Paid'
    ) DEFAULT 'Pending',

    reason VARCHAR(200),

    CONSTRAINT fk_invoice_do
    FOREIGN KEY (DO_ID)
    REFERENCES delivery_order(DO_ID)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
);

-- =====================================================
-- TABLE: NOTIFICATION
-- =====================================================

CREATE TABLE notification (

    noti_ID INT AUTO_INCREMENT PRIMARY KEY,

    customer_ID VARCHAR(20) NOT NULL,

    type VARCHAR(30) NOT NULL,

    content TEXT NOT NULL,

    status ENUM(
        'Unread',
        'Read'
    ) DEFAULT 'Unread',

    created_at DATETIME
    DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notification_customer
    FOREIGN KEY (customer_ID)
    REFERENCES customer(customer_ID)
    ON UPDATE CASCADE
    ON DELETE CASCADE
);

-- =====================================================
-- TABLE: AUDIT_LOG
-- MODULE 4 : Internal Review & Approval Workflow
-- =====================================================

CREATE TABLE audit_log (

    log_ID INT AUTO_INCREMENT PRIMARY KEY,

    staff_ID VARCHAR(10) NOT NULL,

    invoice_ID INT NOT NULL,

    action VARCHAR(30) NOT NULL,

    record_ID VARCHAR(20) NOT NULL,

    timestamp DATETIME
    DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_staff
    FOREIGN KEY (staff_ID)
    REFERENCES ktmb_staff(staff_ID)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    CONSTRAINT fk_audit_invoice
    FOREIGN KEY (invoice_ID)
    REFERENCES invoice(invoice_ID)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
);

-- =====================================================
-- SAMPLE DATA
-- =====================================================

INSERT INTO supplier VALUES
('SUP001','ABC Supplier Sdn Bhd',
 'ABC Supplier Sdn Bhd',
 '0123456789',
 'abc@gmail.com',
 'Active',
 NULL);

INSERT INTO customer VALUES
('CUS001',
 'KTMB Procurement Unit',
 'KTMB',
 'Jalan Sultan Hishamuddin, Kuala Lumpur',
 'Active',
 NOW());

INSERT INTO ktmb_staff VALUES
('STF001',
 'Ahmad Faiz',
 'Procurement Officer');

INSERT INTO purchase_order VALUES
('PO001',
 'CUS001',
 15000.00,
 'Approved');

INSERT INTO delivery_order VALUES
(
 'DO001',
 'SUP001',
 'PO001',
 'CUS001',
 'PO2026001',
 'Approved',
 NOW(),
 'PRJ-KTM-001',
 'uploads/do001.pdf'
);

INSERT INTO invoice
(
DO_ID,
billing_address,
invoice_num,
description,
invoice_date,
subtotal,
tax,
credit_note,
total,
invoice_status,
payment_status
)
VALUES
(
'DO001',
'Jalan Sultan Hishamuddin, Kuala Lumpur',
'INV2026001',
'Supply of railway maintenance equipment',
NOW(),
15000.00,
900.00,
0.00,
15900.00,
'Submitted',
'Pending'
);

INSERT INTO notification
(
customer_ID,
type,
content
)
VALUES
(
'CUS001',
'Invoice',
'Invoice INV2026001 has been submitted.'
);

INSERT INTO audit_log
(
staff_ID,
invoice_ID,
action,
record_ID
)
VALUES
(
'STF001',
1,
'Verified',
'INV2026001'
);

-- =====================================================
-- END OF KTM eDOIS DATABASE
-- =====================================================