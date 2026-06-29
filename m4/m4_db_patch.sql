-- =====================================================
-- MODULE 4 DATABASE PATCH
-- Run this AFTER importing ktm_edois.sql
-- =====================================================

USE ktm_edois;

-- Ensure penalty column exists
ALTER TABLE invoice ADD COLUMN IF NOT EXISTS penalty DECIMAL(12,2) DEFAULT 0.00 AFTER credit_note;

-- Add 'AssignedReviewer' as a valid action (VARCHAR already, no ENUM constraint)
-- No change needed — audit_log.action is VARCHAR(30)

-- Add more staff for demo
INSERT IGNORE INTO ktmb_staff VALUES ('STF002','Siti Nora','Finance Officer');
INSERT IGNORE INTO ktmb_staff VALUES ('STF003','Hafiz Rahman','Manager');
INSERT IGNORE INTO ktmb_staff VALUES ('STF004','Zainal Abidin','Procurement Officer');

-- Additional sample invoices for demo
INSERT IGNORE INTO invoice (DO_ID, billing_address, invoice_num, description, invoice_date, subtotal, tax, credit_note, penalty, total, invoice_status, payment_status)
VALUES
('DO001','Jalan Sultan Hishamuddin, KL','INV2026002','Supply of signal equipment','2026-05-15',8000.00,480.00,0.00,80.00,8400.00,'Submitted','Pending'),
('DO001','Jalan Sultan Hishamuddin, KL','INV2026003','Track maintenance tools','2026-05-20',12000.00,720.00,200.00,0.00,12520.00,'Under Review','Pending'),
('DO001','Jalan Sultan Hishamuddin, KL','INV2026004','Station renovation materials','2026-06-01',5500.00,330.00,0.00,55.00,5775.00,'Finance Review','Pending'),
('DO001','Jalan Sultan Hishamuddin, KL','INV2026005','Safety equipment supply','2026-06-10',3200.00,192.00,0.00,0.00,3392.00,'Rejected','Pending');

-- Sample audit entries for review_history demo
INSERT IGNORE INTO audit_log (staff_ID, invoice_ID, action, record_ID) VALUES
('STF001', 2, 'Verified',        'TRX-20260515143022'),
('STF003', 3, 'UnderReview',     'TRX-20260520091145'),
('STF001', 3, 'AssignedReviewer','TRX-20260520091200'),
('STF002', 4, 'Verified',        'TRX-20260601160030'),
('STF001', 5, 'Rejected',        'TRX-20260610114500');

-- Sample notifications
INSERT IGNORE INTO notification (customer_ID, type, content) VALUES
('CUS001', 'Invoice Approved',    'Invoice INV2026002 has been approved and forwarded to Finance Review.'),
('CUS001', 'Reviewer Assigned',   'Invoice INV2026003 has been assigned to reviewer STF001 for evaluation.'),
('CUS001', 'Invoice Rejected',    'Invoice INV2026005 has been rejected. Reason: Incomplete documentation.'),
('CUS001', 'Additional Info Requested', 'Invoice INV2026003 requires additional information. Please resubmit.');

-- =====================================================
-- END OF PATCH
-- =====================================================
