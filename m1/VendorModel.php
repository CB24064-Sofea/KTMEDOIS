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
        $stmt = $this->db->prepare(
            "SELECT UserID AS supplier_ID, UserName AS supplier_name,
                    company_name, phone, email, password, status, inactive_date
             FROM user
             WHERE CAST(UserID AS CHAR) = ?
             LIMIT 1"
        );
        $stmt->bind_param("s", $supplierId);
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
        if ($profile) {
            return $profile;
        }

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
                $summary[$row['status']] = ($summary[$row['status']] ?? 0) + (int) $row['count'];
            }
        }

        $staffRoles = "'Administrator','Finance Officer','Procurement Officer'";
        $userRes = $this->db->query(
            "SELECT status, COUNT(*) as count
             FROM user
             WHERE role IS NULL OR role = '' OR role NOT IN ($staffRoles)
             GROUP BY status"
        );
        if ($userRes) {
            while ($row = $userRes->fetch_assoc()) {
                $normalized = ucfirst(strtolower(trim((string) ($row['status'] ?? 'active'))));
                if ($normalized === '') {
                    $normalized = 'Active';
                }
                if (!isset($summary[$normalized])) {
                    $summary[$normalized] = 0;
                }
                $summary[$normalized] += (int) $row['count'];
            }
        }

        return $summary;
    }

    /**
     * Checks if the email already exists in vendor records
     */
    public function isEmailRegistered($email) {
        $stmt = $this->db->prepare(
            "SELECT email FROM user WHERE email = ?
             UNION
             SELECT email FROM supplier WHERE email = ?
             LIMIT 1"
        );
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result && $result->num_rows > 0;
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
        $staffRoles = "'Administrator','Finance Officer','Procurement Officer'";
        $query = "
            SELECT supplier_ID, supplier_name, company_name, email, status
            FROM supplier
            WHERE 1=1
            UNION ALL
            SELECT CAST(UserID AS CHAR) AS supplier_ID,
                   UserName AS supplier_name,
                   company_name,
                   email,
                   CASE
                       WHEN status IS NULL OR TRIM(status) = '' THEN 'Active'
                       ELSE CONCAT(UPPER(SUBSTRING(status, 1, 1)), LOWER(SUBSTRING(status, 2)))
                   END AS status
            FROM user
            WHERE role IS NULL OR role = '' OR role NOT IN ($staffRoles)
        ";

        if (!empty($searchKeyword)) {
            $wrapped = "SELECT * FROM ($query) AS vendors
                        WHERE supplier_name LIKE ?
                           OR company_name LIKE ?
                           OR supplier_ID LIKE ?
                           OR email LIKE ?";
            $stmt = $this->db->prepare($wrapped);
            $likeStr = "%" . $searchKeyword . "%";
            $stmt->bind_param("ssss", $likeStr, $likeStr, $likeStr, $likeStr);
            $stmt->execute();
            return $stmt->get_result();
        }

        return $this->db->query("SELECT * FROM ($query) AS vendors ORDER BY company_name ASC");
    }
}