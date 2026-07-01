<?php
// =========================================================================
// FILE        : approve_payment.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : processPaymentUI — used by Finance Officer
// DESCRIPTION : Dedicated boundary page for the Finance Officer to approve
//               the payment status of fully-approved invoices — reachable
//               directly from the sidebar without detouring through the
//               review dashboard first.
//               Reuses ReviewAndApprovalController.processPayment() so the
//               same business rules, audit logging, and vendor notification
//               flow used elsewhere in M04 are applied here.
//               - fetchPaymentQueue()  → InvoiceModel: Approved & unpaid invoices
//               - approvePaymentStatus() → controller->processPayment()
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/ReviewAndApprovalController.php";

// ── Session guard ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['staff_auth'])) {
    header("Location: " . app_url('m1/staff_login.php')); exit;
}

$staffId   = $_SESSION['staff_auth']['staff_id'] ?? $_SESSION['staff_auth']['staff_ID'] ?? 'STF001';
$staffName = htmlspecialchars($_SESSION['staff_auth']['name'] ?? 'Finance Officer');

$controller   = new ReviewAndApprovalController($conn, $staffId);
$current_page = basename($_SERVER['PHP_SELF']);
$decision     = isset($_GET['decision']) ? $_GET['decision'] : '';

// fetchPaymentQueue() — invoices fully Approved and awaiting/undergoing payment
$result  = $controller->fetchPaymentQueue();
$queueCount = $result ? $result->num_rows : 0;

// Finance summary strip — reuses InvoiceModel::getFinanceSummary()
$invoiceModel = new InvoiceModel($conn);
$summary      = $invoiceModel->getFinanceSummary();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS – Approve Payment Status</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
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

        /* ── FINANCE SUMMARY STRIP ─────────────────────────────────────── */
        .finance-strip   { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
        .fin-strip-card   { background:#fff; border-radius:10px; border:1px solid var(--border); padding:16px 20px; display:flex; align-items:center; gap:14px; }
        .fin-strip-icon   { width:42px; height:42px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
        .fin-strip-label  { font-size:11px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; }
        .fin-strip-val    { font-size:18px; font-weight:700; }

        .table-card  { background:#fff; border-radius:10px; border:1px solid var(--border); overflow:hidden; }
        .section-title { background:var(--navy); color:#fff; padding:14px 20px; border-radius:8px 8px 0 0; font-size:14px; font-weight:700; letter-spacing:0.3px; }
        table  { width:100%; border-collapse:collapse; font-size:14px; }
        th     { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:14px 18px; border-bottom:2px solid var(--border); text-align:left; }
        td     { padding:15px 18px; border-bottom:1px solid var(--border); color:#1a1a1a; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#f8fafc; }

        .badge          { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .pay-pending    { background:#f1f5f9; color:#718096; }
        .pay-processing { background:#fef3c7; color:#d97706; }
        .pay-paid       { background:#ecfdf5; color:#059669; }

        .btn-approve-pay { display:inline-flex; align-items:center; gap:6px; background:#0369a1; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; transition:opacity 0.2s; }
        .btn-approve-pay:hover { opacity:0.85; }
        .btn-inspect  { display:inline-flex; align-items:center; gap:6px; background:#f1f5f9; color:#4a5568; text-decoration:none; border:1px solid var(--border); padding:8px 14px; border-radius:6px; font-weight:600; font-size:13px; margin-right:6px; }
        .btn-inspect:hover { background:#e2e8f0; }

        .empty-cell { text-align:center; padding:50px; color:var(--muted); font-size:14px; }

        /* ── CONFIRMATION MODAL ───────────────────────────────────────── */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; z-index:9999; opacity:0; pointer-events:none; transition:opacity 0.25s; }
        .modal-overlay.active { opacity:1; pointer-events:auto; }
        .modal-box     { background:#EAEAEA; width:420px; max-width:90vw; padding:32px 28px; border-radius:22px; box-shadow:0 10px 30px rgba(0,0,0,0.18); text-align:center; transform:scale(0.85); transition:transform 0.25s; }
        .modal-overlay.active .modal-box { transform:scale(1); }
        .modal-icon    { width:56px; height:56px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:26px; color:#fff; margin-bottom:14px; background:#0369a1; }
        .modal-title   { font-size:19px; font-weight:700; color:#000; margin-bottom:8px; }
        .modal-sub     { font-size:14px; color:#4a5568; margin-bottom:22px; line-height:1.5; }
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
                <h1 class="page-title">Approve Payment Status</h1>
            </div>
            <div class="page-sub"><?php echo $staffName; ?> — mark fully-approved invoices as paid to complete disbursement.</div>

            <?php if ($decision === 'success'): ?>
                <div class="alert alert-success">&#10003; Payment marked as completed. Vendor has been notified.</div>
            <?php endif; ?>
            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-danger">&#9888; <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <!-- ── Finance Summary Strip ────────────────────────────────── -->
            <div class="finance-strip">
                <div class="fin-strip-card">
                    <div class="fin-strip-icon" style="background:#eef2ff;color:#4f46e5;">&#128176;</div>
                    <div>
                        <div class="fin-strip-label">Total Invoice Value</div>
                        <div class="fin-strip-val" style="color:var(--navy);">RM <?php echo number_format($summary['total_value'], 2); ?></div>
                    </div>
                </div>
                <div class="fin-strip-card">
                    <div class="fin-strip-icon" style="background:#ecfdf5;color:#059669;">&#9989;</div>
                    <div>
                        <div class="fin-strip-label">Total Paid</div>
                        <div class="fin-strip-val" style="color:#059669;">RM <?php echo number_format($summary['paid_value'], 2); ?></div>
                    </div>
                </div>
                <div class="fin-strip-card">
                    <div class="fin-strip-icon" style="background:#fef3c7;color:#d97706;">&#8987;</div>
                    <div>
                        <div class="fin-strip-label">Awaiting Payment (<?php echo $queueCount; ?>)</div>
                        <div class="fin-strip-val" style="color:#d97706;">RM <?php echo number_format($summary['pending_value'], 2); ?></div>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <div class="section-title">&#128181; Approved Invoices Awaiting Payment</div>
                <table>
                    <thead>
                        <tr>
                            <th>Invoice Number</th>
                            <th>DO Number</th>
                            <th>Supplier</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Payment Status</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $paySlug = 'pay-' . strtolower($row['payment_status'] ?? 'pending');
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['invoice_num']); ?></strong></td>
                            <td><code style="font-size:12px;color:#718096;"><?php echo htmlspecialchars($row['DO_ID']); ?></code></td>
                            <td><?php echo htmlspecialchars($row['supplier_name'] ?? '—'); ?></td>
                            <td><strong>RM <?php echo number_format($row['total'], 2); ?></strong></td>
                            <td style="font-size:13px;color:#718096;"><?php echo date('d M Y', strtotime($row['invoice_date'])); ?></td>
                            <td><span class="badge <?php echo $paySlug; ?>"><?php echo htmlspecialchars($row['payment_status'] ?? 'Pending'); ?></span></td>
                            <td style="text-align:center;white-space:nowrap;">
                                <a href="review_workspace.php?id=<?php echo (int)$row['invoice_ID']; ?>" class="btn-inspect">&#128065; Inspect</a>
                                <button type="button" class="btn-approve-pay"
                                        onclick="openPaymentModal(<?php echo (int)$row['invoice_ID']; ?>, '<?php echo htmlspecialchars($row['invoice_num'], ENT_QUOTES); ?>')">
                                    &#128181; Approve Payment
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-cell">&#128203; No invoices are currently awaiting payment.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="system-footer">© <?php echo date('Y'); ?> Keretapi Tanah Melayu Berhad &nbsp;|&nbsp; KTM eDOIS Internal Review Module</div>

            <!-- ── Confirmation Modal ─────────────────────────────────────── -->
            <div class="modal-overlay" id="confirmModal">
                <div class="modal-box">
                    <div class="modal-icon">&#128181;</div>
                    <div class="modal-title">Confirm Payment</div>
                    <div class="modal-sub" id="modalSub">Mark this invoice as paid? The vendor will be notified that payment is complete.</div>
                    <div class="modal-btns">
                        <button class="btn-modal-yes" id="modalYesBtn">Yes, Approve Payment</button>
                        <button class="btn-modal-no" onclick="closeModal()">Cancel</button>
                    </div>
                </div>
            </div>

            <!-- Hidden form — POST to review_decision.php after modal confirmation -->
            <form id="decisionForm" method="POST" action="review_decision.php" style="display:none;">
                <input type="hidden" name="invoice_id" id="invoiceIdInput">
                <input type="hidden" name="action"      value="PaymentProcessed">
                <input type="hidden" name="redirect_to" value="approve_payment.php">
            </form>

        </div>
    </div>
</div>

<script>
function openPaymentModal(invoiceId, invoiceNum) {
    document.getElementById('invoiceIdInput').value = invoiceId;
    document.getElementById('modalSub').textContent = 'Mark invoice ' + invoiceNum + ' as paid? The vendor will be notified that payment is complete.';
    document.getElementById('confirmModal').classList.add('active');
}

function closeModal() {
    document.getElementById('confirmModal').classList.remove('active');
}

document.getElementById('modalYesBtn').addEventListener('click', function() {
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
