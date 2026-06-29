<?php
require_once __DIR__ . '/StaffModel.php';

class StaffController {
    private $model;

    public function __construct($dbConnection) {
        $this->model = new StaffModel($dbConnection);
    }

    /**
     * Ensures only active, logged-in staff members can access staff modules.
     * Optional $allowedRoles array restricts access to specific sub-roles.
     */
    public function enforceActiveSessionGuard($sessionData, $allowedRoles = []) {
        // Check if staff session ID exists
        if (!isset($sessionData['staff_id'])) {
            header("Location: login.php");
            exit();
        }

        // Fetch staff profile from database
        $currentRecord = $this->model->getStaffProfile($sessionData['staff_id']);
        
        // Validate if record exists and status is active
        if (!$currentRecord || strtoupper($currentRecord['status']) !== 'ACTIVE') {
            session_unset();
            session_destroy();
            header("Location: login.php?error=" . urlencode("Access Denied: Staff account inactive or session expired."));
            exit();
        }

        // Optional Role Guard: If specific sub-roles are required for a page
        if (!empty($allowedRoles) && !in_array($currentRecord['role'], $allowedRoles)) {
            header("Location: staff_dashboard.php?error=" . urlencode("Unauthorized: You do not have permissions to access that utility."));
            exit();
        }

        return $currentRecord;
    }
}