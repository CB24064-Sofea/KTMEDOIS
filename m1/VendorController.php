<?php
require_once __DIR__ . '/VendorModel.php';

class VendorController {
    private $model;

    public function __construct($dbConnection) {
        $this->model = new VendorModel($dbConnection);
    }

    public function enforceActiveSessionGuard($sessionData) {
        if (!isset($sessionData['supplier_id'])) {
            header("Location: login.php");
            exit();
        }
        $currentRecord = $this->model->getVendorProfile($sessionData['supplier_id']);
        if (!$currentRecord || strtoupper($currentRecord['status']) !== 'ACTIVE') {
            unset($_SESSION['vendor_auth']);
            session_destroy();
            header("Location: login.php?error=" . urlencode("Session revoked."));
            exit();
        }
        return $currentRecord;
    }

    // Ensure this method is right here next to enforcement method inside your Controller file!
    public function handleRegistration($postData) {
        $supplierName = trim($postData['supplier_name'] ?? '');
        $companyName  = trim($postData['company_name'] ?? '');
        $phone        = trim($postData['phone'] ?? '');
        $email        = trim($postData['email'] ?? '');

        if (empty($supplierName) || empty($companyName) || empty($email)) {
            return ['status' => 'error', 'message' => 'Please fill in all required fields.'];
        }

        if ($this->model->isEmailRegistered($email)) {
            return ['status' => 'error', 'message' => 'This email address is already registered.'];
        }

        $supplierId = 'SUP' . rand(10000, 99999);
        $success = $this->model->registerVendor($supplierId, $supplierName, $companyName, $phone, $email);

        if ($success) {
            return [
                'status' => 'success', 
                'message' => "Registration successful! Your generated Supplier ID is: <strong>{$supplierId}</strong>. You can now log in."
            ];
        } else {
            return ['status' => 'error', 'message' => 'Database failure occurred.'];
        }
    }
}