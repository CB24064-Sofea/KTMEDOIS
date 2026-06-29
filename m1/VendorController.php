<?php
require_once __DIR__ . '/VendorModel.php';

class VendorController {
    private $model;

    public function __construct($dbConnection) {
        $this->model = new VendorModel($dbConnection);
    }

    /**
     * Ensures only active, logged-in vendors can access the dashboard.
     */
    public function enforceActiveSessionGuard($sessionData) {
        // Check if session ID exists
        if (!isset($sessionData['supplier_id'])) {
            header("Location: login.php");
            exit();
        }

        // Fetch user profile from database
        $currentRecord = $this->model->getVendorProfile($sessionData['supplier_id']);
        
        // Validate if record exists and is active
        if (!$currentRecord || strtoupper($currentRecord['status']) !== 'ACTIVE') {
            session_unset();
            session_destroy();
            header("Location: login.php?error=" . urlencode("Access Denied: Account inactive or session expired."));
            exit();
        }

        return $currentRecord;
    }

    /**
     * Handles new vendor registration including password hashing.
     */
    public function handleRegistration($postData) {
        $supplierName = trim($postData['supplier_name'] ?? '');
        $companyName  = trim($postData['company_name'] ?? '');
        $phone        = trim($postData['phone'] ?? '');
        $email        = trim($postData['email'] ?? '');
        $password     = $postData['password'] ?? '';

        if (empty($supplierName) || empty($companyName) || empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => 'Please fill in all required corporate fields.'];
        }

        if ($this->model->isEmailRegistered($email)) {
            return ['status' => 'error', 'message' => 'This email is already registered.'];
        }

        // Security: Secure password hashing
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $supplierId = 'SUP' . rand(10000, 99999);

        $success = $this->model->registerVendor($supplierId, $supplierName, $companyName, $phone, $email, $hashedPassword);

        if ($success) {
            return [
                'status' => 'success', 
                'message' => "Registration successful! Your Supplier ID: <strong>{$supplierId}</strong>."
            ];
        } else {
            return ['status' => 'error', 'message' => 'System failure: Could not save to database.'];
        }
    }
}