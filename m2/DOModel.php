<?php
/**
 * Class DOModel
 * Handles raw database operations for Module 2 (Manage DO Submission)
 */
class DOModel {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // Check if a Purchase Order exists and is Approved before allowing DO submission
    public function verifyPurchaseOrder($poId) {
        $stmt = $this->db->prepare("SELECT * FROM purchase_order WHERE PO_ID = ? AND PO_status = 'Approved'");
        $stmt->bind_param("s", $poId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Insert a new Delivery Order into the database
    public function createDeliveryOrder($doId, $supplierId, $poId, $customerId, $poNumber, $poStatus, $projectRef, $proofPath) {
        $stmt = $this->db->prepare("INSERT INTO delivery_order (DO_ID, supplier_ID, PO_ID, customer_ID, PO_number, PO_status, project_reference, proof_of_delivery) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $doId, $supplierId, $poId, $customerId, $poNumber, $poStatus, $projectRef, $proofPath);
        return $stmt->execute();
    }

    // Fetch metrics for the dashboard cards
    public function getDashboardMetrics() {
        $metrics = ['total' => 0, 'approved' => 0, 'pending' => 0];
        
        $res = $this->db->query("SELECT COUNT(*) as total FROM delivery_order");
        if ($res) $metrics['total'] = $res->fetch_assoc()['total'];

        $res = $this->db->query("SELECT PO_status, COUNT(*) as count FROM delivery_order GROUP BY PO_status");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                if ($row['PO_status'] === 'Approved') $metrics['approved'] = $row['count'];
                if ($row['PO_status'] === 'Pending') $metrics['pending'] = $row['count'];
            }
        }
        return $metrics;
    }

    // Fetch all records for the dashboard table
    public function getAllDeliveryOrders() {
        return $this->db->query("SELECT * FROM delivery_order ORDER BY created_date DESC");
    }
}