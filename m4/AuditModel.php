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
    // Returns audit log entries with optional search filter and pagination.
    public function fetchLogData($search = '', $limit = 0, $offset = 0) {
        $where = '';
        if (!empty($search)) {
            $safe  = $this->db->real_escape_string($search);
            $where = "WHERE a.staff_ID LIKE '%$safe%'
                         OR i.invoice_num LIKE '%$safe%'
                         OR k.staff_name LIKE '%$safe%'
                         OR a.action LIKE '%$safe%'";
        }
        $limitClause = ($limit > 0) ? "LIMIT $limit OFFSET $offset" : '';
        $sql = "SELECT a.log_ID, a.staff_ID, a.invoice_ID, a.action,
                       a.record_ID, a.timestamp,
                       k.staff_name, i.invoice_num
                FROM audit_log a
                INNER JOIN ktmb_staff k ON a.staff_ID  = k.staff_ID
                INNER JOIN invoice    i ON a.invoice_ID = i.invoice_ID
                $where
                ORDER BY a.timestamp DESC $limitClause";
        return $this->db->query($sql);
    }

    // ── countLogs() — for pagination ─────────────────────────────────────────
    public function countLogs($search = '') {
        $where = '';
        if (!empty($search)) {
            $safe  = $this->db->real_escape_string($search);
            $where = "WHERE a.staff_ID LIKE '%$safe%'
                         OR i.invoice_num LIKE '%$safe%'
                         OR k.staff_name LIKE '%$safe%'
                         OR a.action LIKE '%$safe%'";
        }
        $sql = "SELECT COUNT(*) as c
                FROM audit_log a
                INNER JOIN ktmb_staff k ON a.staff_ID  = k.staff_ID
                INNER JOIN invoice    i ON a.invoice_ID = i.invoice_ID
                $where";
        return (int)$this->db->query($sql)->fetch_assoc()['c'];
    }

    // ── fetchAuditStats() — dashboard stat cards for auditLogUI ─────────────
    public function fetchAuditStats() {
        $q = function($sql) { return $this->db->query($sql)->fetch_assoc()['c']; };
        return [
            'total_logs'  => $q("SELECT COUNT(*) as c FROM audit_log"),
            'approved_td' => $q("SELECT COUNT(*) as c FROM audit_log WHERE action='Verified' AND DATE(timestamp)=CURDATE()"),
            'rejected_td' => $q("SELECT COUNT(*) as c FROM audit_log WHERE action='Rejected' AND DATE(timestamp)=CURDATE()"),
            'officers'    => $q("SELECT COUNT(*) as c FROM ktmb_staff WHERE role='Procurement Officer'"),
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
