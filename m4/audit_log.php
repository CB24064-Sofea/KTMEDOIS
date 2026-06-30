<?php
// =========================================================================
// FILE        : audit_log.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : SDD_CLS_403 — auditLogUI
//               SDD_CLS_406 — AuditController.fetchLogData()
// DESCRIPTION : Displays paginated system audit trail with search filters.
//               - displayAuditLogs() → renders the log table
//               - searchLogs()       → filters by staff ID, invoice no, action
//               - exportReport()     → CSV download
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/AuditController.php";

// ── Session guard ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['staff_auth'])) {
    header("Location: " . app_url('m1/staff_login.php')); exit;
}

// ── Pagination & Search ───────────────────────────────────────────────────────
$search     = isset($_GET['search']) ? trim($_GET['search']) : '';
$perPage    = 12;
$page       = max(1, (int)($_GET['page'] ?? 1));
$offset     = ($page - 1) * $perPage;

$auditCtrl  = new AuditController($conn);
$total      = $auditCtrl->countLogs($search);
$totalPages = max(1, ceil($total / $perPage));
$result     = $auditCtrl->fetchLogData($search, $perPage, $offset);
$stats      = $auditCtrl->getAuditStats();
$current_page = basename($_SERVER['PHP_SELF']);

// ── CSV Export ────────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportResult = $auditCtrl->fetchLogData($search, 0, 0);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="KTMeDOIS_AuditLog_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Log ID','Timestamp','Staff ID','Staff Name','Invoice No.','Action','Record ID']);
    if ($exportResult) {
        while ($row = $exportResult->fetch_assoc()) {
            fputcsv($out, [
                '#'.$row['log_ID'],
                date('d M Y H:i:s', strtotime($row['timestamp'])),
                $row['staff_ID'],
                $row['staff_name'],
                $row['invoice_num'],
                $row['action'],
                $row['record_ID'],
            ]);
        }
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS – System Audit Log</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <style>
        .app-layout-wrapper    { display:flex; flex-direction:column; width:100%; height:100vh; background:#f3f5f9; }
        .lower-split-container { display:flex; flex-grow:1; overflow:hidden; }
        .content-body          { flex-grow:1; padding:36px; overflow-y:auto; font-family:'Inter',sans-serif; color:#333; }

        :root { --navy:#002D62; --border:#e2e8f0; --muted:#718096; }
        .page-header  { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-title   { font-size:26px; font-weight:700; color:var(--navy); }

        /* ── STAT CARDS ────────────────────────────────────────────────── */
        .stats-row   { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
        .stat-card   { background:#fff; border-radius:10px; border:1px solid var(--border); padding:18px; }
        .stat-label  { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.6px; margin-bottom:8px; font-weight:600; }
        .stat-number { font-size:28px; font-weight:700; }

        /* ── TOOLBAR ───────────────────────────────────────────────────── */
        .toolbar     { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap; }
        .search-form { display:flex; gap:8px; }
        .search-input{ padding:10px 15px; border-radius:7px; border:1px solid var(--border); font-size:14px; outline:none; font-family:'Inter',sans-serif; width:280px; }
        .search-input:focus { border-color:var(--navy); }
        .btn-search  { background:var(--navy); color:#fff; border:none; padding:10px 18px; border-radius:7px; font-weight:600; font-size:14px; cursor:pointer; }
        .btn-clear   { background:#f1f5f9; color:#4a5568; border:1px solid var(--border); padding:10px 14px; border-radius:7px; font-weight:600; font-size:14px; text-decoration:none; display:flex; align-items:center; }
        .btn-export  { background:#059669; color:#fff; border:none; padding:10px 18px; border-radius:7px; font-weight:600; font-size:14px; cursor:pointer; text-decoration:none; display:flex; align-items:center; gap:6px; }
        .btn-export:hover { opacity:0.88; }

        /* ── TABLE ─────────────────────────────────────────────────────── */
        .table-card  { background:#fff; border-radius:10px; border:1px solid var(--border); overflow:hidden; margin-bottom:20px; }
        table        { width:100%; border-collapse:collapse; font-size:14px; }
        th           { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:13px 16px; border-bottom:2px solid var(--border); text-align:left; }
        td           { padding:13px 16px; border-bottom:1px solid var(--border); color:#1a1a1a; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td  { background:#f8fafc; }

        code         { font-size:12px; background:#f1f5f9; padding:3px 7px; border-radius:4px; color:#4a5568; }

        /* ── ACTION BADGES ─────────────────────────────────────────────── */
        .badge           { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .a-Verified      { background:#ecfdf5; color:#059669; }
        .a-Approved      { background:#d1fae5; color:#065f46; }
        .a-Rejected      { background:#fef2f2; color:#dc2626; }
        .a-UnderReview   { background:#fef3c7; color:#d97706; }
        .a-AssignedReviewer { background:#e0f2fe; color:#0369a1; }
        .a-default       { background:#f1f5f9; color:#4a5568; }

        /* ── PAGINATION ────────────────────────────────────────────────── */
        .pagination  { display:flex; justify-content:space-between; align-items:center; font-size:13px; color:var(--muted); }
        .page-links  { display:flex; gap:6px; }
        .page-btn    { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:6px; border:1px solid var(--border); background:#fff; color:#4a5568; text-decoration:none; font-size:13px; font-weight:600; transition:all 0.15s; }
        .page-btn:hover { background:#f1f5f9; }
        .page-btn.active { background:var(--navy); color:#fff; border-color:var(--navy); }
        .page-btn.disabled { opacity:0.4; pointer-events:none; }

        .empty-cell  { text-align:center; padding:40px; color:var(--muted); font-size:14px; }
        .system-footer { text-align:center; font-size:11px; color:#a0aec0; padding-top:28px; letter-spacing:1px; }
    </style>
</head>
<body>
<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>
    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>
        <div class="content-body">

            <div class="page-header">
                <h1 class="page-title">&#128203; System Audit Log</h1>
            </div>

            <!-- SDD_CLS_403: displayAuditLogs() stat cards -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-label">Total Log Entries</div>
                    <div class="stat-number" style="color:var(--navy);"><?php echo number_format($stats['total_logs']); ?></div>
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

            <!-- SDD_CLS_403: searchLogs() toolbar -->
            <div class="toolbar">
                <form method="GET" action="audit_log.php" class="search-form">
                    <input type="text" name="search" class="search-input"
                           placeholder="Search by Staff, Invoice No., or Action..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-search">&#128269; Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="audit_log.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>
                <a href="audit_log.php?export=csv<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn-export">
                    &#11015; Export CSV
                </a>
            </div>

            <!-- Showing info -->
            <div style="font-size:13px;color:var(--muted);margin-bottom:12px;">
                Showing <?php echo number_format(($offset+1)); ?> to <?php echo number_format(min($offset + $perPage, $total)); ?> of <?php echo number_format($total); ?> results
            </div>

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
                            $actionKey = str_replace(' ', '', $row['action']);
                            $badgeClass = 'a-' . $actionKey;
                            if (!in_array($actionKey, ['Verified','Approved','Rejected','UnderReview','AssignedReviewer'])) {
                                $badgeClass = 'a-default';
                            }
                        ?>
                        <tr>
                            <td style="font-size:12px;color:#a0aec0;"><strong>#<?php echo $row['log_ID']; ?></strong></td>
                            <td>
                                <div style="font-size:13px;font-weight:500;"><?php echo date('d M Y', strtotime($row['timestamp'])); ?></div>
                                <div style="font-size:11px;color:#a0aec0;"><?php echo date('H:i:s', strtotime($row['timestamp'])); ?></div>
                            </td>
                            <td><code><?php echo htmlspecialchars($row['staff_ID']); ?></code></td>
                            <td style="font-weight:500;"><?php echo htmlspecialchars($row['staff_name']); ?></td>
                            <td>
                                <a href="review_workspace.php?id=<?php echo $row['invoice_ID']; ?>"
                                   style="color:var(--navy);font-weight:600;text-decoration:none;font-size:13px;">
                                    <?php echo htmlspecialchars($row['invoice_num']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge <?php echo htmlspecialchars($badgeClass); ?>">
                                    <?php echo htmlspecialchars($row['action']); ?>
                                </span>
                            </td>
                            <td><code style="font-size:11px;color:#a0aec0;"><?php echo htmlspecialchars($row['record_ID']); ?></code></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-cell">
                            <?php if (!empty($search)): ?>
                                &#128269; No entries found for &ldquo;<?php echo htmlspecialchars($search); ?>&rdquo;.
                                <a href="audit_log.php" style="color:var(--navy);">Clear search</a>
                            <?php else: ?>
                                &#128203; No audit log entries recorded yet.
                            <?php endif; ?>
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <span>Showing <?php echo number_format(($offset+1)); ?> to <?php echo number_format(min($offset + $perPage, $total)); ?> of <?php echo number_format($total); ?> results</span>
                <div class="page-links">
                    <?php
                    $baseUrl = 'audit_log.php?search=' . urlencode($search) . '&page=';
                    // Prev
                    $prevClass = ($page <= 1) ? 'disabled' : '';
                    echo "<a href='{$baseUrl}" . max(1,$page-1) . "' class='page-btn {$prevClass}'>&laquo;</a>";
                    // Page numbers
                    $start = max(1, $page - 2);
                    $end   = min($totalPages, $page + 2);
                    if ($start > 1) echo "<a href='{$baseUrl}1' class='page-btn'>1</a>";
                    if ($start > 2) echo "<span style='padding:0 4px;color:#a0aec0;'>…</span>";
                    for ($i = $start; $i <= $end; $i++) {
                        $act = ($i === $page) ? 'active' : '';
                        echo "<a href='{$baseUrl}{$i}' class='page-btn {$act}'>{$i}</a>";
                    }
                    if ($end < $totalPages - 1) echo "<span style='padding:0 4px;color:#a0aec0;'>…</span>";
                    if ($end < $totalPages) echo "<a href='{$baseUrl}{$totalPages}' class='page-btn'>{$totalPages}</a>";
                    // Next
                    $nextClass = ($page >= $totalPages) ? 'disabled' : '';
                    echo "<a href='{$baseUrl}" . min($totalPages,$page+1) . "' class='page-btn {$nextClass}'>&raquo;</a>";
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="system-footer">© <?php echo date('Y'); ?> Keretapi Tanah Melayu Berhad &nbsp;|&nbsp; KTM eDOIS Internal Review Module</div>

        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
