<?php
/**
 * Class StaffModel
 * Manages atomic data selections and database interactions for internal KTMB infrastructure employees
 */
class StaffModel {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Pulls unique internal registry parameters matching an identified employee ID key
     * Enforced secure column selections including the hashed password string for authentication.
     * * @param string $staffId Unique alphanumeric employee tracking token
     * @return array|null Returns associative data row array if found, null otherwise
     */
    public function getStaffProfile($staffId) {
        // Enforce explicit column targeting, pulling the password token for Controller validation checks
        $sql = "SELECT staff_ID, staff_name, email, role, password 
                FROM ktmb_staff 
                WHERE TRIM(staff_ID) = TRIM(?) 
                LIMIT 1";

        if ($stmt = $this->db->prepare($sql)) {
            $stmt->bind_param("s", $staffId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $result;
        }
        return null;
    }

    /**
     * Optional utility helper method to quickly check if a staff ID exists in the database
     * * @param string $staffId Unique employee tracking token
     * @return bool True if record is registered, false otherwise
     */
    public function staffIdExists($staffId) {
        $sql = "SELECT staff_ID FROM ktmb_staff WHERE TRIM(staff_ID) = TRIM(?) LIMIT 1";
        
        if ($stmt = $this->db->prepare($sql)) {
            $stmt->bind_param("s", $staffId);
            $stmt->execute();
            $stmt->store_result();
            $exists = $stmt->num_rows > 0;
            $stmt->close();
            return $exists;
        }
        return false;
    }
}