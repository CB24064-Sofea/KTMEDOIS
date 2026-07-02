<?php
// =========================================================================
// FILE        : forward_to_finance.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : ForwardToFinanceUI — used by Officer
// DESCRIPTION : Dedicated boundary page for the KTM Officer to forward
//               verified invoices straight to the Finance Review queue —
//               reachable directly from the sidebar without detouring
//               through the review dashboard first.
//               Reuses ReviewAndApprovalController.updateStatus('Verified')
//               so the same business rules, audit logging, and vendor
//               notification flow used elsewhere in M04 are applied here.
//               - listForwardableInvoices() → queries Submitted / Under
//                 Review invoices that are awaiting an officer decision
//               - forwardToFinance()        → controller->updateStatus()
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/ReviewAndApprovalController.php";

// ── Session guard ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['staff_auth'])) {
    header("Location: " . app_url('m1/staff_login.php')); exit;
}

$staffId   = $_SESSION['staff_auth']['staff_id'] ?? $_SESSION['staff_auth']['staff_ID'] ?? 'STF001';
$staffName = htmlspecialchars($_SESSION['staff_auth']['name'] ?? 'Officer');

$controller   = new ReviewAndApprovalController($conn, $staffId);
$current_page = basename($_SERVER['PHP_SELF']);

$decision     = isset($_GET['decision']) ? $_GET['decision'] : '';

// ── listForwardableInvoices() — invoices an Officer can act on ────────────────
// Only 'Submitted' and 'Under Review' invoices are eligible: once forwarded,
// the invoice moves to 'Finance Review' and drops out of this list.
$result = $conn->query(
    "SELECT i.invoice_ID, i.invoice_num, i.DO_ID, i.total, i.invoice_date,
            i.invoice_status, s.supplier_name
     FROM invoice i
     INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
     INNER JOIN supplier s ON d.user_ID = s.supplier_ID
     WHERE i.invoice_status IN ('Submitted','Under Review')
     ORDER BY i.invoice_date ASC"
);

$pendingCount = $result ? $result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS – Forward Invoice to Finance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/KTMEDOIS-main/sidebar.css">
    <style>
        .app-layout-wrapper    { display:flex; flex-direction:column; width:100%; height:100vh; background:#f3f5f9; }
        .lower-split-container { display:flex; flex-grow:1; overflow:hidden; }
        .content-body          { flex-grow:1; padding:36px; overflow-y:auto; font-family:'Inter',sans-serif; color:#333; }

        :root { --navy:#002D62; --border:#e2e8f0; --muted:#718096; }
        .page-header  { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
        .page-title   { font-size:26px; font-weight:700; color:var(--navy); }
        .page-sub     { font-size:13.5px; color:var(--muted); margin-bottom:24px; }

        .alert         { padding:13px 18px; border-radius:8px; font-size:14px; font-weight:500; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .alert-danger  { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }

        .stats-row   { display:grid; grid-template-columns:1fr; max-width:220px; gap:14px; margin-bottom:24px; }
        .stat-card   { background:#fff; border-radius:10px; border:1px solid var(--border); padding:18px; }
        .stat-label  { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.6px; margin-bottom:8px; font-weight:600; }
        .stat-number { font-size:28px; font-weight:700; color:#d97706; }

        .table-card  { background:#fff; border-radius:10px; border:1px solid var(--border); overflow:hidden; }
        .section-title { background:var(--navy); color:#fff; padding:14px 20px; border-radius:8px 8px 0 0; font-size:14px; font-weight:700; letter-spacing:0.3px; }
        table  { width:100%; border-collapse:collapse; font-size:14px; }
        th     { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:14px 18px; border-bottom:2px solid var(--border); text-align:left; }
        td     { padding:15px 18px; border-bottom:1px solid var(--border); color:#1a1a1a; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#f8fafc; }

        .badge          { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .s-submitted    { background:#eef2ff; color:#4f46e5; }
        .s-under-review { background:#fef3c7; color:#d97706; }

        .btn-forward  { display:inline-flex; align-items:center; gap:6px; background:var(--navy); color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; transition:opacity 0.2s; }
        .btn-forward:hover { opacity:0.85; }
        .btn-inspect  { display:inline-flex; align-items:center; gap:6px; background:#f1f5f9; color:#4a5568; text-decoration:none; border:1px solid var(--border); padding:8px 14px; border-radius:6px; font-weight:600; font-size:13px; margin-right:6px; }
        .btn-inspect:hover { background:#e2e8f0; }

        .empty-cell { text-align:center; padding:50px; color:var(--muted); font-size:14px; }

        /* ── CONFIRMATION MODAL ───────────────────────────────────────── */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; z-index:9999; opacity:0; pointer-events:none; transition:opacity 0.25s; }
        .modal-overlay.active { opacity:1; pointer-events:auto; }
        .modal-box     { background:#EAEAEA; width:430px; max-width:90vw; padding:32px 28px; border-radius:22px; box-shadow:0 10px 30px rgba(0,0,0,0.18); text-align:center; transform:scale(0.85); transition:transform 0.25s; }
        .modal-overlay.active .modal-box { transform:scale(1); }
        .modal-icon    { width:56px; height:56px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:26px; color:#fff; margin-bottom:14px; background:var(--navy); }
        .modal-title   { font-size:19px; font-weight:700; color:#000; margin-bottom:8px; }
        .modal-sub     { font-size:14px; color:#4a5568; margin-bottom:16px; line-height:1.5; }
        .modal-box textarea { width:100%; padding:10px 12px; border:1px solid #cbd5e0; border-radius:7px; font-size:13.5px; background:#fff; resize:vertical; min-height:70px; font-family:'Inter',sans-serif; outline:none; margin-bottom:18px; }
        .modal-btns    { display:flex; gap:12px; justify-content:center; }
        .btn-modal-yes { background:#1e1e1e; color:#fff; border:none; padding:11px 26px; border-radius:9px; font-weight:700; font-size:14px; cursor:pointer; }
        .btn-modal-no  { background:#D1CDCD; color:#000; border:1px solid #A6A2A2; padding:11px 22px; border-radius:9px; font-weight:600; font-size:14px; cursor:pointer; }

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
                <h1 class="page-title">Forward Invoice to Finance</h1>
            </div>
            <div class="page-sub">Officer, <?php echo $staffName; ?> — approve reviewed invoices to route them into the Finance Review queue.</div>

            <?php if ($decision === 'success'): ?>
                <div class="alert alert-success">&#10003; Invoice approved and forwarded to Finance Review. Vendor has been notified.</div>
            <?php endif; ?>
            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-danger">&#9888; <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-label">Awaiting Forward</div>
                    <div class="stat-number"><?php echo $pendingCount; ?></div>
                </div>
            </div>

            <div class="table-card">
                <div class="section-title">&#128228; Invoices Ready to Forward</div>
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
                            <td style="text-align:center;white-space:nowrap;">
                                <a href="review_workspace.php?id=<?php echo (int)$row['invoice_ID']; ?>" class="btn-inspect">&#128065; Inspect</a>
                                <button type="button" class="btn-forward"
                                        onclick="openForwardModal(<?php echo (int)$row['invoice_ID']; ?>, '<?php echo htmlspecialchars($row['invoice_num'], ENT_QUOTES); ?>')">
                                    &#10003; Forward
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-cell">&#128203; No invoices are currently awaiting forwarding to Finance.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="system-footer">© <?php echo date('Y'); ?> Keretapi Tanah Melayu Berhad &nbsp;|&nbsp; KTM eDOIS Internal Review Module</div>

            <!-- ── Confirmation Modal ─────────────────────────────────────── -->
            <div class="modal-overlay" id="confirmModal">
                <div class="modal-box">
                    <div class="modal-icon">&#128228;</div>
                    <div class="modal-title" id="modalTitle">Forward to Finance</div>
                    <div class="modal-sub" id="modalSub">Forward this invoice to the Finance Review queue?</div>
                    <textarea id="remarksInput" placeholder="Optional note for the Finance team..."></textarea>
                    <div class="modal-btns">
                        <button class="btn-modal-yes" id="modalYesBtn">Yes, Forward</button>
                        <button class="btn-modal-no" onclick="closeModal()">Cancel</button>
                    </div>
                </div>
            </div>

            <!-- Hidden form — POST to review_decision.php after modal confirmation -->
            <form id="decisionForm" method="POST" action="review_decision.php" style="display:none;">
                <input type="hidden" name="invoice_id"    id="invoiceIdInput">
                <input type="hidden" name="action"        value="Verified">
                <input type="hidden" name="review_remarks" id="remarksHidden">
                <input type="hidden" name="redirect_to"    value="forward_to_finance.php">
            </form>

        </div>
    </div>
</div>

<script>
function openForwardModal(invoiceId, invoiceNum) {
    document.getElementById('invoiceIdInput').value = invoiceId;
    document.getElementById('modalSub').textContent = 'Forward invoice ' + invoiceNum + ' to the Finance Review queue?';
    document.getElementById('remarksInput').value = '';
    document.getElementById('confirmModal').classList.add('active');
}

function closeModal() {
    document.getElementById('confirmModal').classList.remove('active');
}

document.getElementById('modalYesBtn').addEventListener('click', function() {
    document.getElementById('remarksHidden').value = document.getElementById('remarksInput').value.trim();
    closeModal();
    document.getElementById('decisionForm').submit();
});

document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
<?php $conn->close(); ?>
