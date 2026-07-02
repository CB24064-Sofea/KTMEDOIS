<?php
// =========================================================================
// FILE        : do_details.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : doInspectionDetailUI — used by Procurement Officer / Staff
// DESCRIPTION : Full detail view of a single Delivery Order for physical
//               inspection. Shows DO/PO/supplier/customer info, any linked
//               invoice claims, and lets the officer record an inspection
//               decision (Approved / Cancelled) which is persisted via
//               DOReviewController.inspectDO().
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/DOReviewController.php";

// ── Session guard ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['staff_auth']) && !isset($_SESSION['vendor_auth'])) {
    header("Location: " . app_url('m1/login.php')); exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: do_list.php"); exit;
}

$staffId    = $_SESSION['staff_auth']['staff_id'] ?? $_SESSION['staff_auth']['staff_ID'] ?? 'STF001';
$controller = new DOReviewController($conn, $staffId);
$doId       = trim($_GET['id']);

// ── Handle inspection decision POST ───────────────────────────────────────────
$message = '';
$msg_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decision'])) {
    $decision = trim($_POST['decision']);
    $result   = $controller->inspectDO($doId, $decision);
    $message  = $result['message'];
    $msg_type = $result['success'] ? 'success' : 'danger';
}

$doData   = $controller->getDeliveryOrderById($doId);
if (!$doData) {
    header("Location: do_list.php"); exit;
}
$invoices = $controller->getInvoicesForDO($doId);
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS – DO Inspection Detail</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/KTMEDOIS-main/sidebar.css">
    <style>
        .app-layout-wrapper    { display:flex; flex-direction:column; width:100%; height:100vh; background:#f3f5f9; }
        .lower-split-container { display:flex; flex-grow:1; overflow:hidden; }
        .content-body          { flex-grow:1; padding:36px; overflow-y:auto; font-family:'Inter',sans-serif; color:#333; }

        :root { --navy:#002D62; --border:#e2e8f0; --muted:#718096; }
        .page-header  { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .page-title   { font-size:26px; font-weight:700; color:var(--navy); }

        .btn-back-link { display:inline-flex; align-items:center; gap:7px; background:#f1f5f9; color:#4a5568; text-decoration:none; padding:9px 18px; border-radius:7px; font-weight:600; font-size:13px; border:1px solid #cbd5e1; margin-bottom:24px; transition:background 0.2s; }
        .btn-back-link:hover { background:#e2e8f0; }

        .alert         { padding:13px 18px; border-radius:8px; font-size:14px; font-weight:500; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .alert-danger  { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }

        .review-grid { display:grid; grid-template-columns:1.6fr 1fr; gap:24px; align-items:start; }

        .card        { background:#fff; border-radius:10px; border:1px solid var(--border); padding:26px; margin-bottom:20px; }
        .card-title  { font-size:15px; font-weight:700; color:#1a1a1a; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:8px; }

        .info-row    { display:flex; margin-bottom:12px; font-size:14px; }
        .info-label  { width:165px; font-weight:600; color:#555; flex-shrink:0; }
        .info-value  { color:#1a1a1a; }

        .badge            { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .p-pending        { background:#fef3c7; color:#d97706; }
        .p-approved       { background:#ecfdf5; color:#059669; }
        .p-cancelled      { background:#fef2f2; color:#dc2626; }
        .p-completed      { background:#e0f2fe; color:#0369a1; }
        .s-submitted        { background:#eef2ff; color:#4f46e5; }
        .s-under-review     { background:#fef3c7; color:#d97706; }
        .s-finance-review   { background:#e0f2fe; color:#0369a1; }
        .s-approved         { background:#ecfdf5; color:#059669; }
        .s-rejected         { background:#fef2f2; color:#dc2626; }

        .doc-row     { display:flex; align-items:center; justify-content:space-between; padding:11px 14px; background:#f8fafc; border-radius:7px; border:1px solid var(--border); font-size:14px; margin-bottom:10px; }
        .btn-view-doc{ background:var(--navy); color:#fff; padding:7px 14px; border-radius:6px; text-decoration:none; font-size:13px; font-weight:600; }

        table  { width:100%; border-collapse:collapse; font-size:14px; }
        th     { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:11px 14px; border-bottom:2px solid var(--border); text-align:left; }
        td     { padding:11px 14px; border-bottom:1px solid var(--border); }
        tr:last-child td { border-bottom:none; }

        /* ── DECISION PANEL ────────────────────────────────────────────── */
        .decision-card { background:#fff; border-radius:10px; border:1px solid var(--border); padding:26px; position:sticky; top:20px; }
        .btn-approve { display:block; width:100%; background:#16a34a; color:#fff; border:none; padding:13px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; margin-bottom:10px; transition:opacity 0.2s; }
        .btn-approve:hover { opacity:0.88; }
        .btn-reject  { display:block; width:100%; background:#dc2626; color:#fff; border:none; padding:13px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; margin-bottom:10px; transition:opacity 0.2s; }
        .btn-reject:hover  { opacity:0.88; }
        .btn-cancel  { display:block; width:100%; background:#fff; color:#4a5568; border:1px solid #cbd5e0; padding:12px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; text-decoration:none; text-align:center; transition:background 0.2s; }
        .btn-cancel:hover  { background:#f7fafc; }
        .already-done { background:#f8fafc; border-radius:8px; padding:16px; text-align:center; font-size:14px; color:#4a5568; }

        .empty-note { font-size:13px; color:var(--muted); padding:14px 0; }
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
                <h1 class="page-title">DO Inspection Detail</h1>
            </div>

            <a href="do_list.php" class="btn-back-link">&#8592; Back to DO List</a>

            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $msg_type; ?>">
                <?php echo $msg_type === 'success' ? '&#10003;' : '&#9888;'; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div class="review-grid">

                <!-- ── LEFT: DO details ─────────────────────────────────── -->
                <div>
                    <div class="card">
                        <div class="card-title">&#128230; Delivery Order Details</div>
                        <div class="info-row"><span class="info-label">DO Number</span><span class="info-value"><strong><?php echo htmlspecialchars($doData['DO_ID']); ?></strong></span></div>
                        <div class="info-row"><span class="info-label">PO Reference</span><span class="info-value"><code><?php echo htmlspecialchars($doData['PO_number']); ?></code></span></div>
                        <div class="info-row"><span class="info-label">PO Amount</span><span class="info-value">RM <?php echo number_format($doData['PO_amount'] ?? 0, 2); ?></span></div>
                        <div class="info-row"><span class="info-label">Supplier</span><span class="info-value"><?php echo htmlspecialchars($doData['supplier_name']); ?></span></div>
                        <div class="info-row"><span class="info-label">Supplier Contact</span><span class="info-value"><?php echo htmlspecialchars($doData['supplier_email'] ?? '—'); ?> &middot; <?php echo htmlspecialchars($doData['supplier_phone'] ?? '—'); ?></span></div>
                        <div class="info-row"><span class="info-label">Customer</span><span class="info-value"><?php echo htmlspecialchars($doData['customer_name'] ?? '—'); ?></span></div>
                        <div class="info-row"><span class="info-label">Project Reference</span><span class="info-value"><?php echo htmlspecialchars($doData['project_reference'] ?? '—'); ?></span></div>
                        <div class="info-row"><span class="info-label">Created Date</span><span class="info-value"><?php echo date('d M Y, H:i', strtotime($doData['created_date'])); ?></span></div>
                        <div class="info-row">
                            <span class="info-label">Inspection Status</span>
                            <span class="info-value">
                                <?php $slug = 'p-' . strtolower($doData['PO_status']); ?>
                                <span class="badge <?php echo $slug; ?>"><?php echo htmlspecialchars($doData['PO_status']); ?></span>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($doData['proof_of_delivery'])): ?>
                    <div class="card">
                        <div class="card-title">&#128196; Proof of Delivery</div>
                        <div class="doc-row">
                            <span>&#128196; <?php echo htmlspecialchars(basename($doData['proof_of_delivery'])); ?></span>
                            <a href="/KTMEDOIS-main/<?php echo htmlspecialchars($doData['proof_of_delivery']); ?>" target="_blank" class="btn-view-doc">&#128065; View</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-title">&#128196; Linked Invoice Claims</div>
                        <?php if (!empty($invoices)): ?>
                        <table>
                            <thead><tr><th>Invoice No.</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($invoices as $inv):
                                $islug = 's-' . strtolower(str_replace(' ','-',$inv['invoice_status']));
                            ?>
                            <tr>
                                <td><a href="review_workspace.php?id=<?php echo $inv['invoice_ID']; ?>" style="color:var(--navy);font-weight:600;text-decoration:none;"><?php echo htmlspecialchars($inv['invoice_num']); ?></a></td>
                                <td>RM <?php echo number_format($inv['total'],2); ?></td>
                                <td><span class="badge <?php echo $islug; ?>"><?php echo htmlspecialchars($inv['invoice_status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                            <p class="empty-note">No invoice claims have been submitted against this DO yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ── RIGHT: Inspection decision panel ────────────────────── -->
                <div>
                    <div class="decision-card">
                        <div class="card-title">&#10003; Inspection Decision</div>

                        <?php if ($doData['PO_status'] === 'Pending'): ?>
                            <form method="POST" action="do_details.php?id=<?php echo urlencode($doId); ?>">
                                <button type="submit" name="decision" value="Approved" class="btn-approve">&#10003; Approve Delivery Order</button>
                                <button type="submit" name="decision" value="Cancelled" class="btn-reject">&#10007; Cancel / Flag Issue</button>
                            </form>
                            <a href="do_list.php" class="btn-cancel" style="margin-top:6px;">Back to List</a>
                        <?php else: ?>
                            <div class="already-done">
                                This delivery order has already been marked
                                <strong><?php echo htmlspecialchars($doData['PO_status']); ?></strong>.
                            </div>
                            <br>
                            <a href="do_list.php" class="btn-cancel">Back to List</a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="system-footer">© <?php echo date('Y'); ?> Keretapi Tanah Melayu Berhad &nbsp;|&nbsp; KTM eDOIS Internal Review Module</div>

        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
