<?php
/**
 * Class VendorModel
 * Manages raw database interactions for Module 1 (Vendor Registry)
 */
class VendorModel {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // Retrieve full profile details for a singular vendor
    public function getVendorProfile($supplierId) {
        $stmt = $this->db->prepare("SELECT * FROM supplier WHERE supplier_ID = ? LIMIT 1");
        $stmt->bind_param("s", $supplierId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Retrieve active counts for administrative overview dashboards
    public function getSystemStatusSummary() {
        $summary = ['Active' => 0, 'Restricted' => 0, 'Inactive' => 0];
        $res = $this->db->query("SELECT status, COUNT(*) as count FROM supplier GROUP BY status");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $summary[$row['status']] = $row['count'];
            }
        }
        return $summary;
    }

/**
     * Checks if the email already exists in the supplier table
     */
    public function isEmailRegistered($email) {
        $stmt = $this->db->prepare("SELECT supplier_ID FROM supplier WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    /**
     * Inserts a new vendor into the supplier table
     */
    public function registerVendor($supplierId, $name, $company, $phone, $email, $password) {
        $sql = "INSERT INTO supplier (supplier_ID, supplier_name, company_name, phone, email, password, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'ACTIVE')";
        $stmt = $this->db->prepare($sql);
        // Assuming your table also has a 'status' column; added 'ACTIVE' by default
        $stmt->bind_param("ssssss", $supplierId, $name, $company, $phone, $email, $password);
        return $stmt->execute();
    }

    // Dynamic search filtering list for admin registry dashboards
    public function filterSuppliers($searchKeyword) {
        $query = "SELECT * FROM supplier WHERE 1=1";
        if (!empty($searchKeyword)) {
            $query .= " AND (supplier_name LIKE ? OR company_name LIKE ? OR supplier_ID LIKE ?)";
            $stmt = $this->db->prepare($query);
            $likeStr = "%" . $searchKeyword . "%";
            $stmt->bind_param("sss", $likeStr, $likeStr, $likeStr);
            $stmt->execute();
            return $stmt->get_result();
        }
        return $this->db->query($query);
    }
}