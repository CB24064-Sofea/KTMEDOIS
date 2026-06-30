<?php
// =========================================================================
// FILE        : DOReviewModel.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : (Model Layer) — Delivery Order Inspection Entity
// DESCRIPTION : OOP Model class. Handles raw DB read operations for the
//               Procurement Officer's "DO Inspections List" (do_list.php)
//               and detail drill-down (do_details.php). Read-only —
//               PO_status updates for a DO remain owned by Module 2/4
//               business rules and are written via prepared statements here
//               only when an officer explicitly inspects/flags a DO.
// =========================================================================

class DOReviewModel {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // ── fetchDeliveryOrders() — DO Inspections List ──────────────────────────
    // Returns all delivery orders with supplier info, optional search/filter.
    public function fetchDeliveryOrders($search = '', $statusFilter = 'all') {
        $where = [];
        if (!empty($search)) {
            $safe = $this->db->real_escape_string($search);
            $where[] = "(d.DO_ID LIKE '%$safe%' OR s.supplier_name LIKE '%$safe%' OR d.PO_number LIKE '%$safe%')";
        }
        if ($statusFilter !== 'all') {
            $safe = $this->db->real_escape_string($statusFilter);
            $where[] = "d.PO_status = '$safe'";
        }
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT d.DO_ID, d.supplier_ID, d.PO_ID, d.customer_ID, d.PO_number,
                       d.PO_status, d.created_date, d.project_reference, d.proof_of_delivery,
                       s.supplier_name
                FROM delivery_order d
                INNER JOIN supplier s ON d.supplier_ID = s.supplier_ID
                $whereClause
                ORDER BY d.created_date DESC";
        return $this->db->query($sql);
    }

    // ── getDOStats() — stat cards for do_list.php ────────────────────────────
    public function getDOStats() {
        $q = function($sql) { return $this->db->query($sql)->fetch_assoc()['c']; };
        return [
            'total'    => $q("SELECT COUNT(*) as c FROM delivery_order"),
            'pending'  => $q("SELECT COUNT(*) as c FROM delivery_order WHERE PO_status='Pending'"),
            'approved' => $q("SELECT COUNT(*) as c FROM delivery_order WHERE PO_status='Approved'"),
            'rejected' => $q("SELECT COUNT(*) as c FROM delivery_order WHERE PO_status='Cancelled'"),
        ];
    }

    // ── getDeliveryOrderById() — do_details.php ──────────────────────────────
    public function getDeliveryOrderById($doId) {
        $stmt = $this->db->prepare(
            "SELECT d.*, s.supplier_name, s.email AS supplier_email, s.phone AS supplier_phone,
                    c.customer_name, c.company_name AS customer_company,
                    p.PO_amount, p.PO_status AS po_master_status
             FROM delivery_order d
             INNER JOIN supplier s  ON d.supplier_ID  = s.supplier_ID
             INNER JOIN customer c  ON d.customer_ID  = c.customer_ID
             INNER JOIN purchase_order p ON d.PO_ID    = p.PO_ID
             WHERE d.DO_ID = ? LIMIT 1"
        );
        $stmt->bind_param("s", $doId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    // ── getInvoicesForDO() — show related invoice claims under a DO ──────────
    public function getInvoicesForDO($doId) {
        $stmt = $this->db->prepare(
            "SELECT invoice_ID, invoice_num, total, invoice_status, invoice_date
             FROM invoice WHERE DO_ID = ? ORDER BY invoice_date DESC"
        );
        $stmt->bind_param("s", $doId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $stmt->close();
        return $rows;
    }

    // ── updateDOStatus() — Officer marks a DO inspection result ──────────────
    public function updateDOStatus($doId, $newStatus) {
        $stmt = $this->db->prepare("UPDATE delivery_order SET PO_status = ? WHERE DO_ID = ?");
        $stmt->bind_param("ss", $newStatus, $doId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
