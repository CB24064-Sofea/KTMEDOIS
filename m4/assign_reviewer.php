<?php
// =========================================================================
// FILE        : assign_reviewer.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : AssignReviewerUI — used by Admin/Officer
// DESCRIPTION : Admin or KTM Officer assigns a staff reviewer to a specific
//               invoice or delivery order. Calls ReviewAndApprovalController
//               to persist the assignment and log the action.
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/ReviewAndApprovalController.php";

$current_page = basename($_SERVER['PHP_SELF']);
$message = '';
$msg_type = '';

// ── Handle POST: assign reviewer ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id  = intval($_POST['invoice_id'] ?? 0);
    $reviewer_id = trim($_POST['reviewer_id'] ?? '');

    if ($invoice_id > 0 && !empty($reviewer_id)) {
        // Update invoice to Under Review and log assignment
        $stmt = $conn->prepare(
            "UPDATE invoice SET invoice_status = 'Under Review', reason = ? WHERE invoice_ID = ?"
        );
        $note = "Assigned to reviewer: $reviewer_id";
        $stmt->bind_param("si", $note, $invoice_id);

        if ($stmt->execute()) {
            // Log in audit_log
            $staffId  = isset($_SESSION['staff_id']) ? $_SESSION['staff_id'] : 'STF001';
            $recordId = 'TRX-' . date('YmdHis');
            $action   = 'AssignedReviewer';
            $stmt2 = $conn->prepare(
                "INSERT INTO audit_log (staff_ID, invoice_ID, action, record_ID) VALUES (?, ?, ?, ?)"
            );
            $stmt2->bind_param("siss", $staffId, $invoice_id, $action, $recordId);
            $stmt2->execute();
            $stmt2->close();

            // Insert notification
            $inv_stmt = $conn->prepare(
                "SELECT i.invoice_num, d.customer_ID FROM invoice i
                 INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
                 WHERE i.invoice_ID = ? LIMIT 1"
            );
            $inv_stmt->bind_param("i", $invoice_id);
            $inv_stmt->execute();
            $inv = $inv_stmt->get_result()->fetch_assoc();
            $inv_stmt->close();

            if ($inv) {
                $ntype   = 'Reviewer Assigned';
                $ncontent = "Invoice {$inv['invoice_num']} has been assigned to reviewer $reviewer_id for evaluation.";
                $nstmt = $conn->prepare(
                    "INSERT INTO notification (customer_ID, type, content) VALUES (?, ?, ?)"
                );
                $nstmt->bind_param("sss", $inv['customer_ID'], $ntype, $ncontent);
                $nstmt->execute();
                $nstmt->close();
            }

            $message  = "Reviewer assigned successfully. Invoice moved to Under Review.";
            $msg_type = 'success';
        } else {
            $message  = "Database error. Please try again.";
            $msg_type = 'danger';
        }
        $stmt->close();
    } else {
        $message  = "Please select both an invoice and a reviewer.";
        $msg_type = 'warning';
    }
}

// ── Fetch data for the form ───────────────────────────────────────────────────
// Invoices that are Submitted (not yet assigned)
$invoices = $conn->query(
    "SELECT i.invoice_ID, i.invoice_num, i.invoice_status, i.total, i.invoice_date
     FROM invoice i
     WHERE i.invoice_status IN ('Submitted','Under Review')
     ORDER BY i.invoice_date DESC"
);

// Staff that can be assigned (Procurement Officer, Manager)
$staff = $conn->query(
    "SELECT staff_ID, staff_name, role FROM ktmb_staff
     WHERE role IN ('Procurement Officer','Manager')
     ORDER BY staff_name ASC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS - Assign Reviewer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <style>
        .app-layout-wrapper    { display:flex; flex-direction:column; width:100%; height:100vh; background:#f3f5f9; }
        .lower-split-container { display:flex; flex-grow:1; overflow:hidden; }
        .content-body          { flex-grow:1; padding:36px; overflow-y:auto; font-family:'Inter',sans-serif; color:#333; }

        :root { --navy:#002D62; --border:#e2e8f0; --muted:#718096; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-title  { font-size:26px; font-weight:700; color:var(--navy); }
        .ktmb-logo   { height:46px; width:auto; }

        .alert         { padding:13px 18px; border-radius:8px; font-size:14px; font-weight:500; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .alert-danger  { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .alert-warning { background:#fffbeb; color:#92400e; border:1px solid #fde68a; }

        .card       { background:#fff; border-radius:10px; border:1px solid var(--border); padding:28px; margin-bottom:22px; }
        .card-title { font-size:15px; font-weight:700; color:#1a1a1a; margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid var(--border); }

        .form-grid  { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
        .form-group label { font-size:13px; font-weight:600; color:#4a5568; display:block; margin-bottom:6px; }
        select, textarea { width:100%; padding:11px 13px; border:1px solid var(--border); border-radius:7px; font-size:14px; font-family:'Inter',sans-serif; outline:none; background:#fff; }
        select:focus, textarea:focus { border-color:var(--navy); }
        .form-note  { font-size:12px; color:var(--muted); margin-top:5px; }

        .btn-assign { background:var(--navy); color:#fff; border:none; padding:13px 28px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; transition:opacity 0.2s; }
        .btn-assign:hover { opacity:0.88; }

        .badge { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .s-submitted    { background:#eef2ff; color:#4f46e5; }
        .s-under-review { background:#fef3c7; color:#d97706; }

        table  { width:100%; border-collapse:collapse; font-size:14px; }
        th     { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:13px 16px; border-bottom:2px solid var(--border); text-align:left; }
        td     { padding:13px 16px; border-bottom:1px solid var(--border); vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#f8fafc; }

        .system-footer { text-align:center; font-size:11px; color:#a0aec0; padding-top:32px; letter-spacing:1px; }
    </style>
</head>
<body>
<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>
    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>
        <div class="content-body">

            <div class="page-header">
                <h1 class="page-title">Assign Reviewer</h1>
            </div>

            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $msg_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Assignment Form -->
            <div class="card">
                <div class="card-title">Assign Reviewer to Invoice</div>
                <form method="POST" action="assign_reviewer.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Select Invoice <span style="color:#dc2626;">*</span></label>
                            <select name="invoice_id" required>
                                <option value="">— Choose Invoice —</option>
                                <?php if ($invoices && $invoices->num_rows > 0):
                                    while ($inv = $invoices->fetch_assoc()): ?>
                                <option value="<?php echo $inv['invoice_ID']; ?>">
                                    <?php echo htmlspecialchars($inv['invoice_num']); ?> —
                                    MYR <?php echo number_format($inv['total'], 2); ?> —
                                    <?php echo htmlspecialchars($inv['invoice_status']); ?>
                                </option>
                                <?php endwhile; endif; ?>
                            </select>
                            <div class="form-note">Only Submitted or Under Review invoices are shown.</div>
                        </div>

                        <div class="form-group">
                            <label>Assign To (Reviewer) <span style="color:#dc2626;">*</span></label>
                            <select name="reviewer_id" required>
                                <option value="">— Choose Staff —</option>
                                <?php if ($staff && $staff->num_rows > 0):
                                    while ($s = $staff->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($s['staff_ID']); ?>">
                                    <?php echo htmlspecialchars($s['staff_name']); ?> (<?php echo htmlspecialchars($s['role']); ?>)
                                </option>
                                <?php endwhile; endif; ?>
                            </select>
                            <div class="form-note">Only Procurement Officers and Managers are listed.</div>
                        </div>
                    </div>
                    <button type="submit" class="btn-assign">&#10003; Confirm Assignment</button>
                </form>
            </div>

            <!-- Recent assignments from audit log -->
            <div class="card">
                <div class="card-title">Recent Reviewer Assignments</div>
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Invoice No.</th>
                            <th>Assigned By</th>
                            <th>Record ID</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $log = $conn->query(
                        "SELECT a.record_ID, a.timestamp, k.staff_name, i.invoice_num
                         FROM audit_log a
                         INNER JOIN ktmb_staff k ON a.staff_ID = k.staff_ID
                         INNER JOIN invoice i ON a.invoice_ID = i.invoice_ID
                         WHERE a.action = 'AssignedReviewer'
                         ORDER BY a.timestamp DESC LIMIT 10"
                    );
                    if ($log && $log->num_rows > 0):
                        while ($row = $log->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y, H:i', strtotime($row['timestamp'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['invoice_num']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                            <td><code style="font-size:11px;color:#718096;"><?php echo htmlspecialchars($row['record_ID']); ?></code></td>
                        </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="4" style="text-align:center;padding:30px;color:#718096;">No assignments recorded yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="system-footer">© 2026 KTMEDOIS INTEGRATED PORTAL</div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
