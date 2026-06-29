<?php
// =========================================================================
// FILE        : ReviewAndApprovalController.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : SDD_CLS_405 — ReviewAndApprovalController
//               SDD_CLS_406 — AuditController (composed inside)
// DESCRIPTION : Central business logic controller. Receives actions from
//               the boundary (view) layer, applies workflow rules, calls
//               InvoiceModel to persist changes, calls AuditModel to write
//               the compliance log, and sends vendor notifications.
//
//               Methods map directly to SDD_CLS_405 specification:
//               - updateStatus()          → updates invoice_status
//               - routeToFinance()        → status = 'Finance Review'
//               - triggerNotifications()  → inserts notification row
//               - logActivity()           → calls AuditModel.writeLogEntry()
//               - fetchPendingClaims()    → queries invoice list
//               - setUnderReview()        → status = 'Under Review'
// =========================================================================

require_once __DIR__ . '/InvoiceModel.php';
require_once __DIR__ . '/AuditModel.php';

class ReviewAndApprovalController {

    // SDD_CLS_405 attributes
    private $activeStaffID;
    private $targetInvoiceID;
    private $invoiceModel;
    private $auditModel;

    public function __construct($dbConnection, $staffId = 'STF001') {
        $this->invoiceModel  = new InvoiceModel($dbConnection);
        $this->auditModel    = new AuditModel($dbConnection);
        $this->activeStaffID = $staffId;
    }

    // ── fetchPendingClaims() — SDD_CLS_405 ───────────────────────────────────
    // Queries DB for all invoices matching filterStatus. Called by dashboard.
    public function fetchPendingClaims($filterStatus = 'all') {
        return $this->invoiceModel->fetchPendingClaims($filterStatus);
    }

    // ── getDashboardStats() — used by officerMainDashboardUI ─────────────────
    public function getDashboardStats() {
        return $this->invoiceModel->getDashboardStats();
    }

    // ── getInvoiceForReview() — used by reviewSubmissionUI ───────────────────
    public function getInvoiceForReview($invoiceId) {
        $this->targetInvoiceID = (int)$invoiceId;
        return $this->invoiceModel->getInvoiceById($this->targetInvoiceID);
    }

    // ── getAuditHistory() — used by reviewSubmissionUI timeline ──────────────
    public function getAuditHistory($invoiceId) {
        return $this->auditModel->fetchAuditHistoryForInvoice((int)$invoiceId);
    }

    // ── updateStatus() — SDD_CLS_405 CORE METHOD ─────────────────────────────
    // Validates actionType, updates invoice status, logs audit, sends notification.
    // Returns ['success' => bool, 'message' => string]
    public function updateStatus($invoiceId, $remarks, $actionType) {
        $invoiceId = (int)$invoiceId;
        $this->targetInvoiceID = $invoiceId;

        // Step 1: Validate action value
        if (!in_array($actionType, ['Verified', 'Rejected', 'UnderReview'])) {
            return ['success' => false, 'message' => 'Invalid action type.'];
        }

        // Step 2: Check invoice exists and is in a reviewable state
        $check = $this->invoiceModel->checkInvoiceStatus($invoiceId);
        if (!$check) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }
        if (!in_array($check['invoice_status'], ['Submitted', 'Under Review'])) {
            return ['success' => false, 'message' => 'Invoice already processed.'];
        }

        // Step 3: updateStatus() in DB
        $updated = $this->invoiceModel->updateStatus($invoiceId, $actionType, $remarks);
        if (!$updated) {
            return ['success' => false, 'message' => 'Database update failed.'];
        }

        // Step 4: logActivity() → AuditController.writeLogEntry()
        $this->logActivity($actionType, $invoiceId);

        // Step 5: triggerNotifications() → send vendor alert
        $this->triggerNotifications($invoiceId, $actionType, $remarks);

        return ['success' => true, 'message' => 'Decision recorded successfully.'];
    }

    // ── routeToFinance() — SDD_CLS_405 ───────────────────────────────────────
    // Called after Verified decision — routes invoice to Finance Review.
    // In this implementation, updateStatus() already sets 'Finance Review'.
    // This method is provided explicitly to match the SDD class specification.
    public function routeToFinance($invoiceId) {
        // Already handled in updateStatus() → InvoiceModel::updateStatus('Verified')
        // This explicit method exists to satisfy SDD_CLS_405 interface contract.
        return true;
    }

    // ── setUnderReview() — SDD_CLS_405 ───────────────────────────────────────
    // Sets status to Under Review and stores reason. Explicit SDD method.
    public function setUnderReview($invoiceId, $reason) {
        return $this->updateStatus($invoiceId, $reason, 'UnderReview');
    }

    // ── logActivity() — SDD_CLS_405 → calls AuditController ─────────────────
    // Records transaction to audit_log for compliance trail.
    private function logActivity($actionType, $invoiceId) {
        $this->auditModel->writeLogEntry($this->activeStaffID, $actionType, $invoiceId);
    }

    // ── triggerNotifications() — SDD_CLS_405 ─────────────────────────────────
    // Inserts a vendor notification row based on the officer's decision.
    private function triggerNotifications($invoiceId, $actionType, $remarks) {
        $inv = $this->invoiceModel->getInvoiceWithCustomer($invoiceId);
        if (!$inv) return;

        if ($actionType === 'Verified') {
            $type    = 'Invoice Approved';
            $content = "Invoice {$inv['invoice_num']} has been approved and forwarded to Finance Review.";
        } elseif ($actionType === 'UnderReview') {
            $type    = 'Additional Info Requested';
            $content = "Invoice {$inv['invoice_num']} requires additional information. Remarks: {$remarks}. Please resubmit with the requested details.";
        } else {
            $type    = 'Invoice Rejected';
            $content = "Invoice {$inv['invoice_num']} has been rejected. Reason: {$remarks}. Please resubmit with corrections.";
        }

        $this->invoiceModel->insertNotification($inv['customer_ID'], $type, $content);
    }
}
