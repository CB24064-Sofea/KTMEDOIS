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
     * @param string $staffId Unique alphanumeric employee tracking token
     * @return array|null Returns associative data row array if found, null otherwise
     */
    public function getStaffProfile($staffId) {
        // NOTE: `ktmb_staff` (staff_ID, staff_name, email, password, role) is
        // the single canonical staff table used across the whole app —
        // m1/staff_login.php authenticates against it, and every Module 4
        // query (AuditModel, assign_reviewer, review_history, audit_log)
        // joins against it. A previous version of this method attempted a
        // first-pass query against a `user` table using columns
        // (UserID/UserName/role) that don't exist on that table; since
        // mysqli throws on invalid SQL by default in PHP 8.1+, that dead
        // code path crashed every request before ever reaching the
        // (correct) query below. It has been removed.
        $sql = "SELECT staff_ID, staff_name, email, role, password 
                FROM ktmb_staff 
                WHERE TRIM(staff_ID) = TRIM(?) 
                LIMIT 1";

        if ($stmt = $this->db->prepare($sql)) {
            $stmt->bind_param("s", $staffId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result) {
                $row = $result->fetch_assoc();
            } else {
                // Fallback for system configurations lacking the mysqlnd driver
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $row = [];
                    $stmt->bind_result($sId, $sName, $email, $role, $pwd);
                    $stmt->fetch();
                    $row = [
                        'staff_ID'   => $sId,
                        'staff_name' => $sName,
                        'email'      => $email,
                        'role'       => $role,
                        'password'   => $pwd
                    ];
                } else {
                    $row = null;
                }
            }
            
            $stmt->close();
            return $row;
        }
        return null;
    }

    /**
     * Optional utility helper method to quickly check if a staff ID exists in the database
     * @param string $staffId Unique employee tracking token
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
?>
