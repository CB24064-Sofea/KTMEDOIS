<?php
// =========================================================================
// FILE        : DOReviewController.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// DESCRIPTION : Controller layer between do_list.php / do_details.php views
//               and DOReviewModel. Also writes an audit_log style trail
//               for DO inspection actions, mirroring how
//               ReviewAndApprovalController logs invoice decisions.
// =========================================================================

require_once __DIR__ . '/DOReviewModel.php';

class DOReviewController {

    private $doModel;
    private $db;
    private $activeStaffID;

    public function __construct($dbConnection, $staffId = 'STF001') {
        $this->doModel       = new DOReviewModel($dbConnection);
        $this->db             = $dbConnection;
        $this->activeStaffID  = $staffId;
    }

    public function fetchDeliveryOrders($search = '', $statusFilter = 'all') {
        return $this->doModel->fetchDeliveryOrders($search, $statusFilter);
    }

    public function getDOStats() {
        return $this->doModel->getDOStats();
    }

    public function getDeliveryOrderById($doId) {
        return $this->doModel->getDeliveryOrderById($doId);
    }

    public function getInvoicesForDO($doId) {
        return $this->doModel->getInvoicesForDO($doId);
    }

    // ── inspectDO() — officer marks DO as Approved / Cancelled after review ──
    public function inspectDO($doId, $decision) {
        if (!in_array($decision, ['Approved', 'Cancelled', 'Pending'], true)) {
            return ['success' => false, 'message' => 'Invalid inspection decision.'];
        }

        $updated = $this->doModel->updateDOStatus($doId, $decision);
        if (!$updated) {
            return ['success' => false, 'message' => 'Database update failed.'];
        }

        // Write a lightweight system note into audit_log if there is a linked invoice
        $stmt = $this->db->prepare("SELECT invoice_ID FROM invoice WHERE DO_ID = ? LIMIT 1");
        $stmt->bind_param("s", $doId);
        $stmt->execute();
        $linked = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($linked) {
            $recordId = 'TRX-' . date('YmdHis');
            $action   = 'DOInspected:' . $decision;
            $log = $this->db->prepare(
                "INSERT INTO audit_log (user_ID, invoice_ID, action, record_ID) VALUES (?, ?, ?, ?)"
            );
            $log->bind_param("siss", $this->activeStaffID, $linked['invoice_ID'], $action, $recordId);
            $log->execute();
            $log->close();
        }

        return ['success' => true, 'message' => 'Delivery Order inspection recorded successfully.'];
    }
}
