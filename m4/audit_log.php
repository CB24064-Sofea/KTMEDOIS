<?php
// =========================================================================
// FILE        : audit_log.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : SDD_CLS_403 — auditLogUI
//               SDD_CLS_405 — AuditController.fetchLogData()
// DESCRIPTION : Displays a searchable, chronological audit trail of all
//               officer actions taken on invoices. Calls fetchLogData() to
//               retrieve filtered log records from the audit_log table.
//               This page is read-only — no modifications are made here.
//
// AUTHOR      : Module 4 Developer
// DATE        : June 2026
// =========================================================================

include 'db.php'; // Load shared database connection
$current_page = basename($_SERVER['PHP_SELF']); // Used by sidebar active state

// ── Stat counts for summary cards ────────────────────────────────────────────
$total_logs  = $conn->query("SELECT COUNT(*) as c FROM audit_log")->fetch_assoc()['c'];

// Count approvals and rejections for TODAY only (using CURDATE())
$approved_td = $conn->query(
    "SELECT COUNT(*) as c FROM audit_log WHERE action='Verified' AND DATE(timestamp)=CURDATE()"
)->fetch_assoc()['c'];

$rejected_td = $conn->query(
    "SELECT COUNT(*) as c FROM audit_log WHERE action='Rejected' AND DATE(timestamp)=CURDATE()"
)->fetch_assoc()['c'];

// Count active officers from ktmb_staff table
$officers = $conn->query(
    "SELECT COUNT(*) as c FROM ktmb_staff WHERE role='Procurement Officer'"
)->fetch_assoc()['c'];

// ── fetchLogData() — SDD_CLS_405 AuditController ─────────────────────────────
// Retrieves audit log entries with optional search filter.
// Supports filtering by Staff ID or Invoice Number.
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
    // Sanitise search input to prevent SQL injection
    $safe_search = $conn->real_escape_string($search);

    // Search by Staff ID or Invoice Number using LIKE for partial matches
    $sql = "SELECT a.log_ID, a.staff_ID, a.invoice_ID, a.action,
                   a.record_ID, a.timestamp,
                   k.staff_name,
                   i.invoice_num
            FROM audit_log a
            INNER JOIN ktmb_staff k ON a.staff_ID  = k.staff_ID   -- Get staff name
            INNER JOIN invoice    i ON a.invoice_ID = i.invoice_ID -- Get invoice number
            WHERE a.staff_ID    LIKE '%$safe_search%'
               OR i.invoice_num LIKE '%$safe_search%'
            ORDER BY a.timestamp DESC"; // Most recent logs first
} else {
    // No search filter — return all log records
    $sql = "SELECT a.log_ID, a.staff_ID, a.invoice_ID, a.action,
                   a.record_ID, a.timestamp,
                   k.staff_name,
                   i.invoice_num
            FROM audit_log a
            INNER JOIN ktmb_staff k ON a.staff_ID  = k.staff_ID
            INNER JOIN invoice    i ON a.invoice_ID = i.invoice_ID
            ORDER BY a.timestamp DESC";
}

$result = $conn->query($sql);

// Check for query errors
if (!$result) {
    error_log("Audit log query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Portal - Audit Log</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color:#f3f5f9; --card-bg:#ffffff; --primary-navy:#002D62; --dark-gray:#1a1a1a; --border-color:#e2e8f0; --text-muted:#718096; }
        * { box-sizing:border-box; margin:0; padding:0; font-family:'Inter',sans-serif; }
        body { background-color:var(--bg-color); display:flex; height:100vh; overflow:hidden; color:#333; }
        .workspace { flex-grow:1; padding:40px; overflow-y:auto; width:100%; display:flex; flex-direction:column; }
        .header-area { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
        .header-title { font-size:28px; font-weight:700; color:var(--primary-navy); }
        .logo-container { height:50px; display:flex; align-items:center; margin-left:auto; }
        .logo-container img { height:100%; width:auto; object-fit:contain; }
        .stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:30px; }
        .stat-card { background:var(--card-bg); border-radius:12px; border:1px solid var(--border-color); padding:20px; }
        .stat-label { font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; }
        .stat-number { font-size:28px; font-weight:700; }
        .search-form { margin-bottom:20px; display:flex; gap:10px; max-width:460px; }
        .search-input { flex:1; padding:11px 16px; border-radius:8px; border:1px solid var(--border-color); font-size:14px; outline:none; }
        .search-input:focus { border-color:var(--primary-navy); }
        .btn-search { background:var(--primary-navy); color:#fff; border:none; padding:11px 20px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; }
        .btn-clear { background:#f1f5f9; color:#4a5568; border:1px solid #cbd5e0; padding:11px 16px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; text-decoration:none; }
        .card { background:var(--card-bg); border-radius:12px; border:1px solid var(--border-color); padding:24px; }
        .table-responsive { width:100%; overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        th { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:14px 16px; border-bottom:2px solid var(--border-color); }
        td { padding:13px 16px; border-bottom:1px solid var(--border-color); color:var(--dark-gray); vertical-align:middle; }
        tr:hover td { background:#f8fafc; }
        code { font-size:12px; background:#f1f5f9; padding:3px 7px; border-radius:4px; }
        .badge { display:inline-block; padding:5px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .a-Verified      { background:#ecfdf5; color:#059669; }
        .a-Rejected      { background:#fef2f2; color:#dc2626; }
        .a-Finance-Review{ background:#e0f2fe; color:#0369a1; }
        .a-Under-Review  { background:#fef3c7; color:#d97706; }
        .a-default       { background:#f1f5f9; color:#4a5568; }
        .empty-state { text-align:center; padding:40px; color:var(--text-muted); font-size:14px; }
        .system-footer { text-align:center; font-size:11px; color:#a0aec0; margin-top:auto; padding-top:40px; letter-spacing:1px; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="workspace">

    <div class="header-area">
        <h1 class="header-title">System Audit Log</h1>
        <div class="logo-container"><img src="ktmb_logo.jpg" alt="KTMB Logo"></div>
    </div>

    <!-- Summary stat cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Total Log Entries</div>
            <div class="stat-number" style="color:#002D62;"><?php echo $total_logs; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Approved Today</div>
            <div class="stat-number" style="color:#059669;"><?php echo $approved_td; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Rejected Today</div>
            <div class="stat-number" style="color:#dc2626;"><?php echo $rejected_td; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active Officers</div>
            <div class="stat-number" style="color:#0369a1;"><?php echo $officers; ?></div>
        </div>
    </div>

    <!-- Search form — filters by Staff ID or Invoice Number -->
    <form method="GET" action="audit_log.php" class="search-form">
        <input type="text" name="search" class="search-input"
               placeholder="Search by Staff ID or Invoice No..."
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn-search">Search</button>
        <?php if (!empty($search)): ?>
            <!-- Clear button resets the search filter -->
            <a href="audit_log.php" class="btn-clear">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Audit log table -->
    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Timestamp</th>
                        <th>Staff ID</th>
                        <th>Staff Name</th>
                        <th>Invoice No.</th>
                        <th>Action</th>
                        <th>Record ID</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()):
                        // Map action value to CSS class for badge colour
                        $action_class = 'a-' . str_replace(' ', '-', $row['action']);
                    ?>
                    <tr>
                        <td style="font-size:12px;color:#718096;">#<?php echo $row['log_ID']; ?></td>
                        <td style="font-size:13px;">
                            <!-- Split timestamp into date and time for readability -->
                            <div><?php echo date('d M Y', strtotime($row['timestamp'])); ?></div>
                            <div style="color:#718096;font-size:11px;"><?php echo date('H:i:s', strtotime($row['timestamp'])); ?></div>
                        </td>
                        <td><code><?php echo htmlspecialchars($row['staff_ID']); ?></code></td>
                        <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                        <td>
                            <!-- Clickable invoice number — links to review workspace for that invoice -->
                            <a href="review_workspace.php?id=<?php echo $row['invoice_ID']; ?>"
                               style="color:var(--primary-navy);font-weight:600;text-decoration:none;font-size:13px;">
                                <?php echo htmlspecialchars($row['invoice_num']); ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge <?php echo htmlspecialchars($action_class); ?>">
                                <?php echo htmlspecialchars($row['action']); ?>
                            </span>
                        </td>
                        <td><code style="font-size:11px;color:#718096;"><?php echo htmlspecialchars($row['record_ID']); ?></code></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Empty state — shown when no records match the search or table is empty -->
                    <tr>
                        <td colspan="7" class="empty-state">
                            <?php if (!empty($search)): ?>
                                No audit log entries found matching "<?php echo htmlspecialchars($search); ?>".
                                <a href="audit_log.php" style="color:var(--primary-navy);">Clear search</a>
                            <?php else: ?>
                                No audit log entries recorded yet.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="system-footer">© 2026 KTMEDOIS INTEGRATED PORTAL</footer>
</main>
</body>
</html>
<?php $conn->close(); // Close DB connection ?>
