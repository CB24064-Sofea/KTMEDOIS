<?php
// Initialize system session parameters
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Centralized Database Connection Loader - points one directory up to project root
require_once dirname(__DIR__) . "/db.php";

// Simulate Session-Authenticated Vendor Details (Requirement 1.1 - Read-Only Display)
$_SESSION['vendor_id'] = "SUP001"; // Matched to your SQL dump 'SUP001'
$_SESSION['vendor_name'] = "ABC Supplier Sdn Bhd";
$_SESSION['vendor_ref'] = "REF-2026-KTMB";
$_SESSION['vendor_status'] = "Active"; 

$message = "";
$message_type = ""; 

// Enforce Registry Rule: Restrict deactivated vendors from submitting documents
if ($_SESSION['vendor_status'] !== 'Active') {
    $message = "Access Denied. Your vendor profile is currently deactivated in the master system.";
    $message_type = "error";
}

// --- FETCH AVAILABLE DATA FOR DROPDOWNS ---
$po_list = [];
$customer_list = [];

if ($_SESSION['vendor_status'] === 'Active') {
    // 1. Fetch available Purchase Orders
    $po_query = "SELECT PO_ID, customer_ID, PO_status FROM purchase_order WHERE PO_status = 'Approved'";
    $po_result = $conn->query($po_query);
    if ($po_result) {
        while ($row = $po_result->fetch_assoc()) {
            $po_list[] = $row;
        }
    }

    // 2. Fetch Active Customers
    $cust_query = "SELECT customer_ID, customer_name FROM customer WHERE status = 'Active'";
    $cust_result = $conn->query($cust_query);
    if ($cust_result) {
        while ($row = $cust_result->fetch_assoc()) {
            $customer_list[] = $row;
        }
    }
}
// ------------------------------------------

// Handle Submission Stream
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_do']) && $_SESSION['vendor_status'] === 'Active') {
    
    // 🔒 OPTION A SECURITY GUARD: Block file handling and database persistence for guests
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        $message = "Unauthorized Action. You are currently in Guest Preview Mode. Please log in or register an official account to submit documents.";
        $message_type = "error";
    } else {
        // Only executes if user is authenticated and active
        $do_id = trim($_POST['do_id']);
        $supplier_id = $_SESSION['vendor_id']; 
        $po_id = trim($_POST['po_id']);
        $project_ref = trim($_POST['project_ref']); 
        $customer_id = trim($_POST['customer_id']);
        
        if (empty($do_id) || empty($po_id) || empty($project_ref) || empty($customer_id)) {
            $message = "Submission Failed. All database mandatory input fields must be fulfilled.";
            $message_type = "error";
        } else {
            
            // 🛑 NEW GRACEFUL INTERCEPTION: Prevent Primary Key Duplication Error
            $check_duplicate = $conn->prepare("SELECT DO_ID FROM delivery_order WHERE DO_ID = ?");
            $check_duplicate->bind_param("s", $do_id);
            $check_duplicate->execute();
            $dup_res = $check_duplicate->get_result();
            
            if ($dup_res->num_rows > 0) {
                $message = "Submission Blocked: Delivery Order ID '" . htmlspecialchars($do_id) . "' already exists in the system. Please use a unique DO identifier.";
                $message_type = "error";
                $check_duplicate->close();
            } else {
                $check_duplicate->close();
                
                // Enforce File Attachment Operations
                if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] == 0) {
                    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
                    $file_name = $_FILES['proof_file']['name'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    if (!in_array($file_ext, $allowed_extensions)) {
                        $message = "File Type Violation. Only PDF, JPG, and PNG documents are allowed.";
                        $message_type = "error";
                    } else {
                        // Create upload path relative to project root folder
                        $upload_dir = "../uploads/";
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $new_file_name = "do_" . strtolower($do_id) . "." . $file_ext;
                        $target_file_path = $upload_dir . $new_file_name;
                        
                        if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $target_file_path)) {
                            
                            // Cross-check Purchase Order Records & Fetch Status
                            $check_po = $conn->prepare("SELECT PO_status FROM purchase_order WHERE PO_ID = ?");
                            $check_po->bind_param("s", $po_id);
                            $check_po->execute();
                            $po_res = $check_po->get_result();
                            
                            if ($po_res->num_rows === 0) {
                                $message = "Error: The provided Purchase Order ID (PO) does not exist in the master database.";
                                $message_type = "error";
                                unlink($target_file_path);
                            } else {
                                $po_data = $po_res->fetch_assoc();
                                $po_status = $po_data['PO_status'];
                                $po_number = "NUM-" . $po_id; // Match string constraint format
                                
                                // Save database paths inside uploads folder.
                                $db_save_path = "uploads/" . $new_file_name;
                                
                                // FIXED QUERY: Aligned perfectly with your database schema
                                $insert_stmt = $conn->prepare("INSERT INTO delivery_order (DO_ID, supplier_ID, PO_ID, customer_ID, PO_number, PO_status, project_reference, proof_of_delivery) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                $insert_stmt->bind_param("ssssssss", $do_id, $supplier_id, $po_id, $customer_id, $po_number, $po_status, $project_ref, $db_save_path);
                                
                                if ($insert_stmt->execute()) {
                                    $message = "Success! Delivery Order " . htmlspecialchars($do_id) . " has been uploaded successfully.";
                                    $message_type = "success";
                                } else {
                                    $message = "Database Error: " . htmlspecialchars($insert_stmt->error);
                                    $message_type = "error";
                                    unlink($target_file_path);
                                }
                                $insert_stmt->close();
                            }
                            $check_po->close();
                        } else {
                            $message = "System I/O Failure: Could not save the attachment payload.";
                            $message_type = "error";
                        }
                    }
                } else {
                    $message = "Proof of Delivery Document Upload is mandatory.";
                    $message_type = "error";
                }
            }
        }
    } 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS - Submit Delivery Order</title>
    <style>
        * { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        .app-layout-wrapper { display: flex; flex-direction: column; width: 100%; height: 100vh; overflow: hidden; background-color: #f8fafc; }
        .lower-split-container { display: flex; flex-grow: 1; width: 100%; overflow: hidden; }
        .content-body { padding: 32px; overflow-y: auto; flex-grow: 1; display: flex; justify-content: center; align-items: flex-start; }
        .form-card { background: #ffffff; padding: 28px; border-radius: 12px; border: 1px solid #e2e8f0; width: 100%; max-width: 850px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full-width { grid-column: span 2; }
        .readonly-box { background-color: #e2e8f0; color: #4a5568; font-weight: 500; cursor: not-allowed; }
        label { font-size: 13px; font-weight: 600; color: #4a5568; text-transform: uppercase; letter-spacing: 0.5px; }
        input[type="text"], input[type="file"], select { padding: 11px 14px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; color: #1a202c; width: 100%; background-color: #fff; }
        input:focus, select:focus { outline: none; border-color: #002D62; }
        .alert { padding: 14px 18px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; font-weight: 500; }
        .alert.success { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert.error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
        .submit-btn { background-color: #002D62; color: #ffffff; padding: 12px 24px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%; }
        .submit-btn:hover { background-color: #001937; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; gap: 16px; } .form-group.full-width { grid-column: span 1; } }
    </style>
</head>
<body>

<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>
    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>
        <div class="content-body">
            <div class="form-card">
                <h2 style="color: #002D62; font-size: 20px; font-weight: 700; margin-bottom: 6px;">New Delivery Order Submission</h2>
                <p style="color: #718096; font-size: 14px; margin-bottom: 24px;">Digitize operations, attach proofs, and map verified supplier transactions securely.</p>

                <?php if (!empty($message)): ?>
                    <div class="alert <?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form action="create_do.php" method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Supplier ID (Read-Only)</label>
                            <input type="text" class="readonly-box" value="<?php echo htmlspecialchars($_SESSION['vendor_id']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Company Name (Read-Only)</label>
                            <input type="text" class="readonly-box" value="<?php echo htmlspecialchars($_SESSION['vendor_name']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="do_id">Delivery Order ID (DO Number)</label>
                            <input type="text" id="do_id" name="do_id" placeholder="e.g., DO002" required>
                        </div>

                        <div class="form-group">
                            <label for="po_id">Linked Purchase Order (PO ID)</label>
                            <select id="po_id" name="po_id" required>
                                <option value="">-- Select Available PO ID --</option>
                                <?php foreach ($po_list as $po): ?>
                                    <option value="<?php echo htmlspecialchars($po['PO_ID']); ?>">
                                        <?php echo htmlspecialchars($po['PO_ID']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="project_ref">KTM Project Reference Code</label>
                            <input type="text" id="project_ref" name="project_ref" placeholder="e.g., PRJ-KTM-001" required>
                        </div>

                        <div class="form-group">
                            <label for="customer_id">Destination Unit (Customer ID)</label>
                            <select id="customer_id" name="customer_id" required>
                                <option value="">-- Select Available Customer --</option>
                                <?php foreach ($customer_list as $cust): ?>
                                    <option value="<?php echo htmlspecialchars($cust['customer_ID']); ?>">
                                        <?php echo htmlspecialchars($cust['customer_ID'] . " - " . $cust['customer_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="proof_file">Upload Digital Proof (PDF/Images)</label>
                            <input type="file" id="proof_file" name="proof_file" accept=".pdf, .jpg, .jpeg, .png" required>
                        </div>

                        <div class="form-group full-width">
                            <button type="submit" name="submit_do" class="submit-btn" <?php echo ($_SESSION['vendor_status'] !== 'Active') ? 'disabled' : ''; ?>>
                                Validate & Submit Delivery Order
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>