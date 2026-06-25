<?php
require_once __DIR__ . '/DOModel.php';

/**
 * Class DOController
 * Manages form logic, validations, and file uploading actions
 */
class DOController {
    private $model;

    public function __construct($dbConnection) {
        $this->model = new DOModel($dbConnection);
    }

    // Handle form submission request
    public function handleSubmission($postData, $fileData) {
        $doId = trim($postData['do_id'] ?? '');
        $poId = trim($postData['po_id'] ?? '');
        $supplierId = trim($postData['supplier_id'] ?? '');
        $projectRef = trim($postData['project_ref'] ?? '');

        // 1. Basic empty field validation
        if (empty($doId) || empty($poId) || empty($supplierId)) {
            return ['status' => 'error', 'message' => 'Please fill in all required tracking IDs.'];
        }

        // 2. Business Rule validation: Verify if the PO actually exists and is pre-approved
        $poData = $this->model->verifyPurchaseOrder($poId);
        if (!$poData) {
            return ['status' => 'error', 'message' => 'Invalid or Unapproved Purchase Order ID.'];
        }

        // 3. Document upload processing
        $uploadDir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = basename($fileData['proof_file']['name'] ?? '');
        $targetFilePath = $uploadDir . time() . '_' . $fileName;
        $dbFilePath = 'uploads/' . time() . '_' . $fileName;

        if (empty($fileName) || !move_uploaded_file($fileData['proof_file']['tmp_name'], $targetFilePath)) {
            return ['status' => 'error', 'message' => 'Failed to process proof of delivery document upload.'];
        }

        // 4. Save to Database using attributes from your SQL setup
        $customerId = $poData['customer_ID']; 
        $poNumber = 'PO-NUM-' . $poId; // Synthetic matching number
        $poStatus = 'Pending'; // Default status when newly submitted

        $success = $this->model->createDeliveryOrder($doId, $supplierId, $poId, $customerId, $poNumber, $poStatus, $projectRef, $dbFilePath);

        if ($success) {
            return ['status' => 'success', 'message' => 'Delivery Order uploaded successfully!'];
        } else {
            return ['status' => 'error', 'message' => 'Database constraint failure or duplicate DO ID encountered.'];
        }
    }
}