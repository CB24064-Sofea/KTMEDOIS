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

// ── Session guard ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['staff_auth'])) {
    header("Location: " . app_url('m1/staff_login.php')); exit;
}

$staffId  = $_SESSION['staff_auth']['staff_id'] ?? $_SESSION['staff_auth']['staff_ID'] ?? 'STF001';
$current_page = basename($_SERVER['PHP_SELF']);
$message = '';
$msg_type = '';

// ── Handle POST: assign reviewer ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id  = intval($_POST['invoice_id'] ?? 0);
    $reviewer_id = trim($_POST['reviewer_id'] ?? '');
    $note_extra  = trim($_POST['assignment_note'] ?? '');

    if ($invoice_id > 0 && !empty($reviewer_id)) {
        // Update invoice to Under Review and log assignment
        $note = "Assigned to reviewer: $reviewer_id" . ($note_extra ? " — $note_extra" : '');
        $stmt = $conn->prepare(
            "UPDATE invoice SET invoice_status = 'Under Review', reason = ? WHERE invoice_ID = ?"
        );
        $stmt->bind_param("si", $note, $invoice_id);

        if ($stmt->execute()) {
            // ── Log in audit_log ──────────────────────────────────────────
            $recordId = 'TRX-' . date('YmdHis');
            $action   = 'AssignedReviewer';
            $stmt2 = $conn->prepare(
                "INSERT INTO audit_log (staff_ID, invoice_ID, action, record_ID) VALUES (?, ?, ?, ?)"
            );
            $stmt2->bind_param("siss", $staffId, $invoice_id, $action, $recordId);
            $stmt2->execute();
            $stmt2->close();

            // ── Insert notification ───────────────────────────────────────
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
                $ncontent = "Invoice {$inv['invoice_num']} has been assigned to reviewer {$reviewer_id} for evaluation.";
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
            $message  = "Database error: " . $conn->error . ". Please try again.";
            $msg_type = 'danger';
        }
        $stmt->close();
    } else {
        $message  = "Please select both an invoice and a reviewer.";
        $msg_type = 'warning';
    }
}

// ── Fetch data for the form ───────────────────────────────────────────────────
$invoices = $conn->query(
    "SELECT i.invoice_ID, i.invoice_num, i.invoice_status, i.total, i.invoice_date,
            s.supplier_name
     FROM invoice i
     INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
     INNER JOIN supplier s ON d.supplier_ID = s.supplier_ID
     WHERE i.invoice_status IN ('Submitted','Under Review')
     ORDER BY i.invoice_date DESC"
);

$staff = $conn->query(
    "SELECT staff_ID, staff_name, role FROM ktmb_staff
     WHERE role IN ('Procurement Officer','Manager','Finance Officer')
     ORDER BY role ASC, staff_name ASC"
);

// Recent assignments
$recent = $conn->query(
    "SELECT a.log_ID, a.record_ID, a.timestamp, k.staff_name, i.invoice_num, i.invoice_status
     FROM audit_log a
     INNER JOIN ktmb_staff k ON a.staff_ID = k.staff_ID
     INNER JOIN invoice i ON a.invoice_ID = i.invoice_ID
     WHERE a.action = 'AssignedReviewer'
     ORDER BY a.timestamp DESC LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS – Assign Reviewer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/KTMEDOIS-main/sidebar.css">
    <style>
        .app-layout-wrapper    { display:flex; flex-direction:column; width:100%; height:100vh; background:#f3f5f9; }
        .lower-split-container { display:flex; flex-grow:1; overflow:hidden; }
        .content-body          { flex-grow:1; padding:36px; overflow-y:auto; font-family:'Inter',sans-serif; color:#333; }

        :root { --navy:#002D62; --border:#e2e8f0; --muted:#718096; }
        .page-header  { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-title   { font-size:26px; font-weight:700; color:var(--navy); }

        .alert         { padding:13px 18px; border-radius:8px; font-size:14px; font-weight:500; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .alert-danger  { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .alert-warning { background:#fffbeb; color:#92400e; border:1px solid #fde68a; }

        .card        { background:#fff; border-radius:10px; border:1px solid var(--border); padding:28px; margin-bottom:22px; }
        .card-title  { font-size:15px; font-weight:700; color:#1a1a1a; margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid var(--border); }

        .form-grid   { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
        .form-group label { font-size:13px; font-weight:600; color:#4a5568; display:block; margin-bottom:6px; }
        select, textarea, input[type="text"] { width:100%; padding:11px 13px; border:1px solid var(--border); border-radius:7px; font-size:14px; font-family:'Inter',sans-serif; outline:none; background:#fff; }
        select:focus, textarea:focus, input:focus { border-color:var(--navy); box-shadow:0 0 0 3px rgba(0,45,98,0.1); }
        .form-note   { font-size:12px; color:var(--muted); margin-top:5px; }

        .btn-assign  { background:var(--navy); color:#fff; border:none; padding:13px 28px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; transition:opacity 0.2s; }
        .btn-assign:hover { opacity:0.88; }

        .badge { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .s-submitted    { background:#eef2ff; color:#4f46e5; }
        .s-under-review { background:#fef3c7; color:#d97706; }
        .s-approved     { background:#ecfdf5; color:#059669; }

        table  { width:100%; border-collapse:collapse; font-size:14px; }
        th     { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:13px 16px; border-bottom:2px solid var(--border); text-align:left; }
        td     { padding:13px 16px; border-bottom:1px solid var(--border); vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#f8fafc; }

        .role-tag  { font-size:11px; background:#eef2ff; color:#4f46e5; padding:2px 8px; border-radius:50px; font-weight:600; margin-left:6px; }
        .empty-cell { text-align:center; padding:30px; color:var(--muted); }
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
                <h1 class="page-title">Assign Inspector Duties</h1>
            </div>

            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $msg_type; ?>">
                <?php if ($msg_type === 'success'): ?>&#10003;<?php elseif ($msg_type === 'danger'): ?>&#9888;<?php else: ?>&#9432;<?php endif; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Assignment Form -->
            <div class="card">
                <div class="card-title">&#128204; Assign Reviewer to Invoice</div>
                <form method="POST" action="assign_reviewer.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Select Invoice <span style="color:#dc2626;">*</span></label>
                            <select name="invoice_id" required>
                                <option value="">— Choose Invoice —</option>
                                <?php if ($invoices && $invoices->num_rows > 0):
                                    while ($inv = $invoices->fetch_assoc()): ?>
                                <option value="<?php echo $inv['invoice_ID']; ?>">
                                    <?php echo htmlspecialchars($inv['invoice_num']); ?>
                                    — RM <?php echo number_format($inv['total'], 2); ?>
                                    — <?php echo htmlspecialchars($inv['invoice_status']); ?>
                                    (<?php echo htmlspecialchars($inv['supplier_name']); ?>)
                                </option>
                                <?php endwhile; endif; ?>
                            </select>
                            <div class="form-note">Only Submitted or Under Review invoices are listed.</div>
                        </div>

                        <div class="form-group">
                            <label>Assign To (Reviewer) <span style="color:#dc2626;">*</span></label>
                            <select name="reviewer_id" required>
                                <option value="">— Choose Staff Member —</option>
                                <?php if ($staff && $staff->num_rows > 0):
                                    $curRole = '';
                                    while ($s = $staff->fetch_assoc()):
                                        if ($curRole !== $s['role']) {
                                            $curRole = $s['role'];
                                            echo "<optgroup label='{$curRole}'>";
                                        }
                                ?>
                                <option value="<?php echo htmlspecialchars($s['staff_ID']); ?>">
                                    <?php echo htmlspecialchars($s['staff_name']); ?> (<?php echo htmlspecialchars($s['staff_ID']); ?>)
                                </option>
                                <?php endwhile;
                                echo '</optgroup>';
                                endif; ?>
                            </select>
                            <div class="form-note">Procurement Officers, Finance Officers, and Managers available.</div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:20px;">
                        <label>Assignment Note <span style="color:#a0aec0;">(optional)</span></label>
                        <textarea name="assignment_note" rows="2" placeholder="Add any specific instructions for the reviewer..."></textarea>
                    </div>

                    <button type="submit" class="btn-assign">&#10003; Confirm Assignment</button>
                </form>
            </div>

            <!-- Recent Assignments Table -->
            <div class="card">
                <div class="card-title">&#128338; Recent Reviewer Assignments</div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Timestamp</th>
                            <th>Invoice No.</th>
                            <th>Assigned By</th>
                            <th>Current Status</th>
                            <th>Record ID</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($recent && $recent->num_rows > 0):
                        while ($row = $recent->fetch_assoc()):
                            $slug = 's-' . strtolower(str_replace(' ','-',$row['invoice_status']));
                    ?>
                    <tr>
                        <td style="color:#a0aec0;font-size:12px;">#<?php echo $row['log_ID']; ?></td>
                        <td>
                            <div><?php echo date('d M Y', strtotime($row['timestamp'])); ?></div>
                            <div style="font-size:11px;color:#a0aec0;"><?php echo date('H:i', strtotime($row['timestamp'])); ?></div>
                        </td>
                        <td><strong><?php echo htmlspecialchars($row['invoice_num']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                        <td><span class="badge <?php echo $slug; ?>"><?php echo htmlspecialchars($row['invoice_status']); ?></span></td>
                        <td><code style="font-size:11px;color:#a0aec0;"><?php echo htmlspecialchars($row['record_ID']); ?></code></td>
                    </tr>
                    <?php endwhile;
                    else: ?>
                        <tr><td colspan="6" class="empty-cell">No reviewer assignments recorded yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="system-footer">© <?php echo date('Y'); ?> Keretapi Tanah Melayu Berhad &nbsp;|&nbsp; KTM eDOIS Internal Review Module</div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
