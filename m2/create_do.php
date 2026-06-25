<?php
// Initialize system session parameters
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Centralized Database Connection Loader - points one directory up to project root
require_once dirname(__DIR__) . "/db.php";

// Simulate Session-Authenticated Vendor Details (Requirement 1.1 - Read-Only Display)
$_SESSION['vendor_id'] = "SPL001";
$_SESSION['vendor_name'] = "LNSTech Solutions";
$_SESSION['vendor_ref'] = "REF-2026-KTMB";
$_SESSION['vendor_status'] = "Active"; // If 'Deactivated', they are restricted

// TO TEST GUEST PREVIEW MODE: Uncomment the line below to clear login status
// unset($_SESSION['user_logged_in']); 

$message = "";
$message_type = ""; 

// Enforce Registry Rule: Restrict deactivated vendors from submitting documents
if ($_SESSION['vendor_status'] !== 'Active') {
    $message = "Access Denied. Your vendor profile is currently deactivated in the master system.";
    $message_type = "error";
}

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
        $project_ref = trim($_POST['project_ref']); // Added KTM Project Reference
        $customer_id = trim($_POST['customer_id']);
        $delivery_date = $_POST['delivery_date'];
        
        if (empty($do_id) || empty($po_id) || empty($project_ref) || empty($customer_id) || empty($delivery_date)) {
            $message = "Submission Failed. All database mandatory input fields must be fulfilled.";
            $message_type = "error";
        } else {
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
                    $upload_dir = "../uploads/proof_docs/";
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $new_file_name = "DO_" . $do_id . "_" . time() . "." . $file_ext;
                    $target_file_path = $upload_dir . $new_file_name;
                    
                    if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $target_file_path)) {
                        
                        // Cross-check Purchase Order Records
                        $check_po = $conn->prepare("SELECT 1 FROM purchase_order WHERE PO_ID = ?");
                        $check_po->bind_param("s", $po_id);
                        $check_po->execute();
                        $po_exists = $check_po->get_result()->num_rows > 0;
                        
                        if (!$po_exists) {
                            $message = "Error: The provided Purchase Order ID (PO) does not exist in the master database.";
                            $message_type = "error";
                            unlink($target_file_path);
                        } else {
                            // Save database paths inside uploads folder. Status set explicitly to 'Submitted'
                            $db_save_path = "uploads/proof_docs/" . $new_file_name;
                            $insert_stmt = $conn->prepare("INSERT INTO delivery_order (DO_ID, supplier_ID, PO_ID, customer_ID, delivery_date, proof_of_delivery, delivery_status) VALUES (?, ?, ?, ?, ?, ?, 'Submitted')");
                            $insert_stmt->bind_param("ssssss", $do_id, $supplier_id, $po_id, $customer_id, $delivery_date, $db_save_path);
                            
                            if ($insert_stmt->execute()) {
                                $message = "Success! Delivery Order " . htmlspecialchars($do_id) . " has been uploaded and set to 'Submitted' status.";
                                $message_type = "success";
                            } else {
                                $message = "Database Exception Error: Duplicate Key entries or schema collision.";
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
    } // End of security guard check
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS Portal</title>
    
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <title>KTM eDOIS - Submit Delivery Order</title>
    <style>
        * { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        
        .app-layout-wrapper { display: flex; flex-direction: column; width: 100%; height: 100vh; overflow: hidden; background-color: #f8fafc; }
        .lower-split-container { display: flex; flex-grow: 1; width: 100%; overflow: hidden; }
        
        /* CENTERING ENGINE: Horizontally centers the card content layout area */
        .content-body { 
            padding: 32px; 
            overflow-y: auto; 
            flex-grow: 1; 
            display: flex;
            justify-content: center; 
            align-items: flex-start; 
        }
        
        /* RESPONSIVE STRUCTURED CARD PROFILE */
        .form-card { 
            background: #ffffff; 
            padding: 28px; 
            border-radius: 12px; 
            border: 1px solid #e2e8f0; 
            width: 100%;
            max-width: 850px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); 
        }
        
        /* GRID MANAGEMENT THAT STACKS ON MOBILE VIEWPORTS natively */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full-width { grid-column: span 2; }
        .readonly-box { background-color: #e2e8f0; color: #4a5568; font-weight: 500; cursor: not-allowed; }
        
        label { font-size: 13px; font-weight: 600; color: #4a5568; text-transform: uppercase; letter-spacing: 0.5px; }
        input[type="text"], input[type="date"], input[type="file"] { padding: 11px 14px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; color: #1a202c; width: 100%; }
        input:focus { outline: none; border-color: #002D62; }
        
        .alert { padding: 14px 18px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; font-weight: 500; }
        .alert.success { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert.error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
        
        .guest-notice-banner { background-color: #fffaf0; border: 1px solid #feebc8; color: #dd6b20; padding: 14px 18px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; font-weight: 500; line-height: 1.5; }
        
        .submit-btn { background-color: #002D62; color: #ffffff; padding: 12px 24px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color 0.2s; width: 100%; }
        .submit-btn:hover { background-color: #001937; }
        .submit-btn:disabled { background-color: #a0aec0; cursor: not-allowed; }

        /* 📱 RESPONSIVE MEDIA BREAKPOINTS FOR PHONE SCREENS */
        @media (max-width: 768px) {
            .content-body { padding: 16px; }
            .form-card { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; gap: 16px; }
            .form-group.full-width { grid-column: span 1; }
        }
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

                <?php if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true): ?>
                    <div class="guest-notice-banner">
                        🔒 <strong>Guest Preview Mode:</strong> Form submission is restricted. Please log in or register to complete transactions.
                    </div>
                <?php endif; ?>

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
                            <input type="text" id="do_id" name="do_id" placeholder="e.g., DO001" required>
                        </div>

                        <div class="form-group">
                            <label for="po_id">Linked Purchase Order (PO ID)</label>
                            <input type="text" id="po_id" name="po_id" placeholder="e.g., PO001" required>
                        </div>

                        <div class="form-group">
                            <label for="project_ref">KTM Project Reference Code</label>
                            <input type="text" id="project_ref" name="project_ref" placeholder="e.g., PRJ-KTM-2026" required>
                        </div>

                        <div class="form-group">
                            <label for="customer_id">Destination Unit (Customer ID)</label>
                            <input type="text" id="customer_id" name="customer_id" placeholder="e.g., CUST001" required>
                        </div>

                        <div class="form-group">
                            <label for="delivery_date">Fulfillment Delivery Date</label>
                            <input type="date" id="delivery_date" name="delivery_date" required>
                        </div>

                        <div class="form-group">
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