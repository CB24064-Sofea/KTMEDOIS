<?php
// =========================================================================
// FILE        : AuditController.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : SDD_CLS_406 — AuditController
// DESCRIPTION : Handles direct record insertions and lookups for the
//               compliance audit trail. Interacts with AuditModel (which
//               maps to the AuditLog entity DB model).
//
//               SDD_CLS_406 attributes:
//               - logRecords      : Array   (result set from fetchLogData)
//               - filterQuery     : String  (search keyword)
//               - activeStaffID   : String
//               - targetRecordID  : String  (TRX-timestamp format)
//
//               SDD_CLS_406 methods:
//               - fetchLogData()    → retrieves audit trail by filter
//               - writeLogEntry()   → instantiates new log row
// =========================================================================

require_once __DIR__ . '/AuditModel.php';

class AuditController {

    // SDD_CLS_406 attributes
    private $logRecords   = [];
    private $filterQuery  = '';
    private $activeStaffID;
    private $targetRecordID;

    private $auditModel;

    public function __construct($dbConnection, $staffId = 'STF001') {
        $this->auditModel    = new AuditModel($dbConnection);
        $this->activeStaffID = $staffId;
    }

    // ── fetchLogData() — SDD_CLS_406 ─────────────────────────────────────────
    // Retrieves audit trail items matching explicit filter variables.
    public function fetchLogData($filterQuery = '', $limit = 0, $offset = 0) {
        $this->filterQuery = $filterQuery;
        return $this->auditModel->fetchLogData($filterQuery, $limit, $offset);
    }

    // ── countLogs() — for pagination ─────────────────────────────────────────
    public function countLogs($filterQuery = '') {
        return $this->auditModel->countLogs($filterQuery);
    }

    // ── getAuditStats() — for auditLogUI stat cards ───────────────────────────
    public function getAuditStats() {
        return $this->auditModel->fetchAuditStats();
    }

    // ── writeLogEntry() — SDD_CLS_406 ────────────────────────────────────────
    // Instantiates a new log row to track a transaction.
    // Algorithm per SDD:
    //   1. Receive staffID, actionPerformed, recID
    //   2. Assign field values (log_ID is AUTO_INCREMENT — never manually assigned)
    //   3. Execute DB insert
    //   4. Return True/False
    public function writeLogEntry($staffId, $actionPerformed, $invoiceId) {
        $this->activeStaffID  = $staffId;
        $this->targetRecordID = 'TRX-' . date('YmdHis');
        return $this->auditModel->writeLogEntry($staffId, $actionPerformed, $invoiceId);
    }
}
