<?php
// =========================================================================
// FILE        : review_dashboard.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : SDD_CLS_401 — officerMainDashboardUI
// DESCRIPTION : Main review dashboard for Finance Manager / KTM Approver.
//               Displays pending invoices (Finance Manager) or pending DOs
//               (KTM Approver) based on session role.
//               - filterClaims() → stat cards filter the invoice table
//               - selectInvoice() → opens reviewSubmissionUI (review_workspace)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/ReviewAndApprovalController.php";

// ── Session guard ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['staff_auth'])) {
    header("Location: " . app_url('m1/staff_login.php')); exit;
}

$staffSession = $_SESSION['staff_auth'];
$staffName    = htmlspecialchars($staffSession['name'] ?? 'Staff');
$staffRole    = trim($staffSession['sub_role'] ?? $staffSession['role'] ?? 'Staff');
$staffId      = $staffSession['staff_id'] ?? $staffSession['staff_ID'] ?? 'STF001';

$isFinance    = (stripos($staffRole, 'finance') !== false || stripos($staffRole, 'Finance Manager') !== false);
$isApprover   = (stripos($staffRole, 'approver') !== false || $staffRole === 'Administrator' || $staffRole === 'Manager');

$controller   = new ReviewAndApprovalController($conn, $staffId);
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$stats        = $controller->getDashboardStats();
$result       = $controller->fetchPendingClaims($filterStatus);
$decision     = isset($_GET['decision']) ? $_GET['decision'] : '';
$decision_act = isset($_GET['action'])   ? $_GET['action']   : '';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS – Review Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <style>
        /* ── LAYOUT ENGINE ─────────────────────────────────────────────── */
        .app-layout-wrapper    { display:flex; flex-direction:column; width:100%; height:100vh; background:#f3f5f9; }
        .lower-split-container { display:flex; flex-grow:1; overflow:hidden; }
        .content-body          { flex-grow:1; padding:36px; overflow-y:auto; font-family:'Inter',sans-serif; color:#333; }

        :root { --navy:#002D62; --border:#e2e8f0; --muted:#718096; }

        /* ── PAGE HEADER ───────────────────────────────────────────────── */
        .page-header  { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-title   { font-size:26px; font-weight:700; color:var(--navy); }

        /* ── ALERT BANNERS ─────────────────────────────────────────────── */
        .alert         { padding:13px 18px; border-radius:8px; font-size:14px; font-weight:500; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .alert-danger  { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .alert-warning { background:#fffbeb; color:#92400e; border:1px solid #fde68a; }

        /* ── STAT CARDS ────────────────────────────────────────────────── */
        .stats-row      { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:14px; margin-bottom:28px; }
        .stat-card      { background:#fff; border-radius:10px; border:1px solid var(--border); padding:18px; text-decoration:none; display:block; transition:box-shadow 0.2s; color:inherit; }
        .stat-card:hover{ box-shadow:0 4px 14px rgba(0,0,0,0.08); }
        .stat-card.active-filter { border-color:var(--navy); box-shadow:0 0 0 2px rgba(0,45,98,0.15); }
        .stat-label     { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.6px; margin-bottom:8px; font-weight:600; }
        .stat-number    { font-size:28px; font-weight:700; }

        /* ── SECTION TITLE ─────────────────────────────────────────────── */
        .section-title  { background:var(--navy); color:#fff; padding:14px 20px; border-radius:8px 8px 0 0; font-size:14px; font-weight:700; letter-spacing:0.3px; }

        /* ── TABLE ─────────────────────────────────────────────────────── */
        .table-card     { background:#fff; border-radius:10px; border:1px solid var(--border); overflow:hidden; }
        table           { width:100%; border-collapse:collapse; font-size:14px; }
        th              { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:14px 18px; border-bottom:2px solid var(--border); text-align:left; }
        td              { padding:15px 18px; border-bottom:1px solid var(--border); color:#1a1a1a; vertical-align:middle; }
        tr:last-child td{ border-bottom:none; }
        tr:hover td     { background:#f8fafc; }

        /* ── BADGES ────────────────────────────────────────────────────── */
        .badge              { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .s-submitted        { background:#eef2ff; color:#4f46e5; }
        .s-under-review     { background:#fef3c7; color:#d97706; }
        .s-finance-review   { background:#e0f2fe; color:#0369a1; }
        .s-approved         { background:#ecfdf5; color:#059669; }
        .s-rejected         { background:#fef2f2; color:#dc2626; }

        /* ── ACTION BUTTON ─────────────────────────────────────────────── */
        .btn-view     { display:inline-flex; align-items:center; gap:6px; background:var(--navy); color:#fff; text-decoration:none; padding:7px 14px; border-radius:6px; font-weight:600; font-size:13px; border:none; cursor:pointer; transition:opacity 0.2s; }
        .btn-view:hover { opacity:0.85; }
        .btn-view svg   { width:14px; height:14px; }

        /* ── EMPTY STATE ───────────────────────────────────────────────── */
        .empty-cell { text-align:center; padding:50px; color:var(--muted); font-size:14px; }

        /* ── FINANCE SUMMARY STRIP ─────────────────────────────────────── */
        .finance-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
        .fin-strip-card { background:#fff; border-radius:10px; border:1px solid var(--border); padding:16px 20px; display:flex; align-items:center; gap:14px; }
        .fin-strip-icon { width:42px; height:42px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
        .fin-strip-label { font-size:11px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; }
        .fin-strip-val   { font-size:18px; font-weight:700; }

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
                <h1 class="page-title">Review Dashboard</h1>
            </div>

            <?php if ($decision === 'success'): ?>
                <?php if ($decision_act === 'Verified'): ?>
                    <div class="alert alert-success">&#10003; Invoice approved and routed to Finance Review. Vendor has been notified.</div>
                <?php elseif ($decision_act === 'Approved'): ?>
                    <div class="alert alert-success">&#10003; Invoice fully approved. Payment processing has been initiated.</div>
                <?php elseif ($decision_act === 'Rejected'): ?>
                    <div class="alert alert-danger">&#10007; Invoice rejected. Reason saved and vendor notified.</div>
                <?php elseif ($decision_act === 'UnderReview'): ?>
                    <div class="alert alert-warning">&#8635; Additional information requested. Invoice set to Under Review.</div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-danger">&#9888; <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <!-- ── SDD_CLS_401: filterClaims() stat cards ──────────────────── -->
            <div class="stats-row">
                <?php
                $cards = [
                    ['label'=>'Total Claims',   'val'=>$stats['total'],     'key'=>'all',           'color'=>'#002D62'],
                    ['label'=>'Submitted',      'val'=>$stats['submitted'], 'key'=>'Submitted',     'color'=>'#4f46e5'],
                    ['label'=>'Under Review',   'val'=>$stats['reviewing'], 'key'=>'Under Review',  'color'=>'#d97706'],
                    ['label'=>'Finance Review', 'val'=>$stats['finance'],   'key'=>'Finance Review','color'=>'#0369a1'],
                    ['label'=>'Approved',       'val'=>$stats['approved'],  'key'=>'Approved',      'color'=>'#059669'],
                    ['label'=>'Rejected',       'val'=>$stats['rejected'],  'key'=>'Rejected',      'color'=>'#dc2626'],
                ];
                foreach ($cards as $c):
                    $active = ($filterStatus === $c['key']) ? 'active-filter' : '';
                ?>
                <a href="review_dashboard.php?status=<?php echo urlencode($c['key']); ?>" class="stat-card <?php echo $active; ?>">
                    <div class="stat-label"><?php echo $c['label']; ?></div>
                    <div class="stat-number" style="color:<?php echo $c['color']; ?>"><?php echo $c['val']; ?></div>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- ── Pending Invoices table ──────────────────────────────────── -->
            <div class="table-card">
                <div class="section-title">
                    &#128196; Pending Invoices
                    <?php if ($filterStatus !== 'all'): ?>
                        — Filtered: <?php echo htmlspecialchars($filterStatus); ?>
                        &nbsp;<a href="review_dashboard.php" style="color:#93c5fd;font-size:12px;font-weight:500;">Clear filter</a>
                    <?php endif; ?>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Invoice Number</th>
                            <th>DO Number</th>
                            <th>Supplier</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $slug = 's-' . strtolower(str_replace(' ', '-', $row['invoice_status']));
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['invoice_num']); ?></strong></td>
                            <td><code style="font-size:12px;color:#718096;"><?php echo htmlspecialchars($row['DO_ID']); ?></code></td>
                            <td><?php echo htmlspecialchars($row['supplier_name'] ?? '—'); ?></td>
                            <td><strong>RM <?php echo number_format($row['total'], 2); ?></strong></td>
                            <td style="font-size:13px;color:#718096;"><?php echo date('d M Y', strtotime($row['invoice_date'])); ?></td>
                            <td><span class="badge <?php echo $slug; ?>"><?php echo htmlspecialchars($row['invoice_status']); ?></span></td>
                            <td style="text-align:center;">
                                <a href="review_workspace.php?id=<?php echo $row['invoice_ID']; ?>" class="btn-view">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                    View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-cell">&#128203; No invoices found for this filter.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="system-footer">© <?php echo date('Y'); ?> Keretapi Tanah Melayu Berhad &nbsp;|&nbsp; KTM eDOIS Internal Review Module</div>

        </div><!-- /content-body -->
    </div><!-- /lower-split-container -->
</div><!-- /app-layout-wrapper -->
</body>
</html>
<?php $conn->close(); ?>
