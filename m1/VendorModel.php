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