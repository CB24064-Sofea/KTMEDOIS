<?php
// =========================================================================
// FILE        : InvoiceModel.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : SDD_CLS_405 (Model Layer) — Invoice Entity
// DESCRIPTION : OOP Model class. Handles all raw database read/write
//               operations on the invoice and related tables.
//               No business logic here — only DB queries.
// =========================================================================

class InvoiceModel {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // ── fetchPendingClaims() — SDD_CLS_401 officerMainDashboardUI ────────────
    // Returns all invoices, optionally filtered by status
    public function fetchPendingClaims($filterStatus = 'all') {
        if ($filterStatus !== 'all') {
            $safe = $this->db->real_escape_string($filterStatus);
            $sql = "SELECT i.invoice_ID, i.invoice_num, i.DO_ID, i.total,
                           i.invoice_status, i.invoice_date, i.payment_status,
                           s.supplier_name
                    FROM invoice i
                    INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
                    INNER JOIN supplier s ON d.supplier_ID = s.supplier_ID
                    WHERE i.invoice_status = '$safe'
                    ORDER BY i.invoice_ID DESC";
        } else {
            $sql = "SELECT i.invoice_ID, i.invoice_num, i.DO_ID, i.total,
                           i.invoice_status, i.invoice_date, i.payment_status,
                           s.supplier_name
                    FROM invoice i
                    INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
                    INNER JOIN supplier s ON d.supplier_ID = s.supplier_ID
                    ORDER BY i.invoice_ID DESC";
        }
        return $this->db->query($sql);
    }

    // ── Dashboard stat counts ─────────────────────────────────────────────────
    public function getDashboardStats() {
        $q = function($sql) { return $this->db->query($sql)->fetch_assoc()['c']; };
        return [
            'total'          => $q("SELECT COUNT(*) as c FROM invoice"),
            'submitted'      => $q("SELECT COUNT(*) as c FROM invoice WHERE invoice_status='Submitted'"),
            'reviewing'      => $q("SELECT COUNT(*) as c FROM invoice WHERE invoice_status='Under Review'"),
            'finance'        => $q("SELECT COUNT(*) as c FROM invoice WHERE invoice_status='Finance Review'"),
            'approved'       => $q("SELECT COUNT(*) as c FROM invoice WHERE invoice_status='Approved'"),
            'rejected'       => $q("SELECT COUNT(*) as c FROM invoice WHERE invoice_status='Rejected'"),
            'pending_pay'    => $q("SELECT COUNT(*) as c FROM invoice WHERE invoice_status='Approved' AND payment_status != 'Paid'"),
            'paid'           => $q("SELECT COUNT(*) as c FROM invoice WHERE payment_status='Paid'"),
        ];
    }

    // ── getInvoiceById() — SDD_CLS_402 reviewSubmissionUI ────────────────────
    // Fetches full invoice + linked delivery order + supplier data
    public function getInvoiceById($invoiceId) {
        $stmt = $this->db->prepare(
            "SELECT i.*, d.supplier_ID AS supplier_ID, d.PO_ID, d.proof_of_delivery, d.PO_number,
                    s.supplier_name, s.email as supplier_email
             FROM invoice i
             INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
             INNER JOIN supplier s ON d.supplier_ID = s.supplier_ID
             WHERE i.invoice_ID = ? LIMIT 1"
        );
        $stmt->bind_param("i", $invoiceId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    // ── updateStatus() — SDD_CLS_405 ReviewAndApprovalController ─────────────
    // Updates invoice_status in the database.
    public function updateStatus($invoiceId, $actionType, $remarks) {
        if ($actionType === 'Verified') {
            $stmt = $this->db->prepare(
                "UPDATE invoice SET invoice_status = 'Finance Review' WHERE invoice_ID = ?"
            );
            $stmt->bind_param("i", $invoiceId);
        } elseif ($actionType === 'Approved') {
            $stmt = $this->db->prepare(
                "UPDATE invoice SET invoice_status = 'Approved', payment_status = 'Processing' WHERE invoice_ID = ?"
            );
            $stmt->bind_param("i", $invoiceId);
        } elseif ($actionType === 'UnderReview') {
            $stmt = $this->db->prepare(
                "UPDATE invoice SET invoice_status = 'Under Review' WHERE invoice_ID = ?"
            );
            $stmt->bind_param("i", $invoiceId);
        } else {
            // Rejected
            $stmt = $this->db->prepare(
                "UPDATE invoice SET invoice_status = 'Rejected', reason = ? WHERE invoice_ID = ?"
            );
            $stmt->bind_param("si", $remarks, $invoiceId);
        }
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // ── checkInvoiceStatus() — validate reviewable state ─────────────────────
    public function checkInvoiceStatus($invoiceId) {
        $stmt = $this->db->prepare(
            "SELECT invoice_status FROM invoice WHERE invoice_ID = ? LIMIT 1"
        );
        $stmt->bind_param("i", $invoiceId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    // ── getInvoiceWithCustomer() — for notification sending ──────────────────
    public function getInvoiceWithCustomer($invoiceId) {
        $stmt = $this->db->prepare(
            "SELECT i.invoice_num, i.DO_ID, d.customer_ID
             FROM invoice i
             INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
             WHERE i.invoice_ID = ? LIMIT 1"
        );
        $stmt->bind_param("i", $invoiceId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    // ── insertNotification() — triggerNotifications() ────────────────────────
    public function insertNotification($customerId, $type, $content) {
        $stmt = $this->db->prepare(
            "INSERT INTO notification (customer_ID, type, content) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $customerId, $type, $content);
        $stmt->execute();
        $stmt->close();
    }

    // ── fetchReportData() — SDD_CLS_404 generateReportUI ────────────────────
    // Retrieves filtered records for the management report
    public function fetchReportData($dateFrom, $dateTo, $status) {
        $whereParts = [];
        if (!empty($dateFrom)) $whereParts[] = "i.invoice_date >= '" . $this->db->real_escape_string($dateFrom) . " 00:00:00'";
        if (!empty($dateTo))   $whereParts[] = "i.invoice_date <= '" . $this->db->real_escape_string($dateTo)   . " 23:59:59'";
        if ($status !== 'all') $whereParts[] = "i.invoice_status = '" . $this->db->real_escape_string($status) . "'";
        $where = !empty($whereParts) ? 'WHERE ' . implode(' AND ', $whereParts) : '';

        $sql = "SELECT i.invoice_num, i.DO_ID, i.total, i.invoice_status,
                       i.invoice_date, i.payment_status, s.supplier_name
                FROM invoice i
                INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
                INNER JOIN supplier s ON d.supplier_ID = s.supplier_ID
                $where
                ORDER BY i.invoice_date DESC";
        $result = $this->db->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) $data[] = $row;
        }
        return $data;
    }

    // ── fetchPaymentQueue() — invoices fully Approved but not yet Paid ───────
    // SDD CLASS: used by processPaymentUI (Finance Officer "Process payments")
    public function fetchPaymentQueue() {
        $sql = "SELECT i.invoice_ID, i.invoice_num, i.DO_ID, i.total,
                       i.invoice_status, i.invoice_date, i.payment_status,
                       s.supplier_name
                FROM invoice i
                INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
                INNER JOIN supplier s ON d.supplier_ID = s.supplier_ID
                WHERE i.invoice_status = 'Approved' AND i.payment_status != 'Paid'
                ORDER BY i.invoice_ID DESC";
        return $this->db->query($sql);
    }

    // ── markAsPaid() — Finance Officer completes payment disbursement ────────
    // Only allowed once an invoice has been fully Approved.
    public function markAsPaid($invoiceId) {
        $stmt = $this->db->prepare(
            "UPDATE invoice SET payment_status = 'Paid'
             WHERE invoice_ID = ? AND invoice_status = 'Approved'"
        );
        $stmt->bind_param("i", $invoiceId);
        $success = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }

    // ── getFinanceSummary() — finance stats for dashboard ────────────────────
    public function getFinanceSummary() {
        $total_val  = $this->db->query("SELECT COALESCE(SUM(total),0) as t FROM invoice")->fetch_assoc()['t'];
        $paid_val   = $this->db->query("SELECT COALESCE(SUM(total),0) as t FROM invoice WHERE payment_status='Paid'")->fetch_assoc()['t'];
        $pending_val= $this->db->query("SELECT COALESCE(SUM(total),0) as t FROM invoice WHERE invoice_status='Finance Review'")->fetch_assoc()['t'];
        return [
            'total_value'   => $total_val,
            'paid_value'    => $paid_val,
            'pending_value' => $pending_val,
        ];
    }
}
