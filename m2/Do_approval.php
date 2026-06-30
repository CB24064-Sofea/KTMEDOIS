<?php
// Initialize system session parameters
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Centralized Database Connection Loader
require_once dirname(__DIR__) . "/db.php";

// Simulate Session-Authenticated KTM Staff
$_SESSION['staff_id'] = "STF001";
$_SESSION['staff_name'] = "Ahmad Faiz";
$_SESSION['role'] = "KTM Staff";

/**
 * Class DeliveryOrderVerificationManager
 * Handles KTM Staff administrative approvals using OOP Principles
 */
class DeliveryOrderVerificationManager {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Fetch all delivery orders currently awaiting verification
     */
    public function getPendingDeliveryOrders() {
        $query = "SELECT * FROM delivery_order WHERE PO_status = 'Pending' OR PO_status = 'Approved' ORDER BY created_date ASC";
        return $this->db->query($query);
    }

    /**
     * Process verification decision (Approve / Reject)
     */
    public function verifyDeliveryOrder($do_id, $decision) {
        $status = ($decision === 'approve') ? 'Approved' : 'Rejected';
        
        $query = "UPDATE delivery_order SET PO_status = ? WHERE DO_ID = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $status, $do_id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}

// Instantiate the Verification Manager Engine
$approvalEngine = new DeliveryOrderVerificationManager($conn);

$message = "";
$message_type = "";

// Handle Approval / Rejection Postbacks Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_verify'])) {
    $target_do = trim($_POST['do_id']);
    $decision = trim($_POST['decision']); 

    if (!empty($target_do) && ($decision === 'approve' || $decision === 'reject')) {
        if ($approvalEngine->verifyDeliveryOrder($target_do, $decision)) {
            $message = "Successfully updated Delivery Order " . htmlspecialchars($target_do) . " to '" . ucfirst($decision) . "d'.";
            $message_type = "success";
        } else {
            $message = "Operational Error: Could not execute state change inside the database.";
            $message_type = "error";
        }
    }
}

// Load data rows
$pending_list = $approvalEngine->getPendingDeliveryOrders();
$pending_count = $pending_list ? $pending_list->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS - Staff DO Verification Portal</title>
    <style>
        * { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        
        /* Layout structures matching main layout wrappers */
        .app-layout-wrapper { display: flex; flex-direction: column; width: 100%; height: 100vh; overflow: hidden; background-color: #f8fafc; }
        .lower-split-container { display: flex; flex-grow: 1; width: 100%; overflow: hidden; }
        .content-body { padding: 32px; overflow-y: auto; flex-grow: 1; }
        
        .alert { padding: 14px 18px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; font-weight: 500; }
        .alert.success { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert.error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
        
        .dashboard-meta-summary { background: #ffffff; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; }
        .table-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background-color: #f8fafc; padding: 14px 20px; font-size: 12px; font-weight: 700; color: #718096; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        td { padding: 16px 20px; font-size: 14px; color: #2d3748; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
        
        .btn-action { padding: 8px 14px; font-size: 13px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; }
        .btn-approve { background-color: #10b981; color: #ffffff; margin-right: 4px; }
        .btn-reject { background-color: #ef4444; color: #ffffff; }
        .btn-view { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; }
        .status-badge { background-color: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>

    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>

        <div class="content-body">
            <div style="margin-bottom: 24px;">
                <h2 style="color: #002D62; font-size: 22px; font-weight: 700;">Delivery Order Verification Desk</h2>
                <p style="color: #718096; font-size: 14px; margin-top: 2px;">Inspect supplier inbound payloads, review digital proofs, and manage validation lifecycles.</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-meta-summary">
                <div>
                    <span style="font-size: 13px; color: #718096; font-weight: 600; text-transform: uppercase;">Active Submissions</span>
                    <h3 style="font-size: 28px; color: #002D62; font-weight: 700; margin-top: 2px;"><?php echo $pending_count; ?> entries</h3>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 13px; color: #718096; font-weight: 500;">Reviewer: <strong><?php echo htmlspecialchars($_SESSION['staff_name']); ?></strong></span>
                </div>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>DO Number</th>
                            <th>Purchase Order</th>
                            <th>Supplier ID</th>
                            <th>Project Reference</th>
                            <th>Digital Proof</th>
                            <th>Submission Date</th>
                            <th>State</th>
                            <th style="text-align: right;">Administrative Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pending_list && $pending_list->num_rows > 0): ?>
                            <?php while($row = $pending_list->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 600; color: #002D62;"><?php echo htmlspecialchars($row['DO_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['PO_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['supplier_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['project_reference']); ?></td>
                                    <td>
                                        <a href="/KTMEDOIS/<?php echo htmlspecialchars($row['proof_of_delivery']); ?>" target="_blank" class="btn-view">📄 View Proof</a>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($row['created_date'])); ?></td>
                                    <td>
                                        <span class="status-badge"><?php echo htmlspecialchars($row['PO_status']); ?></span>
                                    </td>
                                    <td align="right">
                                        <form action="Do_approval.php" method="POST" style="display: inline-block;">
                                            <input type="hidden" name="do_id" value="<?php echo htmlspecialchars($row['DO_ID']); ?>">
                                            <input type="hidden" name="decision" value="approve">
                                            <button type="submit" name="action_verify" class="btn-action btn-approve" onclick="return confirm('Approve submission?');">✓ Approve</button>
                                        </form>
                                        <form action="Do_approval.php" method="POST" style="display: inline-block;">
                                            <input type="hidden" name="do_id" value="<?php echo htmlspecialchars($row['DO_ID']); ?>">
                                            <input type="hidden" name="decision" value="reject">
                                            <button type="submit" name="action_verify" class="btn-action btn-reject" onclick="return confirm('Reject submission?');">✗ Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #a0aec0; padding: 48px 0; font-style: italic;"> No entries require verification.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>