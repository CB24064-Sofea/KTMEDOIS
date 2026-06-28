<?php
// =========================================================================
// 1. SESSION & SECURITY VALIDATION
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$staff_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'STF9999';

// =========================================================================
// 2. DATABASE CONNECTION SETUP
// =========================================================================
$servername = "127.0.0.1:3307";
$username = "root";
$password = "";
$dbname = "ktm_edois";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$message = "";
$message_type = "";

// =========================================================================
// 3. POST ACTION ENGINE (PROCESS APPROVAL / REJECTION FROM THE LIST)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $invoice_id = intval($_POST['invoice_id']);
    $action_type = $_POST['action_type'];
    $rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';

    if ($action_type === 'Approve') {
        $new_status = 'Approved';
        $stmt = $conn->prepare("UPDATE invoice SET invoice_status = ?, reason = NULL WHERE invoice_ID = ?");
        $stmt->bind_param("si", $new_status, $invoice_id);
    } elseif ($action_type === 'Reject') {
        $new_status = 'Rejected';
        $stmt = $conn->prepare("UPDATE invoice SET invoice_status = ?, reason = ? WHERE invoice_ID = ?");
        $stmt->bind_param("ssi", $new_status, $rejection_reason, $invoice_id);
    }

    if (isset($stmt) && $stmt->execute()) {
        // Audit log telemetry insertion
        $record_id = "INV-" . $invoice_id;
        $log_action = ($action_type === 'Approve') ? 'APPROVE_INVOICE' : 'REJECT_INVOICE';
        $log_stmt = $conn->prepare("INSERT INTO audit_log (staff_ID, invoice_ID, action, record_ID) VALUES (?, ?, ?, ?)");
        $log_stmt->bind_param("siss", $staff_id, $invoice_id, $log_action, $record_id);
        $log_stmt->execute();
        
        $message = "Invoice state updated successfully! The item has been cleared from this view.";
        $message_type = "success";
    } else {
        $message = "Processing failure. Please try again.";
        $message_type = "error";
    }
}

// =========================================================================
// 4. AUTOMATIC FETCH: ONLY EXTRACT 'UNDER REVIEW' INVOICES
// =========================================================================
$query_str = "SELECT i.*, do.proof_of_delivery, do.PO_number 
              FROM invoice i 
              LEFT JOIN delivery_order do ON i.DO_ID = do.DO_ID 
              WHERE i.invoice_status = 'Under Review'
              ORDER BY i.invoice_date DESC";

$result = $conn->query($query_str);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Portal - Finance Review Desk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f3f5f9;
            --card-bg: #ffffff;
            --primary-navy: #002D62;
            --dark-gray: #1a1a1a;
            --border-color: #e2e8f0;
            --text-muted: #718096;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        
        .app-container { display: flex; flex-direction: row; flex-grow: 1; width: 100%; overflow: hidden; height: calc(100vh - 70px); }
        .workspace { flex-grow: 1; padding: 40px; overflow-y: auto; max-width: 1200px; margin: 0 auto; width: 100%; }
        
        .header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-title { font-size: 26px; font-weight: 700; color: var(--primary-navy); }
        
        .counter-badge { background: #fef3c7; color: #d97706; padding: 6px 14px; border-radius: 50px; font-size: 14px; font-weight: 700; border: 1px solid #fde68a; }
        
        .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; font-weight: 500; }
        .alert-success { background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }

        /* Review List Specific Styling */
        .invoice-card { background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border-color); padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.01); display: grid; grid-template-columns: 1fr 340px; gap: 30px; align-items: center; }
        
        .meta-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 15px; padding-bottom: 15px; }
        .data-group label { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px; }
        .data-group p { font-size: 14px; font-weight: 600; color: var(--dark-gray); }
        .data-group code { font-family: monospace; font-size: 13px; color: #0f172a; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; }
        
        .invoice-title-block { border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .inv-number-tag { font-size: 18px; font-weight: 700; color: var(--primary-navy); display: flex; align-items: center; gap: 10px; }
        .badge-review { background-color: #fef3c7; color: #d97706; font-size: 12px; padding: 3px 10px; border-radius: 50px; font-weight: 600; }

        .action-container-box { background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #edf2f7; align-self: start; }
        .btn-flex-row { display: flex; gap: 10px; margin-top: 10px; }
        
        .btn-action { flex: 1; border: none; padding: 12px; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.2s; text-align: center; }
        .btn-approve { background-color: #059669; color: #ffffff; }
        .btn-approve:hover { background-color: #047857; }
        .btn-reject { background-color: #dc2626; color: #ffffff; }
        .btn-reject:hover { background-color: #b91c1c; }
        
        .inner-reason-box { display: none; margin-top: 12px; }
        .inner-reason-box textarea { width: 100%; height: 75px; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 12px; resize: none; margin-bottom: 8px; outline: none; }
        .inner-reason-box textarea:focus { border-color: #dc2626; }
        
        .btn-confirm-reject { background-color: #1e293b; color: white; width: 100%; display: block; }

        .empty-state { text-align: center; padding: 60px; background: white; border-radius: 12px; border: 1px dashed var(--border-color); }
        .empty-state p { color: var(--text-muted); font-size: 15px; margin-top: 8px; }
        .btn-doc { color: var(--primary-navy); text-decoration: none; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; margin-top: 5px; }
        .btn-doc:hover { text-decoration: underline; }
        
        a[onclick*="printPreviewImage"] {
        color: var(--primary-navy);
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        margin-top: 12px;
        cursor: pointer;
        }

        a[onclick*="printPreviewImage"]:hover {
            text-decoration: underline;
            color: #001f42; 
        }
    </style>
</head>
<body>

    <?php include 'topbar.php'; ?>

    <div class="app-container">
        
        <?php include '../sidebar.php'; ?>

        <main class="workspace">
            <div class="header-container">
                <h1 class="header-title">Finance Verification Queue</h1>
                <div class="counter-badge">
                    <?php echo $result->num_rows; ?> Invoices Awaiting Review
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($invoice = $result->fetch_assoc()): ?>
                    <div class="invoice-card" id="invoice_row_<?php echo $invoice['invoice_ID']; ?>">
                        
                        <div class="info-side">
                            <div class="invoice-title-block">
                                <div class="inv-number-tag">
                                    Invoice: <?php echo htmlspecialchars($invoice['invoice_num']); ?>
                                    <span class="badge-review">Under Review</span>
                                </div>
                            </div>
                            
                            <div class="meta-grid">
                                <div class="data-group">
                                    <label>Delivery Order ID</label>
                                    <p><code><?php echo htmlspecialchars($invoice['DO_ID']); ?></code></p>
                                </div>
                                <div class="data-group">
                                    <label>PO Reference</label>
                                    <p><code><?php echo htmlspecialchars($invoice['PO_number'] ?? 'N/A'); ?></code></p>
                                </div>
                                <div class="data-group">
                                    <label>Submission Date</label>
                                    <p><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></p>
                                </div>
                                <div class="data-group" style="grid-column: span 2;">
                                    <label>Billing Location Target</label>
                                    <p style="font-weight: 400; font-size: 13px; line-height: 1.4;">
                                        <?php echo htmlspecialchars($invoice['billing_address']); ?>
                                    </p>
                                </div>
                                <div class="data-group">
                                    <label>Total Claims Valuation</label>
                                    <p style="color: #059669; font-weight: 700; font-size: 16px;">
                                        MYR <?php echo number_format($invoice['total'], 2); ?>
                                    </p>
                                </div>
                            </div>
                                
                                <a href="Delivery Order .png" onclick="printPreviewImage(this.href); return false;">
                               View Attached Proof of Delivery (POD)
                            </a>
                        </div>

                        <div class="action-container-box">
                            <label style="display:block; font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:5px; letter-spacing:0.5px;">
                                Status Engine Action
                            </label>
                            <p style="font-size:13px; font-weight:500; color:var(--dark-gray); margin-bottom:15px;">
                                Update document claim submission status:
                            </p>
                            
                            <form method="POST" id="form_<?php echo $invoice['invoice_ID']; ?>">
                                <input type="hidden" name="invoice_id" value="<?php echo $invoice['invoice_ID']; ?>">
                                <input type="hidden" name="action_type" id="action_<?php echo $invoice['invoice_ID']; ?>" value="">

                                <div class="btn-flex-row">
                                    <button type="button" class="btn-action btn-approve" onclick="submitDecision(<?php echo $invoice['invoice_ID']; ?>, 'Approve')">
                                        Approve
                                    </button>
                                    <button type="button" class="btn-action btn-reject" onclick="toggleRejectionBox(<?php echo $invoice['invoice_ID']; ?>)">
                                        Reject
                                    </button>
                                </div>

                                <div class="inner-reason-box" id="reject_box_<?php echo $invoice['invoice_ID']; ?>">
                                    <textarea name="rejection_reason" id="reason_input_<?php echo $invoice['invoice_ID']; ?>" placeholder="State the reason for rejecting this invoice..."></textarea>
                                    <button type="button" class="btn-action btn-confirm-reject" onclick="submitDecision(<?php echo $invoice['invoice_ID']; ?>, 'Reject')">
                                        Confirm Permanent Rejection
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Queue is Clear!</h3>
                    <p>There are currently no invoice claim submissions marked as 'Under Review' requiring validation.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function toggleRejectionBox(id) {
            const box = document.getElementById('reject_box_' + id);
            box.style.display = (box.style.display === 'block') ? 'none' : 'block';
        }

        function submitDecision(id, type) {
            if (type === 'Reject') {
                const reason = document.getElementById('reason_input_' + id).value.trim();
                if (reason.length === 0) {
                    alert('Action Prevented: Please state an explicit validation rejection reason before confirming.');
                    return;
                }
            }
            
            document.getElementById('action_' + id).value = type;
            document.getElementById('form_' + id).submit();
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>