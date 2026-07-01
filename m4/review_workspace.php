<?php
// =========================================================================
// FILE        : review_workspace.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : SDD_CLS_402 — reviewSubmissionUI
// DESCRIPTION : Detailed review workspace. Instantiates ReviewAndApprovalController
//               (OOP) to load invoice data and audit history. Decision panel
//               lets the officer Approve, Reject, or Request Additional Info.
//               CLIENT-SIDE VALIDATION ensures remarks are filled before any
//               button opens the confirmation modal (SDD RQ06).
// =========================================================================

if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/ReviewAndApprovalController.php";

// ── Session guard ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['staff_auth'])) {
    header("Location: " . app_url('m1/staff_login.php')); exit;
}

// Redirect if no invoice ID given
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: review_dashboard.php"); exit;
}

$staffId    = $_SESSION['staff_auth']['staff_id'] ?? $_SESSION['staff_auth']['staff_ID'] ?? 'STF001';
$staffName  = htmlspecialchars($_SESSION['staff_auth']['name'] ?? 'Staff');
$staffRole  = trim($_SESSION['staff_auth']['sub_role'] ?? $_SESSION['staff_auth']['role'] ?? 'Staff');

// ── OOP: instantiate controller and call methods ──────────────────────────────
$controller   = new ReviewAndApprovalController($conn, $staffId);
$invoiceData  = $controller->getInvoiceForReview((int)$_GET['id']);
$auditHistory = $controller->getAuditHistory((int)$_GET['id']);

if (!$invoiceData) {
    header("Location: review_dashboard.php"); exit;
}

$isFinanceReview = ($invoiceData['invoice_status'] === 'Finance Review');
$canReview       = in_array($invoiceData['invoice_status'], ['Submitted', 'Under Review', 'Finance Review']);
$isFinanceUser   = (stripos($staffRole, 'finance') !== false);

// A fully Approved invoice whose payment hasn't been disbursed yet is ready
// for the Finance Officer to process payment (mark payment_status = Paid).
$canProcessPayment = (
    $isFinanceUser &&
    $invoiceData['invoice_status'] === 'Approved' &&
    ($invoiceData['payment_status'] ?? '') !== 'Paid'
);
$current_page    = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS – Review Workspace</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <style>
        .app-layout-wrapper    { display:flex; flex-direction:column; width:100%; height:100vh; background:#f3f5f9; }
        .lower-split-container { display:flex; flex-grow:1; overflow:hidden; }
        .content-body          { flex-grow:1; padding:36px; overflow-y:auto; font-family:'Inter',sans-serif; color:#333; }

        :root { --navy:#002D62; --border:#e2e8f0; --muted:#718096; }
        .page-header  { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .page-title   { font-size:26px; font-weight:700; color:var(--navy); }

        .btn-back-link { display:inline-flex; align-items:center; gap:7px; background:#f1f5f9; color:#4a5568; text-decoration:none; padding:9px 18px; border-radius:7px; font-weight:600; font-size:13px; border:1px solid #cbd5e1; margin-bottom:24px; transition:background 0.2s; }
        .btn-back-link:hover { background:#e2e8f0; }

        /* ── TWO-COLUMN LAYOUT ──────────────────────────────────────────── */
        .review-grid { display:grid; grid-template-columns:1.6fr 1fr; gap:24px; align-items:start; }

        /* ── CARDS ─────────────────────────────────────────────────────── */
        .card        { background:#fff; border-radius:10px; border:1px solid var(--border); padding:26px; margin-bottom:20px; }
        .card-title  { font-size:15px; font-weight:700; color:#1a1a1a; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:8px; }

        /* ── INFO ROWS ─────────────────────────────────────────────────── */
        .info-row    { display:flex; margin-bottom:12px; font-size:14px; }
        .info-label  { width:165px; font-weight:600; color:#555; flex-shrink:0; }
        .info-value  { color:#1a1a1a; }

        /* ── FINANCIAL TABLE ───────────────────────────────────────────── */
        .fin-table   { width:100%; border-collapse:collapse; font-size:14px; }
        .fin-table td { padding:10px 0; border-bottom:1px solid var(--border); }
        .fin-table tr:last-child td { border-bottom:none; font-weight:700; font-size:15px; color:var(--navy); }
        .fin-label   { color:var(--muted); }
        .fin-val     { text-align:right; font-weight:600; }

        /* ── BADGES ────────────────────────────────────────────────────── */
        .badge              { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .s-submitted        { background:#eef2ff; color:#4f46e5; }
        .s-under-review     { background:#fef3c7; color:#d97706; }
        .s-finance-review   { background:#e0f2fe; color:#0369a1; }
        .s-approved         { background:#ecfdf5; color:#059669; }
        .s-rejected         { background:#fef2f2; color:#dc2626; }
        .s-unknown          { background:#f1f5f9; color:#718096; }
        .pay-pending        { background:#f1f5f9; color:#718096; }
        .pay-processing     { background:#fef3c7; color:#d97706; }
        .pay-paid           { background:#ecfdf5; color:#059669; }

        .btn-pay      { display:block; width:100%; background:#0369a1; color:#fff; border:none; padding:13px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; margin-bottom:10px; transition:opacity 0.2s; }
        .btn-pay:hover { opacity:0.88; }

        /* ── DOCUMENT ROW ──────────────────────────────────────────────── */
        .doc-row     { display:flex; align-items:center; justify-content:space-between; padding:11px 14px; background:#f8fafc; border-radius:7px; border:1px solid var(--border); font-size:14px; }
        .btn-view-doc{ background:var(--navy); color:#fff; padding:7px 14px; border-radius:6px; text-decoration:none; font-size:13px; font-weight:600; }

        /* ── TIMELINE ──────────────────────────────────────────────────── */
        .timeline    { padding-left:18px; border-left:2px solid var(--border); }
        .tl-item     { position:relative; margin-bottom:14px; }
        .tl-dot      { position:absolute; left:-23px; top:4px; width:10px; height:10px; border-radius:50%; }
        .dot-v       { background:#16a34a; }
        .dot-r       { background:#dc2626; }
        .dot-u       { background:#d97706; }
        .dot-a       { background:#0369a1; }
        .dot-default { background:var(--navy); }
        .tl-action   { font-size:13px; font-weight:600; }
        .tl-meta     { font-size:12px; color:var(--muted); margin-top:2px; }
        .tl-record   { font-size:11px; color:#a0aec0; }

        /* ── DECISION PANEL ────────────────────────────────────────────── */
        .decision-card { background:#fff; border-radius:10px; border:1px solid var(--border); padding:26px; position:sticky; top:20px; }

        textarea     { width:100%; padding:11px 13px; border:1px solid #cbd5e0; border-radius:7px; font-size:14px; background:#fafafa; resize:vertical; min-height:100px; font-family:'Inter',sans-serif; outline:none; transition:border-color 0.2s, box-shadow 0.2s; }
        textarea:focus       { border-color:var(--navy); box-shadow:0 0 0 3px rgba(0,45,98,0.1); }
        textarea.input-error { border-color:#dc2626 !important; box-shadow:0 0 0 3px rgba(220,38,38,0.12); }
        .form-label  { font-size:13px; font-weight:600; color:#4a5568; display:block; margin-bottom:6px; }
        .error-msg   { color:#dc2626; font-size:12px; font-weight:600; margin-top:6px; display:none; }
        .error-msg.show { display:block; }

        /* ── ACTION BUTTONS ────────────────────────────────────────────── */
        .btn-approve { display:block; width:100%; background:#16a34a; color:#fff; border:none; padding:13px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; margin-bottom:10px; transition:opacity 0.2s; }
        .btn-approve:hover { opacity:0.88; }
        .btn-request { display:block; width:100%; background:#d97706; color:#fff; border:none; padding:13px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; margin-bottom:10px; transition:opacity 0.2s; }
        .btn-request:hover { opacity:0.88; }
        .btn-reject  { display:block; width:100%; background:#dc2626; color:#fff; border:none; padding:13px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; margin-bottom:10px; transition:opacity 0.2s; }
        .btn-reject:hover  { opacity:0.88; }
        .btn-cancel  { display:block; width:100%; background:#fff; color:#4a5568; border:1px solid #cbd5e0; padding:12px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; text-decoration:none; text-align:center; transition:background 0.2s; }
        .btn-cancel:hover  { background:#f7fafc; }

        .already-done { background:#f8fafc; border-radius:8px; padding:16px; text-align:center; font-size:14px; color:#4a5568; }
        .reason-box   { background:#fef2f2; border:1px solid #fecaca; border-radius:6px; padding:12px; font-size:13px; color:#dc2626; margin-top:10px; }

        /* ── ROLE INFO PILL ─────────────────────────────────────────────── */
        .role-pill   { background:#eef2ff; color:#4f46e5; padding:4px 12px; border-radius:50px; font-size:11px; font-weight:700; }
        .finance-pill{ background:#e0f2fe; color:#0369a1; }

        /* ── CONFIRMATION MODAL (SDD RQ06) ──────────────────────────────── */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; z-index:9999; opacity:0; pointer-events:none; transition:opacity 0.25s; }
        .modal-overlay.active { opacity:1; pointer-events:auto; }
        .modal-box     { background:#EAEAEA; width:430px; padding:36px 28px; border-radius:22px; box-shadow:0 10px 30px rgba(0,0,0,0.18); text-align:center; transform:scale(0.85); transition:transform 0.25s; }
        .modal-overlay.active .modal-box { transform:scale(1); }
        .modal-icon    { width:56px; height:56px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:26px; color:#fff; margin-bottom:14px; }
        .modal-title   { font-size:20px; font-weight:700; color:#000; margin-bottom:8px; }
        .modal-sub     { font-size:14px; color:#4a5568; margin-bottom:22px; line-height:1.5; }
        .modal-btns    { display:flex; gap:12px; justify-content:center; }
        .btn-modal-yes { background:#1e1e1e; color:#fff; border:none; padding:11px 28px; border-radius:9px; font-weight:700; font-size:14px; cursor:pointer; }
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
                <h1 class="page-title">Review Workspace</h1>
            </div>

            <a href="review_dashboard.php" class="btn-back-link">&#8592; Back to Dashboard</a>

            <div class="review-grid">

                <!-- ── LEFT: Invoice details ────────────────────────────── -->
                <div>
                    <!-- Invoice Details Card -->
                    <div class="card">
                        <div class="card-title">&#128196; Invoice Details</div>
                        <div class="info-row"><span class="info-label">Invoice Number</span><span class="info-value"><strong><?php echo htmlspecialchars($invoiceData['invoice_num']); ?></strong></span></div>
                        <div class="info-row"><span class="info-label">DO Number</span><span class="info-value"><code><?php echo htmlspecialchars($invoiceData['DO_ID']); ?></code></span></div>
                        <div class="info-row"><span class="info-label">PO Reference</span><span class="info-value"><?php echo htmlspecialchars($invoiceData['PO_number'] ?? $invoiceData['PO_ID'] ?? '—'); ?></span></div>
                        <div class="info-row"><span class="info-label">Supplier</span><span class="info-value"><?php echo htmlspecialchars($invoiceData['supplier_name'] ?? '—'); ?></span></div>
                        <div class="info-row"><span class="info-label">Invoice Date</span><span class="info-value"><?php echo date('d M Y', strtotime($invoiceData['invoice_date'])); ?></span></div>
                        <?php if (!empty($invoiceData['billing_address'])): ?>
                        <div class="info-row"><span class="info-label">Billing Address</span><span class="info-value"><?php echo htmlspecialchars($invoiceData['billing_address']); ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($invoiceData['description'])): ?>
                        <div class="info-row"><span class="info-label">Description</span><span class="info-value"><?php echo htmlspecialchars($invoiceData['description']); ?></span></div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-label">Current Status</span>
                            <span class="info-value">
                                <?php
                                $statusSlug = 's-' . strtolower(str_replace(' ', '-', $invoiceData['invoice_status']));
                                $displayStatus = $invoiceData['invoice_status'];
                                // Handle edge cases
                                if (empty($displayStatus) || $displayStatus === '0') $displayStatus = 'Unknown';
                                ?>
                                <span class="badge <?php echo $statusSlug; ?>"><?php echo htmlspecialchars($displayStatus); ?></span>
                            </span>
                        </div>
                        <?php if (!empty($invoiceData['payment_status'])): ?>
                        <div class="info-row">
                            <span class="info-label">Payment Status</span>
                            <span class="info-value">
                                <?php $paySlug = 'pay-' . strtolower($invoiceData['payment_status']); ?>
                                <span class="badge <?php echo $paySlug; ?>"><?php echo htmlspecialchars($invoiceData['payment_status']); ?></span>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Financial Breakdown -->
                    <div class="card">
                        <div class="card-title">&#128184; Financial Breakdown</div>
                        <table class="fin-table">
                            <tr><td class="fin-label">Subtotal</td><td class="fin-val">RM <?php echo number_format($invoiceData['subtotal'] ?? 0, 2); ?></td></tr>
                            <tr><td class="fin-label">Tax (SST 6%)</td><td class="fin-val">RM <?php echo number_format($invoiceData['tax'] ?? 0, 2); ?></td></tr>
                            <?php if (isset($invoiceData['credit_note']) && $invoiceData['credit_note'] > 0): ?>
                            <tr><td class="fin-label">Credit Note / Discount</td><td class="fin-val" style="color:#16a34a;">- RM <?php echo number_format($invoiceData['credit_note'], 2); ?></td></tr>
                            <?php endif; ?>
                            <?php if (!empty($invoiceData['penalty']) && $invoiceData['penalty'] > 0): ?>
                            <tr><td class="fin-label">Penalty (1%)</td><td class="fin-val" style="color:#dc2626;">- RM <?php echo number_format($invoiceData['penalty'], 2); ?></td></tr>
                            <?php endif; ?>
                            <tr><td class="fin-label"><strong>Total Payable</strong></td><td class="fin-val">RM <?php echo number_format($invoiceData['total'] ?? 0, 2); ?></td></tr>
                        </table>
                    </div>

                    <!-- Supporting Document -->
                    <?php if (!empty($invoiceData['proof_of_delivery'])): ?>
                    <div class="card">
                        <div class="card-title">&#128196; Supporting Document</div>
                        <div class="doc-row">
                            <span>&#128196; <?php echo htmlspecialchars(basename($invoiceData['proof_of_delivery'])); ?></span>
                            <a href="/KTMEDOIS/<?php echo htmlspecialchars($invoiceData['proof_of_delivery']); ?>" target="_blank" class="btn-view-doc">&#128065; View</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Audit History Timeline -->
                    <?php if (!empty($auditHistory)): ?>
                    <div class="card">
                        <div class="card-title">&#128203; Audit History</div>
                        <div class="timeline">
                            <?php foreach ($auditHistory as $log):
                                $dotClass = 'dot-default';
                                if ($log['action'] === 'Verified')        $dotClass = 'dot-v';
                                elseif ($log['action'] === 'Rejected')    $dotClass = 'dot-r';
                                elseif ($log['action'] === 'UnderReview') $dotClass = 'dot-u';
                                elseif ($log['action'] === 'Approved')    $dotClass = 'dot-a';
                            ?>
                            <div class="tl-item">
                                <div class="tl-dot <?php echo $dotClass; ?>"></div>
                                <div class="tl-action"><?php echo htmlspecialchars($log['action']); ?></div>
                                <div class="tl-meta">By <?php echo htmlspecialchars($log['staff_name']); ?> &middot; <?php echo date('d M Y, H:i', strtotime($log['timestamp'])); ?></div>
                                <div class="tl-record">TRX: <?php echo htmlspecialchars($log['record_ID']); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ── RIGHT: Decision panel ─────────────────────────────── -->
                <div>
                    <div class="decision-card">
                        <div class="card-title">&#10003; Review Decision</div>

                        <?php if ($canReview): ?>
                            <div style="margin-bottom:16px;">
                                <label class="form-label">Review Remarks <span style="color:#dc2626;">*</span></label>
                                <textarea id="remarksInput" placeholder="Enter evaluation remarks or reason..."></textarea>
                                <div class="error-msg" id="remarksError">
                                    &#9888; Please enter your remarks before submitting.
                                </div>
                            </div>

                            <?php if ($isFinanceReview): ?>
                                <!-- Finance final approval -->
                                <button class="btn-approve" onclick="openModal('Approved')">&#10003; Final Approve &amp; Process Payment</button>
                                <button class="btn-reject"  onclick="openModal('Rejected')">&#10007; Reject Invoice</button>
                            <?php else: ?>
                                <!-- Officer review -->
                                <button class="btn-approve"  onclick="openModal('Verified')">&#10003; Approve Invoice</button>
                                <button class="btn-request"  onclick="openModal('UnderReview')">&#8635; Request Additional Info</button>
                                <button class="btn-reject"   onclick="openModal('Rejected')">&#10007; Reject Invoice</button>
                            <?php endif; ?>

                            <a href="review_dashboard.php" class="btn-cancel" style="margin-top:6px;">Cancel</a>

                        <?php elseif ($canProcessPayment): ?>
                            <!-- Finance: process payment on a fully-approved invoice -->
                            <div class="already-done" style="margin-bottom:16px;">
                                <p>This invoice is <strong>Approved</strong>. Payment is currently
                                   <strong><?php echo htmlspecialchars($invoiceData['payment_status']); ?></strong>.</p>
                            </div>
                            <button class="btn-pay" onclick="openPaymentModal()">&#128181; Mark Payment as Paid</button>
                            <a href="review_dashboard.php" class="btn-cancel">Back to Dashboard</a>

                        <?php else: ?>
                            <div class="already-done">
                                <p>This invoice is <strong><?php echo htmlspecialchars($invoiceData['invoice_status']); ?></strong> and cannot be reviewed further.</p>
                                <?php if (!empty($invoiceData['reason'])): ?>
                                <div class="reason-box"><strong>Reason on file:</strong><br><?php echo htmlspecialchars($invoiceData['reason']); ?></div>
                                <?php endif; ?>
                            </div>
                            <br>
                            <a href="review_dashboard.php" class="btn-cancel">Back to Dashboard</a>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /review-grid -->

            <!-- ── SDD_CLS_402 RQ06: Confirmation Modal ─────────────────── -->
            <div class="modal-overlay" id="confirmModal">
                <div class="modal-box">
                    <div class="modal-icon" id="modalIcon">&#10003;</div>
                    <div class="modal-title" id="modalTitle">Confirm Decision</div>
                    <div class="modal-sub"   id="modalSub">Are you sure?</div>
                    <div class="modal-btns">
                        <button class="btn-modal-yes" id="modalYesBtn">Yes, Confirm</button>
                        <button class="btn-modal-no"  onclick="closeModal()">Cancel</button>
                    </div>
                </div>
            </div>

            <!-- Hidden form — POST to review_decision.php after modal confirmation -->
            <form id="decisionForm" method="POST" action="review_decision.php" style="display:none;">
                <input type="hidden" name="invoice_id"    value="<?php echo (int)$invoiceData['invoice_ID']; ?>">
                <input type="hidden" name="action"         id="actionInput">
                <input type="hidden" name="review_remarks" id="remarksHidden">
                <input type="hidden" name="redirect_to"    value="review_dashboard.php">
            </form>

            <div class="system-footer">© <?php echo date('Y'); ?> Keretapi Tanah Melayu Berhad &nbsp;|&nbsp; KTM eDOIS Internal Review Module</div>

        </div>
    </div>
</div>

<script>
let pendingAction = '';

// ── openModal() — SDD_CLS_402 ─────────────────────────────────────────────
// Step 1: Validate that remarks are not empty (CLIENT-SIDE VALIDATION)
// Step 2: Build modal content based on action type
// Step 3: Show modal overlay
function openModal(action) {
    const remarksEl = document.getElementById('remarksInput');
    const errorEl   = document.getElementById('remarksError');
    const remarks   = remarksEl.value.trim();

    // CLIENT-SIDE VALIDATION — remarks mandatory for all actions
    if (!remarks) {
        remarksEl.classList.add('input-error');
        errorEl.classList.add('show');
        remarksEl.focus();
        return;
    }
    remarksEl.classList.remove('input-error');
    errorEl.classList.remove('show');

    pendingAction = action;
    const isApprove = (action === 'Verified' || action === 'Approved');
    const isRequest = (action === 'UnderReview');

    const icon = document.getElementById('modalIcon');
    icon.textContent      = isApprove ? '✓' : isRequest ? '↻' : '✗';
    icon.style.background = isApprove ? '#16a34a' : isRequest ? '#d97706' : '#dc2626';

    document.getElementById('modalTitle').textContent = isApprove
        ? (action === 'Approved' ? 'Final Approval' : 'Confirm Approval')
        : isRequest ? 'Request Additional Info' : 'Confirm Rejection';

    document.getElementById('modalSub').textContent = isApprove
        ? (action === 'Approved'
            ? 'Fully approve this invoice and initiate payment processing?'
            : 'Approve this invoice and route to Finance Review queue?')
        : isRequest
        ? 'Set invoice to Under Review and notify vendor to provide additional information?'
        : 'Reject this invoice? The vendor will be notified with your remarks.';

    document.getElementById('confirmModal').classList.add('active');
}

// ── openPaymentModal() — Finance "Mark Payment as Paid" (no remarks needed) ──
function openPaymentModal() {
    pendingAction = 'PaymentProcessed';

    const icon = document.getElementById('modalIcon');
    icon.textContent      = '💲';
    icon.style.background = '#0369a1';

    document.getElementById('modalTitle').textContent = 'Confirm Payment';
    document.getElementById('modalSub').textContent   = 'Mark this invoice as paid? The vendor will be notified that payment is complete.';

    document.getElementById('confirmModal').classList.add('active');
}

function closeModal() {
    document.getElementById('confirmModal').classList.remove('active');
}

document.getElementById('modalYesBtn').addEventListener('click', function() {
    document.getElementById('actionInput').value   = pendingAction;
    const remarksEl = document.getElementById('remarksInput');
    document.getElementById('remarksHidden').value = remarksEl ? remarksEl.value.trim() : '';
    closeModal();
    document.getElementById('decisionForm').submit();
});

// Auto-clear error styling as user types
const remarksInputEl = document.getElementById('remarksInput');
if (remarksInputEl) {
    remarksInputEl.addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('input-error');
            document.getElementById('remarksError').classList.remove('show');
        }
    });
}

// Close modal by clicking outside
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
<?php $conn->close(); ?>
