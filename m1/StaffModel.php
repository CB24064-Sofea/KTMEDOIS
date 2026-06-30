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
        $sql = "SELECT UserID AS staff_ID, UserName AS staff_name, email, role, password
                FROM user
                WHERE CAST(UserID AS CHAR) = TRIM(?)
                LIMIT 1";

        if ($stmt = $this->db->prepare($sql)) {
            $stmt->bind_param("s", $staffId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && ($row = $result->fetch_assoc())) {
                $stmt->close();
                return $row;
            }
            $stmt->close();
        }

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