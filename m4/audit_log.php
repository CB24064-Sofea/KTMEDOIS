<?php
// =========================================================================
// FILE        : audit_log.php
// SDD CLASS   : SDD_CLS_403 — auditLogUI
//               SDD_CLS_406 — AuditController.fetchLogData()
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/AuditController.php";

$search    = isset($_GET['search']) ? trim($_GET['search']) : '';
$auditCtrl = new AuditController($conn);
$result    = $auditCtrl->fetchLogData($search);
$stats     = $auditCtrl->getAuditStats();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS - Audit Log</title>
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

        .stats-row   { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
        .stat-card   { background:#fff; border-radius:10px; border:1px solid var(--border); padding:18px; }
        .stat-label  { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.6px; margin-bottom:8px; font-weight:600; }
        .stat-number { font-size:28px; font-weight:700; }

        /* SDD_CLS_403: searchLogs() */
        .search-form  { display:flex; gap:10px; max-width:460px; margin-bottom:20px; }
        .search-input { flex:1; padding:10px 15px; border-radius:7px; border:1px solid var(--border); font-size:14px; outline:none; font-family:'Inter',sans-serif; }
        .search-input:focus { border-color:var(--navy); }
        .btn-search   { background:var(--navy); color:#fff; border:none; padding:10px 20px; border-radius:7px; font-weight:600; font-size:14px; cursor:pointer; }
        .btn-clear    { background:#f1f5f9; color:#4a5568; border:1px solid #cbd5e0; padding:10px 16px; border-radius:7px; font-weight:600; font-size:14px; cursor:pointer; text-decoration:none; display:flex; align-items:center; }

        .table-card   { background:#fff; border-radius:10px; border:1px solid var(--border); overflow:hidden; }
        table         { width:100%; border-collapse:collapse; font-size:14px; }
        th            { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:13px 16px; border-bottom:2px solid var(--border); text-align:left; }
        td            { padding:13px 16px; border-bottom:1px solid var(--border); color:#1a1a1a; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td   { background:#f8fafc; }
        code          { font-size:12px; background:#f1f5f9; padding:3px 7px; border-radius:4px; }

        .badge           { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .a-Verified      { background:#ecfdf5; color:#059669; }
        .a-Rejected      { background:#fef2f2; color:#dc2626; }
        .a-UnderReview   { background:#fef3c7; color:#d97706; }
        .a-default       { background:#f1f5f9; color:#4a5568; }

        .empty-cell      { text-align:center; padding:40px; color:var(--muted); font-size:14px; }
        .system-footer   { text-align:center; font-size:11px; color:#a0aec0; padding-top:32px; letter-spacing:1px; }
    </style>
</head>
<body>
<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>
    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>
        <div class="content-body">

            <div class="page-header">
                <h1 class="page-title">System Audit Log</h1>
            </div>

            <!-- SDD_CLS_403: displayAuditLogs() stat cards -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-label">Total Log Entries</div>
                    <div class="stat-number" style="color:#002D62;"><?php echo $stats['total_logs']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Approved Today</div>
                    <div class="stat-number" style="color:#059669;"><?php echo $stats['approved_td']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Rejected Today</div>
                    <div class="stat-number" style="color:#dc2626;"><?php echo $stats['rejected_td']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Active Officers</div>
                    <div class="stat-number" style="color:#0369a1;"><?php echo $stats['officers']; ?></div>
                </div>
            </div>

            <!-- SDD_CLS_403: searchLogs() — search by Staff ID or Invoice No -->
            <form method="GET" action="audit_log.php" class="search-form">
                <input type="text" name="search" class="search-input"
                       placeholder="Search by Staff ID or Invoice No..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="audit_log.php" class="btn-clear">Clear</a>
                <?php endif; ?>
            </form>

            <div class="table-card">
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
                            $actionClass = 'a-' . str_replace(' ', '', $row['action']);
                        ?>
                        <tr>
                            <td style="font-size:12px;color:#718096;">#<?php echo $row['log_ID']; ?></td>
                            <td>
                                <div style="font-size:13px;"><?php echo date('d M Y', strtotime($row['timestamp'])); ?></div>
                                <div style="font-size:11px;color:#718096;"><?php echo date('H:i:s', strtotime($row['timestamp'])); ?></div>
                            </td>
                            <td><code><?php echo htmlspecialchars($row['staff_ID']); ?></code></td>
                            <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                            <td>
                                <a href="review_workspace.php?id=<?php echo $row['invoice_ID']; ?>"
                                   style="color:var(--navy);font-weight:600;text-decoration:none;font-size:13px;">
                                    <?php echo htmlspecialchars($row['invoice_num']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge <?php echo htmlspecialchars($actionClass); ?>">
                                    <?php echo htmlspecialchars($row['action']); ?>
                                </span>
                            </td>
                            <td><code style="font-size:11px;color:#718096;"><?php echo htmlspecialchars($row['record_ID']); ?></code></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-cell">
                            <?php if (!empty($search)): ?>
                                No entries found for "<?php echo htmlspecialchars($search); ?>". <a href="audit_log.php" style="color:var(--navy);">Clear</a>
                            <?php else: ?>
                                No audit log entries recorded yet.
                            <?php endif; ?>
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="system-footer">© 2026 KTMEDOIS INTEGRATED PORTAL</div>

        </div><!-- /content-body -->
    </div><!-- /lower-split-container -->
</div><!-- /app-layout-wrapper -->
</body>
</html>
<?php $conn->close(); ?>
