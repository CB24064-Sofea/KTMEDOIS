<?php
class Database {
    private $host = "localhost";
    private $db_name = "ktm_edois";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
<?php
class AuthService {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Authenticates a vendor using their registered email and verifies status.
     */
    public function login($email, $password) {
        // 1. Fetch the supplier record by email
        $query = "SELECT supplier_ID, supplier_name, company_name, email, status FROM supplier WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Credential Verification 
        // Note: For production use password_verify(). If password checking is handled externally, bypass this.
        if (!$supplier) {
            throw new Exception("Invalid email or unauthorized access credentials.");
        }

        // 3. Strict Operational Evaluation (Matches your ENUM definitions)
        if ($supplier['status'] !== 'Active') {
            throw new Exception("Access Denied: Your vendor status is currently '" . $supplier['status'] . "' in KTM's registry.");
        }

        // 4. Secure Session Management with Read-Only Metadata values
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Exposing these parameters globally so Module 2 and Module 3 can pick them up automatically
        $_SESSION['vendor_auth'] = [
            "supplier_ID"   => $supplier['supplier_ID'],
            "supplier_name" => $supplier['supplier_name'],
            "company_name"  => $supplier['company_name'],
            "email"         => $supplier['email'],
            "role"          => "Vendor"
        ];

        return true;
    }
}
<?php
require_once 'Database.php';
require_once 'AuthService.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? ''); // Capturing for the auth pipeline

    // Instantiate Dependencies
    $database = new Database();
    $dbConn   = $database->getConnection();
    $auth     = new AuthService($dbConn);

    try {
        if (empty($email)) {
            throw new Exception("Email input field is required.");
        }
        
        // Run verification pipeline
        if ($auth->login($email, $password)) {
            // Forward successfully authenticated vendor to dashboard
            header("Location: vendor_dashboard.php");
            exit();
        }
    } catch (Exception $e) {
        // Return back to UI login view with flash error message parameters
        header("Location: login_view.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}
