<?php
// =========================================================================
// FILE        : review_dashboard.php
// SDD CLASS   : SDD_CLS_401 — officerMainDashboardUI
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/ReviewAndApprovalController.php";

$controller      = new ReviewAndApprovalController($conn);
$filterStatus    = isset($_GET['status']) ? $_GET['status'] : 'all';
$stats           = $controller->getDashboardStats();
$result          = $controller->fetchPendingClaims($filterStatus);
$decision        = isset($_GET['decision']) ? $_GET['decision'] : '';
$decision_action = isset($_GET['action'])   ? $_GET['action']   : '';
$current_page    = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS - Review Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <style>
        /* CRITICAL: these layout classes must be defined here — NOT in sidebar.css */
        .app-layout-wrapper     { display:flex; flex-direction:column; width:100%; height:100vh; background:#f3f5f9; }
        .lower-split-container  { display:flex; flex-grow:1; overflow:hidden; }
        .content-body           { flex-grow:1; padding:36px; overflow-y:auto; font-family:'Inter',sans-serif; color:#333; }

        :root { --navy:#002D62; --border:#e2e8f0; --muted:#718096; }
        .page-header  { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-title   { font-size:26px; font-weight:700; color:var(--navy); }
        .ktmb-logo    { height:46px; width:auto; }

        .alert         { padding:13px 18px; border-radius:8px; font-size:14px; font-weight:500; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .alert-danger  { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .alert-warning { background:#fffbeb; color:#92400e; border:1px solid #fde68a; }

        .stats-row      { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:28px; }
        .stat-card      { background:#fff; border-radius:10px; border:1px solid var(--border); padding:18px; text-decoration:none; display:block; transition:box-shadow 0.2s; color:inherit; }
        .stat-card:hover{ box-shadow:0 4px 14px rgba(0,0,0,0.08); }
        .stat-card.active-filter { border-color:var(--navy); box-shadow:0 0 0 2px rgba(0,45,98,0.15); }
        .stat-label     { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.6px; margin-bottom:8px; font-weight:600; }
        .stat-number    { font-size:28px; font-weight:700; }

        .table-card     { background:#fff; border-radius:10px; border:1px solid var(--border); overflow:hidden; }
        table           { width:100%; border-collapse:collapse; font-size:14px; }
        th              { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:14px 18px; border-bottom:2px solid var(--border); text-align:left; }
        td              { padding:15px 18px; border-bottom:1px solid var(--border); color:#1a1a1a; vertical-align:middle; }
        tr:last-child td{ border-bottom:none; }
        tr:hover td     { background:#f8fafc; }

        .badge              { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .s-submitted        { background:#eef2ff; color:#4f46e5; }
        .s-under-review     { background:#fef3c7; color:#d97706; }
        .s-finance-review   { background:#e0f2fe; color:#0369a1; }
        .s-approved         { background:#ecfdf5; color:#059669; }
        .s-rejected         { background:#fef2f2; color:#dc2626; }

        .btn-review         { display:inline-block; background:#f1f5f9; color:var(--navy); text-decoration:none; padding:7px 14px; border-radius:6px; font-weight:600; font-size:13px; border:1px solid #cbd5e1; transition:all 0.2s; }
        .btn-review:hover   { background:var(--navy); color:#fff; }
        .empty-cell         { text-align:center; padding:40px; color:var(--muted); font-size:14px; }
        .system-footer      { text-align:center; font-size:11px; color:#a0aec0; padding-top:32px; letter-spacing:1px; }
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
                <?php if ($decision_action === 'Verified'): ?>
                    <div class="alert alert-success">&#10003; Invoice approved and routed to Finance Review. Vendor has been notified.</div>
                <?php elseif ($decision_action === 'Rejected'): ?>
                    <div class="alert alert-danger">&#10007; Invoice rejected. Reason saved and vendor notified.</div>
                <?php elseif ($decision_action === 'UnderReview'): ?>
                    <div class="alert alert-warning">&#8635; Additional information requested. Invoice set to Under Review.</div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- SDD_CLS_401: filterClaims() stat cards -->
            <div class="stats-row">
                <?php
                $cards = [
                    ['label'=>'Total Claims',   'val'=>$stats['total'],     'key'=>'all',           'color'=>'#002D62'],
                    ['label'=>'Submitted',      'val'=>$stats['submitted'], 'key'=>'Submitted',     'color'=>'#4f46e5'],
                    ['label'=>'Under Review',   'val'=>$stats['reviewing'], 'key'=>'Under Review',  'color'=>'#d97706'],
                    ['label'=>'Finance Review', 'val'=>$stats['finance'],   'key'=>'Finance Review','color'=>'#0369a1'],
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

            <!-- SDD_CLS_401: selectInvoice() opens reviewSubmissionUI -->
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice No.</th>
                            <th>DO Reference</th>
                            <th>Claim Amount</th>
                            <th>Invoice Date</th>
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
                            <td><code><?php echo htmlspecialchars($row['DO_ID']); ?></code></td>
                            <td><strong>MYR <?php echo number_format($row['total'], 2); ?></strong></td>
                            <td><?php echo date('d M Y', strtotime($row['invoice_date'])); ?></td>
                            <td><span class="badge <?php echo $slug; ?>"><?php echo htmlspecialchars($row['invoice_status']); ?></span></td>
                            <td style="text-align:center;">
                                <a href="review_workspace.php?id=<?php echo $row['invoice_ID']; ?>" class="btn-review">Review</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="empty-cell">No invoices found for this filter.</td></tr>
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
