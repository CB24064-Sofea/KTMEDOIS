<?php
// =========================================================================
// FILE        : AuditModel.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : SDD_CLS_406 — AuditController (Model Layer)
// DESCRIPTION : OOP Model class for the audit_log table.
//               Handles inserting and retrieving compliance log records.
// =========================================================================

class AuditModel {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // ── writeLogEntry() — SDD_CLS_406 AuditController ────────────────────────
    // Inserts a new audit log row. record_ID = TRX- + timestamp (unique).
    // log_ID is AUTO_INCREMENT — never manually assigned.
    public function writeLogEntry($staffId, $actionType, $invoiceId) {
        $recordId = 'TRX-' . date('YmdHis');
        $stmt = $this->db->prepare(
            "INSERT INTO audit_log (staff_ID, invoice_ID, action, record_ID)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("siss", $staffId, $invoiceId, $actionType, $recordId);
        $success = $stmt->execute();
        if (!$success) {
            error_log("Audit log insert failed for invoice_ID: $invoiceId — " . $this->db->error);
        }
        $stmt->close();
        return $success;
    }

    // ── fetchLogData() — SDD_CLS_403 auditLogUI ──────────────────────────────
    // Returns audit log entries with optional search filter.
    public function fetchLogData($search = '') {
        if (!empty($search)) {
            $safe = $this->db->real_escape_string($search);
            $sql = "SELECT a.log_ID, a.staff_ID, a.invoice_ID, a.action,
                           a.record_ID, a.timestamp,
                           k.staff_name, i.invoice_num
                    FROM audit_log a
                    INNER JOIN ktmb_staff k ON a.staff_ID  = k.staff_ID
                    INNER JOIN invoice    i ON a.invoice_ID = i.invoice_ID
                    WHERE a.staff_ID LIKE '%$safe%' OR i.invoice_num LIKE '%$safe%'
                    ORDER BY a.timestamp DESC";
        } else {
            $sql = "SELECT a.log_ID, a.staff_ID, a.invoice_ID, a.action,
                           a.record_ID, a.timestamp,
                           k.staff_name, i.invoice_num
                    FROM audit_log a
                    INNER JOIN ktmb_staff k ON a.staff_ID  = k.staff_ID
                    INNER JOIN invoice    i ON a.invoice_ID = i.invoice_ID
                    ORDER BY a.timestamp DESC";
        }
        return $this->db->query($sql);
    }

    // ── fetchAuditStats() — dashboard stat cards for auditLogUI ─────────────
    public function fetchAuditStats() {
        return [
            'total_logs'  => $this->db->query("SELECT COUNT(*) as c FROM audit_log")->fetch_assoc()['c'],
            'approved_td' => $this->db->query("SELECT COUNT(*) as c FROM audit_log WHERE action='Verified' AND DATE(timestamp)=CURDATE()")->fetch_assoc()['c'],
            'rejected_td' => $this->db->query("SELECT COUNT(*) as c FROM audit_log WHERE action='Rejected' AND DATE(timestamp)=CURDATE()")->fetch_assoc()['c'],
            'officers'    => $this->db->query("SELECT COUNT(*) as c FROM ktmb_staff WHERE role='Procurement Officer'")->fetch_assoc()['c'],
        ];
    }

    // ── fetchAuditHistoryForInvoice() — timeline in reviewSubmissionUI ────────
    public function fetchAuditHistoryForInvoice($invoiceId) {
        $stmt = $this->db->prepare(
            "SELECT a.action, a.record_ID, a.timestamp, k.staff_name
             FROM audit_log a
             INNER JOIN ktmb_staff k ON a.staff_ID = k.staff_ID
             WHERE a.invoice_ID = ?
             ORDER BY a.timestamp DESC"
        );
        $stmt->bind_param("i", $invoiceId);
        $stmt->execute();
        $res = $stmt->get_result();
        $history = [];
        while ($r = $res->fetch_assoc()) $history[] = $r;
        $stmt->close();
        return $history;
    }
}
