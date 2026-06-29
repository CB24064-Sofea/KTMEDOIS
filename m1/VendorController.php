<?php
require_once __DIR__ . '/VendorModel.php';

/**
 * Class VendorController
 * Controls behavioral flows, security routing, and data presentation for Module 1
 */
class VendorController {
    private $model;

    public function __construct($dbConnection) {
        $this->model = new VendorModel($dbConnection);
    }

    /**
     * Ensure logged-in vendors are protected, active, and not restricted/inactive
     */
    public function enforceActiveSessionGuard($sessionData) {
        if (!isset($sessionData['supplier_id'])) {
            header("Location: login.php");
            exit();
        }

        $currentRecord = $this->model->getVendorProfile($sessionData['supplier_id']);
        
        if (!$currentRecord || strtoupper($currentRecord['status']) !== 'ACTIVE') {
            unset($_SESSION['vendor_auth']);
            session_destroy();
            header("Location: login.php?error=" . urlencode("Session revoked: Your company profile status is no longer active."));
            exit();
        }

        return $currentRecord;
    }

    /**
     * ✅ FIXED: Added the missing registration handler method
     * Processes form inputs and manages vendor account creation workflow
     */
    public function handleRegistration($postData) {
        $supplierName = trim($postData['supplier_name'] ?? '');
        $companyName  = trim($postData['company_name'] ?? '');
        $phone        = trim($postData['phone'] ?? '');
        $email        = trim($postData['email'] ?? '');

        // 1. Inputs validation
        if (empty($supplierName) || empty($companyName) || empty($email)) {
            return ['status' => 'error', 'message' => 'Please fill in all required corporate fields.'];
        }

        // 2. Duplicate Check
        if ($this->model->isEmailRegistered($email)) {
            return ['status' => 'error', 'message' => 'This email address is already registered in our directory.'];
        }

        // 3. Automated unique ID Generator (e.g., SUP12345)
        $supplierId = 'SUP' . rand(10000, 99999);

        // 4. Save to Database via the Model
        $success = $this->model->registerVendor($supplierId, $supplierName, $companyName, $phone, $email);

        if ($success) {
            return [
                'status' => 'success', 
                'message' => "Registration successful! Your generated Supplier ID is: <strong>{$supplierId}</strong>."
            ];
        } else {
            return ['status' => 'error', 'message' => 'Database constraint error or system failure occurred.'];
        }
    }
}