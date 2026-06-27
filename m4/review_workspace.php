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
// AUTHOR      : Module 4 Developer
// DATE        : June 2026
// =========================================================================

if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/ReviewAndApprovalController.php";

// Redirect if no invoice ID given
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: review_dashboard.php"); exit;
}

// ── OOP: instantiate controller and call methods ──────────────────────────────
$controller   = new ReviewAndApprovalController($conn);
$invoiceData  = $controller->getInvoiceForReview((int)$_GET['id']);
$auditHistory = $controller->getAuditHistory((int)$_GET['id']);

if (!$invoiceData) {
    header("Location: review_dashboard.php"); exit;
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS - Review Workspace</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <style>
        .app-layout-wrapper    { display:flex; flex-direction:column; width:100%; height:100vh; background:#f3f5f9; }
        .lower-split-container { display:flex; flex-grow:1; overflow:hidden; }
        .content-body          { flex-grow:1; padding:36px; overflow-y:auto; font-family:'Inter',sans-serif; color:#333; }

        :root { --navy:#002D62; --border:#e2e8f0; --muted:#718096; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .page-title  { font-size:26px; font-weight:700; color:var(--navy); }
        .ktmb-logo   { height:46px; width:auto; }

        .btn-back-link { display:inline-block; background:#f1f5f9; color:#4a5568; text-decoration:none; padding:9px 18px; border-radius:7px; font-weight:600; font-size:13px; border:1px solid #cbd5e1; margin-bottom:24px; transition:background 0.2s; }
        .btn-back-link:hover { background:#e2e8f0; }

        .review-grid { display:grid; grid-template-columns:1.5fr 1fr; gap:24px; align-items:start; }

        .card { background:#fff; border-radius:10px; border:1px solid var(--border); padding:26px; margin-bottom:20px; }
        .card-title { font-size:15px; font-weight:700; color:#1a1a1a; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border); }

        .info-row    { display:flex; margin-bottom:12px; font-size:14px; }
        .info-label  { width:155px; font-weight:600; color:#555; flex-shrink:0; }
        .info-value  { color:#1a1a1a; }

        .fin-table   { width:100%; border-collapse:collapse; font-size:14px; }
        .fin-table td { padding:10px 0; border-bottom:1px solid var(--border); }
        .fin-table tr:last-child td { border-bottom:none; font-weight:700; font-size:15px; color:var(--navy); }
        .fin-label   { color:var(--muted); }
        .fin-val     { text-align:right; font-weight:600; }

        .badge { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .s-submitted      { background:#eef2ff; color:#4f46e5; }
        .s-under-review   { background:#fef3c7; color:#d97706; }
        .s-finance-review { background:#e0f2fe; color:#0369a1; }
        .s-approved       { background:#ecfdf5; color:#059669; }
        .s-rejected       { background:#fef2f2; color:#dc2626; }

        .doc-row { display:flex; align-items:center; justify-content:space-between; padding:11px 14px; background:#f8fafc; border-radius:7px; border:1px solid var(--border); font-size:14px; }
        .btn-view-doc { background:var(--navy); color:#fff; padding:7px 14px; border-radius:6px; text-decoration:none; font-size:13px; font-weight:600; }

        .timeline    { padding-left:18px; border-left:2px solid var(--border); }
        .tl-item     { position:relative; margin-bottom:14px; }
        .tl-dot      { position:absolute; left:-23px; top:4px; width:10px; height:10px; background:var(--navy); border-radius:50%; }
        .tl-action   { font-size:13px; font-weight:600; }
        .tl-meta     { font-size:12px; color:var(--muted); margin-top:2px; }
        .tl-record   { font-size:11px; color:#a0aec0; }

        /* Decision panel */
        .decision-card { background:#fff; border-radius:10px; border:1px solid var(--border); padding:26px; position:sticky; top:20px; }
        .form-label  { font-size:13px; font-weight:600; color:#4a5568; display:block; margin-bottom:6px; }

        /* ── CLIENT-SIDE VALIDATION STYLES ─────────────────────────────── */
        textarea     { width:100%; padding:11px 13px; border:1px solid #cbd5e0; border-radius:7px; font-size:14px; background:#fafafa; resize:vertical; min-height:100px; font-family:'Inter',sans-serif; outline:none; transition:border-color 0.2s, box-shadow 0.2s; }
        textarea:focus          { border-color:var(--navy); }
        textarea.input-error    { border-color:#dc2626 !important; box-shadow:0 0 0 3px rgba(220,38,38,0.12); }
        .remarks-error-msg      { color:#dc2626; font-size:12px; font-weight:600; margin-top:6px; display:none; }
        .remarks-error-msg.show { display:block; }
        /* ────────────────────────────────────────────────────────────────── */

        .btn-approve  { display:block; width:100%; background:#16a34a; color:#fff; border:none; padding:13px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; margin-bottom:10px; transition:opacity 0.2s; }
        .btn-approve:hover  { opacity:0.88; }
        .btn-request  { display:block; width:100%; background:#d97706; color:#fff; border:none; padding:13px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; margin-bottom:10px; transition:opacity 0.2s; }
        .btn-request:hover  { opacity:0.88; }
        .btn-reject   { display:block; width:100%; background:#dc2626; color:#fff; border:none; padding:13px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; margin-bottom:10px; transition:opacity 0.2s; }
        .btn-reject:hover   { opacity:0.88; }
        .btn-cancel   { display:block; width:100%; background:#fff; color:#4a5568; border:1px solid #cbd5e0; padding:12px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; text-decoration:none; text-align:center; transition:background 0.2s; }
        .btn-cancel:hover   { background:#f7fafc; }

        .already-done { background:#f8fafc; border-radius:8px; padding:16px; text-align:center; font-size:14px; color:#4a5568; }
        .reason-box   { background:#fef2f2; border:1px solid #fecaca; border-radius:6px; padding:12px; font-size:13px; color:#dc2626; margin-top:10px; }

        /* Confirmation modal — SDD_CLS_402: RQ06 */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; z-index:9999; opacity:0; pointer-events:none; transition:opacity 0.25s; }
        .modal-overlay.active { opacity:1; pointer-events:auto; }
        .modal-box     { background:#EAEAEA; width:420px; padding:36px 28px; border-radius:22px; box-shadow:0 10px 30px rgba(0,0,0,0.18); text-align:center; transform:scale(0.85); transition:transform 0.25s; }
        .modal-overlay.active .modal-box { transform:scale(1); }
        .modal-icon    { width:52px; height:52px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:24px; color:#fff; margin-bottom:14px; }
        .modal-title   { font-size:20px; font-weight:700; color:#000; margin-bottom:8px; }
        .modal-sub     { font-size:14px; color:#4a5568; margin-bottom:22px; line-height:1.5; }
        .modal-btns    { display:flex; gap:12px; justify-content:center; }
        .btn-modal-yes { background:#1e1e1e; color:#fff; border:none; padding:10px 26px; border-radius:9px; font-weight:700; font-size:14px; cursor:pointer; }
        .btn-modal-no  { background:#D1CDCD; color:#000; border:1px solid #A6A2A2; padding:10px 22px; border-radius:9px; font-weight:600; font-size:14px; cursor:pointer; }

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
                <img src="/KTMEDOIS/ktmb_logo.jpg" alt="KTMB Logo" class="ktmb-logo">
            </div>

            <a href="review_dashboard.php" class="btn-back-link">&#8592; Back to Dashboard</a>

            <div class="review-grid">

                <!-- LEFT: Invoice details -->
                <div>
                    <div class="card">
                        <div class="card-title">Invoice Information</div>
                        <div class="info-row"><span class="info-label">Invoice No.</span><span class="info-value"><?php echo htmlspecialchars($invoiceData['invoice_num']); ?></span></div>
                        <div class="info-row"><span class="info-label">DO Reference</span><span class="info-value"><code><?php echo htmlspecialchars($invoiceData['DO_ID']); ?></code></span></div>
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
                                <?php $slug = 's-' . strtolower(str_replace(' ','-',$invoiceData['invoice_status'])); ?>
                                <span class="badge <?php echo $slug; ?>"><?php echo htmlspecialchars($invoiceData['invoice_status']); ?></span>
                            </span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-title">Financial Breakdown</div>
                        <table class="fin-table">
                            <tr><td class="fin-label">Subtotal</td><td class="fin-val">MYR <?php echo number_format($invoiceData['subtotal'] ?? 0, 2); ?></td></tr>
                            <tr><td class="fin-label">Tax (SST 6%)</td><td class="fin-val">MYR <?php echo number_format($invoiceData['tax'] ?? 0, 2); ?></td></tr>
                            <tr><td class="fin-label">Credit Note / Discount</td><td class="fin-val" style="color:#16a34a;">- MYR <?php echo number_format($invoiceData['credit_note'] ?? 0, 2); ?></td></tr>
                            <?php if (!empty($invoiceData['penalty']) && $invoiceData['penalty'] > 0): ?>
                            <tr><td class="fin-label">Penalty (1%)</td><td class="fin-val" style="color:#dc2626;">- MYR <?php echo number_format($invoiceData['penalty'], 2); ?></td></tr>
                            <?php endif; ?>
                            <tr><td class="fin-label"><strong>Total Payable</strong></td><td class="fin-val">MYR <?php echo number_format($invoiceData['total'] ?? 0, 2); ?></td></tr>
                        </table>
                    </div>

                    <?php if (!empty($invoiceData['proof_of_delivery'])): ?>
                    <div class="card">
                        <div class="card-title">Supporting Document</div>
                        <div class="doc-row">
                            <span>&#128196; <?php echo htmlspecialchars(basename($invoiceData['proof_of_delivery'])); ?></span>
                            <a href="/KTMEDOIS/<?php echo htmlspecialchars($invoiceData['proof_of_delivery']); ?>" target="_blank" class="btn-view-doc">View</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($auditHistory)): ?>
                    <div class="card">
                        <div class="card-title">Audit History</div>
                        <div class="timeline">
                            <?php foreach ($auditHistory as $log): ?>
                            <div class="tl-item">
                                <div class="tl-dot"></div>
                                <div class="tl-action"><?php echo htmlspecialchars($log['action']); ?></div>
                                <div class="tl-meta">By <?php echo htmlspecialchars($log['staff_name']); ?> &middot; <?php echo date('d M Y, H:i', strtotime($log['timestamp'])); ?></div>
                                <div class="tl-record">TRX: <?php echo htmlspecialchars($log['record_ID']); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- RIGHT: Decision panel — SDD_CLS_402 -->
                <div>
                    <div class="decision-card">
                        <div class="card-title">Review Decision</div>

                        <?php if (in_array($invoiceData['invoice_status'], ['Submitted', 'Under Review'])): ?>
                            <div style="margin-bottom:16px;">
                                <label class="form-label">Review Remarks <span style="color:#dc2626;">*</span></label>
                                <textarea id="remarksInput" placeholder="Enter evaluation remarks or rejection reason..."></textarea>
                                <!-- ── CLIENT-SIDE VALIDATION ERROR MESSAGE ── -->
                                <div class="remarks-error-msg" id="remarksError">
                                    &#9888; Please enter your remarks before submitting a decision.
                                </div>
                            </div>

                            <!-- SDD_CLS_402: Approve button → calls updateStatus('Verified') -->
                            <button class="btn-approve" onclick="openModal('Verified')">&#10003; Approve Invoice</button>

                            <!-- SDD_CLS_402: requestAdditionalInfo() → calls setUnderReview() -->
                            <button class="btn-request" onclick="openModal('UnderReview')">&#8635; Request Additional Info</button>

                            <!-- SDD_CLS_402: Reject button → calls updateStatus('Rejected') -->
                            <button class="btn-reject" onclick="openModal('Rejected')">&#10007; Reject Invoice</button>

                            <!-- SDD_CLS_402: cancelReview() -->
                            <a href="review_dashboard.php" class="btn-cancel">Cancel</a>

                        <?php else: ?>
                            <div class="already-done">
                                <p>This invoice is <strong><?php echo htmlspecialchars($invoiceData['invoice_status']); ?></strong> and cannot be reviewed again.</p>
                                <?php if (!empty($invoiceData['reason'])): ?>
                                <div class="reason-box"><strong>Reason:</strong> <?php echo htmlspecialchars($invoiceData['reason']); ?></div>
                                <?php endif; ?>
                            </div>
                            <br>
                            <a href="review_dashboard.php" class="btn-cancel">Back to Dashboard</a>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /review-grid -->

            <!-- SDD_CLS_402: RQ06 Confirmation Modal -->
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
            </form>

            <div class="system-footer">© 2026 KTMEDOIS INTEGRATED PORTAL</div>

        </div><!-- /content-body -->
    </div><!-- /lower-split-container -->
</div><!-- /app-layout-wrapper -->

<script>
let pendingAction = '';

// ── openModal() — SDD_CLS_402 ────────────────────────────────────────────────
// Step 1: Validate that remarks are not empty (CLIENT-SIDE VALIDATION)
// Step 2: If valid, build modal content based on action type
// Step 3: Show modal overlay
function openModal(action) {
    const remarksEl = document.getElementById('remarksInput');
    const errorEl   = document.getElementById('remarksError');
    const remarks   = remarksEl.value.trim();

    // ── CLIENT-SIDE VALIDATION ────────────────────────────────────────────────
    // Remarks are mandatory for ALL three actions — needed for audit trail
    if (!remarks) {
        remarksEl.classList.add('input-error');
        errorEl.classList.add('show');
        remarksEl.focus();
        return; // STOP — do not open modal if remarks empty
    }
    // Clear error state once user has entered text
    remarksEl.classList.remove('input-error');
    errorEl.classList.remove('show');

    // Build modal content based on which action button was clicked
    pendingAction = action;
    const isApprove = (action === 'Verified');
    const isRequest = (action === 'UnderReview');

    const icon = document.getElementById('modalIcon');
    icon.textContent      = isApprove ? '✓' : isRequest ? '↻' : '✗';
    icon.style.background = isApprove ? '#16a34a' : isRequest ? '#d97706' : '#dc2626';

    document.getElementById('modalTitle').textContent = isApprove
        ? 'Confirm Approval'
        : isRequest ? 'Request Additional Info' : 'Confirm Rejection';

    document.getElementById('modalSub').textContent = isApprove
        ? 'Approve this invoice and route to Finance Review queue?'
        : isRequest
        ? 'Set invoice to Under Review and notify vendor to provide additional information?'
        : 'Reject this invoice? The vendor will be notified with your remarks.';

    document.getElementById('confirmModal').classList.add('active');
}

function closeModal() {
    document.getElementById('confirmModal').classList.remove('active');
}

// On modal confirmation: transfer data to hidden form and submit to review_decision.php
document.getElementById('modalYesBtn').addEventListener('click', function() {
    document.getElementById('actionInput').value   = pendingAction;
    document.getElementById('remarksHidden').value = document.getElementById('remarksInput').value.trim();
    closeModal();
    document.getElementById('decisionForm').submit();
});

// Auto-clear error styling as user types
document.getElementById('remarksInput').addEventListener('input', function() {
    if (this.value.trim()) {
        this.classList.remove('input-error');
        document.getElementById('remarksError').classList.remove('show');
    }
});

// Close modal when clicking outside modal box
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
<?php $conn->close(); ?>
