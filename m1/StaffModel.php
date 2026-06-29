<?php
/**
 * Class StaffModel
 * Manages database interactions for KTMB Staff profiles and role permissions
 */
class StaffModel {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Retrieve full profile details for a singular staff member
     */
    public function getStaffProfile($staffId) {
        $stmt = $this->db->prepare("SELECT staff_ID, name, email, status, role FROM ktmb_staff WHERE staff_ID = ? LIMIT 1");
        $stmt->bind_param("s", $staffId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Checks if a staff email already exists (Useful for registration/admin tools)
     */
    public function isEmailRegistered($email) {
        $stmt = $this->db->prepare("SELECT staff_ID FROM ktmb_staff WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}