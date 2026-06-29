<?php
require_once __DIR__ . '/StaffModel.php';

/**
 * Class StaffController
 * Controls behavioral flows, security routing, and role authentication for Module 1 (Staff Portal)
 */
class StaffController {
    private $model;

    public function __construct($dbConnection) {
        $this->model = new StaffModel($dbConnection);
    }

    /**
     * Guards administrative and internal staff areas from unauthenticated sessions
     * Automatically cross-checks database record integrity.
     * * @param array $sessionData Passes $_SESSION['staff_auth'] reference map
     * @return array Verified active row staff details
     */
    public function enforceActiveSessionGuard($sessionData) {
        // Check if the fundamental session authentication key is missing
        if (!isset($sessionData['staff_id']) || empty($sessionData['staff_id'])) {
            header("Location: login.php");
            exit();
        }

        // Fetch fresh database record matching the tracking ID to prevent session spoofing
        $currentStaff = $this->model->getStaffProfile($sessionData['staff_id']);

        // Block access if staff member does not exist or has been removed from the registry
        if (!$currentStaff) {
            unset($_SESSION['staff_auth']);
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            header("Location: login.php?error=" . urlencode("Access Revoked: Staff account credentials invalid or deactivated."));
            exit();
        }

        // Return standardized, sanitized metadata array
        return [
            'staff_ID' => trim($currentStaff['staff_ID']),
            'name'     => htmlspecialchars(trim($currentStaff['staff_name'])),
            'email'    => htmlspecialchars(trim($currentStaff['email'])),
            'role'     => trim($currentStaff['role']) // Expected values: 'Administrator', 'Finance Reviewer', 'KTM Staff'
        ];
    }

    /**
     * Processes login form submission for staff members using secure password verification
     * * @param array $postData Passes $_POST parameters containing login credentials
     * @return array Status feedback context strings
     */
    public function handleStaffLogin($postData) {
        $staffId  = trim($postData['staff_id'] ?? '');
        $password = trim($postData['password'] ?? '');

        if (empty($staffId) || empty($password)) {
            return [
                'status'  => 'error',
                'message' => 'Please provide both your Staff ID and account password.'
            ];
        }

        // Fetch target record via the model
        $staff = $this->model->getStaffProfile($staffId);

        // Standard BCRYPT password evaluation matching our user ecosystem
        if ($staff && password_verify($password, $staff['password'])) {
            
            // Establish global authenticated tracking sessions
            $_SESSION['staff_auth'] = [
                "staff_id" => $staff['staff_ID'],
                "name"     => $staff['staff_name'],
                "email"    => $staff['email'],
                "sub_role" => $staff['role']
            ];
            $_SESSION['current_module'] = 'staff';

            header("Location: ktm_dashboard.php");
            exit();
        }

        return [
            'status'  => 'error',
            'message' => 'Invalid Staff Identification Token or password mismatch.'
        ];
    }
}