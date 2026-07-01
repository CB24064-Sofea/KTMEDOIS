<?php
// =========================================================================
// FILE        : review_history.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : ReviewHistoryUI — used by Officer, Finance
// DESCRIPTION : View previous review history for all invoices. Shows a
//               complete per-invoice audit trail grouped by invoice.
//               Officers and Finance staff can search by invoice number.
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/AuditController.php";

// ── Session guard ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['staff_auth'])) {
    header("Location: " . app_url('m1/staff_login.php')); exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$auditCtrl = new AuditController($conn);

// ── Fetch grouped review history ──────────────────────────────────────────────
if (!empty($search)) {
    $safe = $conn->real_escape_string($search);
    $sql = "SELECT i.invoice_ID, i.invoice_num, i.invoice_status, i.total, i.invoice_date,
                   COUNT(a.log_ID) as review_count,
                   MAX(a.timestamp) as last_action,
                   s.supplier_name
            FROM invoice i
            INNER JOIN audit_log a ON i.invoice_ID = a.invoice_ID
            INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
            INNER JOIN supplier s ON d.supplier_ID = s.supplier_ID
            WHERE i.invoice_num LIKE '%$safe%' OR s.supplier_name LIKE '%$safe%'
            GROUP BY i.invoice_ID
            ORDER BY last_action DESC";
} else {
    $sql = "SELECT i.invoice_ID, i.invoice_num, i.invoice_status, i.total, i.invoice_date,
                   COUNT(a.log_ID) as review_count,
                   MAX(a.timestamp) as last_action,
                   s.supplier_name
            FROM invoice i
            INNER JOIN audit_log a ON i.invoice_ID = a.invoice_ID
            INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
            INNER JOIN supplier s ON d.supplier_ID = s.supplier_ID
            GROUP BY i.invoice_ID
            ORDER BY last_action DESC";
}
$invoices = $conn->query($sql);
$invCount = $invoices ? $invoices->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS – Review History</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <style>
        .app-layout-wrapper    { display:flex; flex-direction:column; width:100%; height:100vh; background:#f3f5f9; }
        .lower-split-container { display:flex; flex-grow:1; overflow:hidden; }
        .content-body          { flex-grow:1; padding:36px; overflow-y:auto; font-family:'Inter',sans-serif; color:#333; }

        :root { --navy:#002D62; --border:#e2e8f0; --muted:#718096; }
        .page-header  { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-title   { font-size:26px; font-weight:700; color:var(--navy); }

        .toolbar      { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; gap:12px; flex-wrap:wrap; }
        .search-form  { display:flex; gap:8px; }
        .search-input { flex:1; padding:10px 14px; border-radius:7px; border:1px solid var(--border); font-size:14px; outline:none; font-family:'Inter',sans-serif; width:300px; }
        .search-input:focus { border-color:var(--navy); }
        .btn-search   { background:var(--navy); color:#fff; border:none; padding:10px 20px; border-radius:7px; font-weight:600; font-size:14px; cursor:pointer; }
        .btn-clear    { background:#f1f5f9; color:#4a5568; border:1px solid var(--border); padding:10px 14px; border-radius:7px; font-weight:600; font-size:14px; text-decoration:none; display:inline-flex; align-items:center; }
        .result-count { font-size:13px; color:var(--muted); }

        /* ── ACCORDION INVOICE CARDS ────────────────────────────────────── */
        .inv-card     { background:#fff; border-radius:10px; border:1px solid var(--border); margin-bottom:10px; overflow:hidden; transition:box-shadow 0.2s; }
        .inv-card:hover { box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .inv-header   { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; cursor:pointer; transition:background 0.15s; user-select:none; }
        .inv-header:hover { background:#f8fafc; }
        .inv-left     { display:flex; align-items:center; gap:14px; }
        .inv-num      { font-size:15px; font-weight:700; color:var(--navy); }
        .inv-meta     { font-size:13px; color:var(--muted); margin-top:2px; }
        .inv-right    { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

        .badge              { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .s-submitted        { background:#eef2ff; color:#4f46e5; }
        .s-under-review     { background:#fef3c7; color:#d97706; }
        .s-finance-review   { background:#e0f2fe; color:#0369a1; }
        .s-approved         { background:#ecfdf5; color:#059669; }
        .s-rejected         { background:#fef2f2; color:#dc2626; }
        .count-badge        { background:#f1f5f9; color:#4a5568; font-size:12px; font-weight:600; padding:4px 10px; border-radius:50px; }
        .chevron            { font-size:14px; color:var(--muted); transition:transform 0.2s; display:inline-block; }
        .chevron.open       { transform:rotate(180deg); }

        .btn-view    { display:inline-block; background:#f1f5f9; color:var(--navy); text-decoration:none; padding:6px 13px; border-radius:6px; font-weight:600; font-size:12px; border:1px solid var(--border); transition:all 0.2s; }
        .btn-view:hover { background:var(--navy); color:#fff; }

        /* ── TIMELINE ───────────────────────────────────────────────────── */
        .inv-body       { display:none; border-top:1px solid var(--border); padding:20px 24px; }
        .inv-body.open  { display:block; }
        .timeline       { padding-left:20px; border-left:2px solid var(--border); }
        .tl-item        { position:relative; margin-bottom:18px; }
        .tl-dot         { position:absolute; left:-24px; top:4px; width:10px; height:10px; border-radius:50%; }
        .dot-verified   { background:#16a34a; }
        .dot-approved   { background:#065f46; }
        .dot-rejected   { background:#dc2626; }
        .dot-underreview{ background:#d97706; }
        .dot-assigned   { background:#0369a1; }
        .dot-default    { background:var(--navy); }
        .tl-action      { font-size:13px; font-weight:700; }
        .tl-meta        { font-size:12px; color:var(--muted); margin-top:2px; }
        .tl-record      { font-size:11px; color:#a0aec0; }

        .empty-state    { text-align:center; padding:60px; color:var(--muted); font-size:14px; }
        .system-footer  { text-align:center; font-size:11px; color:#a0aec0; padding-top:32px; letter-spacing:1px; }
    </style>
</head>
<body>
<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>
    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>
        <div class="content-body">

            <div class="page-header">
                <h1 class="page-title">&#128203; Review History</h1>
            </div>

            <div class="toolbar">
                <form method="GET" action="review_history.php" class="search-form">
                    <input type="text" name="search" class="search-input"
                           placeholder="Search by Invoice Number or Supplier..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-search">&#128269; Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="review_history.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>
                <div class="result-count">
                    <?php echo $invCount; ?> invoice(s) with review history
                    <?php if (!empty($search)): ?>
                        &nbsp;matching &ldquo;<?php echo htmlspecialchars($search); ?>&rdquo;
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($invoices && $invoices->num_rows > 0):
                while ($inv = $invoices->fetch_assoc()):
                    $slug = 's-' . strtolower(str_replace(' ', '-', $inv['invoice_status']));

                    // Fetch audit entries for this invoice
                    $histStmt = $conn->prepare(
                        "SELECT a.action, a.record_ID, a.timestamp, k.staff_name
                         FROM audit_log a
                         INNER JOIN ktmb_staff k ON a.staff_ID = k.staff_ID
                         WHERE a.invoice_ID = ?
                         ORDER BY a.timestamp DESC"
                    );
                    $histStmt->bind_param("i", $inv['invoice_ID']);
                    $histStmt->execute();
                    $entries = $histStmt->get_result();
                    $histStmt->close();
            ?>
            <div class="inv-card">
                <div class="inv-header" onclick="toggleHistory(<?php echo $inv['invoice_ID']; ?>)">
                    <div class="inv-left">
                        <div>
                            <div class="inv-num"><?php echo htmlspecialchars($inv['invoice_num']); ?></div>
                            <div class="inv-meta">
                                RM <?php echo number_format($inv['total'], 2); ?>
                                &middot; <?php echo htmlspecialchars($inv['supplier_name']); ?>
                                &middot; <?php echo date('d M Y', strtotime($inv['invoice_date'])); ?>
                            </div>
                        </div>
                    </div>
                    <div class="inv-right">
                        <span class="badge <?php echo $slug; ?>"><?php echo htmlspecialchars($inv['invoice_status']); ?></span>
                        <span class="count-badge"><?php echo $inv['review_count']; ?> action<?php echo $inv['review_count'] > 1 ? 's' : ''; ?></span>
                        <a href="review_workspace.php?id=<?php echo $inv['invoice_ID']; ?>" class="btn-view" onclick="event.stopPropagation();">View</a>
                        <span class="chevron" id="chevron-<?php echo $inv['invoice_ID']; ?>">&#9660;</span>
                    </div>
                </div>

                <div class="inv-body" id="body-<?php echo $inv['invoice_ID']; ?>">
                    <div class="timeline">
                        <?php if ($entries && $entries->num_rows > 0):
                            while ($log = $entries->fetch_assoc()):
                                $dotClass = 'dot-default';
                                $a = $log['action'];
                                if ($a === 'Verified')            $dotClass = 'dot-verified';
                                elseif ($a === 'Approved')        $dotClass = 'dot-approved';
                                elseif ($a === 'Rejected')        $dotClass = 'dot-rejected';
                                elseif ($a === 'UnderReview')     $dotClass = 'dot-underreview';
                                elseif ($a === 'AssignedReviewer')$dotClass = 'dot-assigned';
                        ?>
                        <div class="tl-item">
                            <div class="tl-dot <?php echo $dotClass; ?>"></div>
                            <div class="tl-action"><?php echo htmlspecialchars($log['action']); ?></div>
                            <div class="tl-meta">
                                By <strong><?php echo htmlspecialchars($log['staff_name']); ?></strong>
                                &middot; <?php echo date('d M Y, H:i', strtotime($log['timestamp'])); ?>
                            </div>
                            <div class="tl-record">TRX: <?php echo htmlspecialchars($log['record_ID']); ?></div>
                        </div>
                        <?php endwhile;
                        else: ?>
                            <p style="font-size:13px;color:#718096;">No history entries found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile;
            else: ?>
                <div class="empty-state">&#128203; No invoices with review history found.</div>
            <?php endif; ?>

            <div class="system-footer">© <?php echo date('Y'); ?> Keretapi Tanah Melayu Berhad &nbsp;|&nbsp; KTM eDOIS Internal Review Module</div>
        </div>
    </div>
</div>

<script>
function toggleHistory(id) {
    const body    = document.getElementById('body-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const isOpen  = body.classList.contains('open');

    // Collapse all others
    document.querySelectorAll('.inv-body').forEach(b => b.classList.remove('open'));
    document.querySelectorAll('.chevron').forEach(c => c.classList.remove('open'));

    if (!isOpen) {
        body.classList.add('open');
        chevron.classList.add('open');
    }
}
</script>
</body>
</html>
<?php $conn->close(); ?>
