<?php
// =========================================================================
// SDD_CLS_402 — reviewSubmissionUI
// =========================================================================
include 'db.php';
$current_page = basename($_SERVER['PHP_SELF']);

$invoice_data = null;
$audit_history = [];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $invoice_id = intval($_GET['id']);

    $stmt = $conn->prepare(
        "SELECT i.*, d.supplier_ID, d.PO_ID, d.proof_of_delivery
         FROM invoice i
         INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
         WHERE i.invoice_ID = ? LIMIT 1"
    );
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $invoice_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Audit history for this invoice
    $stmt2 = $conn->prepare(
        "SELECT a.action, a.record_ID, a.timestamp, k.staff_name
         FROM audit_log a
         INNER JOIN ktmb_staff k ON a.staff_ID = k.staff_ID
         WHERE a.invoice_ID = ?
         ORDER BY a.timestamp DESC"
    );
    $stmt2->bind_param("i", $invoice_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while($r = $res2->fetch_assoc()) $audit_history[] = $r;
    $stmt2->close();
}

if (!$invoice_data) {
    header("Location: review_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Portal - Review Workspace</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color:#f3f5f9; --card-bg:#ffffff; --primary-navy:#002D62; --dark-gray:#1a1a1a; --border-color:#e2e8f0; --text-muted:#757575; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); display: flex; height: 100vh; overflow: hidden; color: #333; }
        .workspace { flex-grow: 1; padding: 40px; overflow-y: auto; width: 100%; display: flex; flex-direction: column; }
        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 16px; }
        .header-title { font-size: 28px; font-weight: 700; color: var(--primary-navy); }
        .logo-container { height: 50px; display: flex; align-items: center; margin-left: auto; }
        .logo-container img { height: 100%; width: auto; object-fit: contain; }

        .details-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 30px; align-items: start; }
        .card { background: var(--card-bg); border-radius: 16px; border: 1px solid var(--border-color); padding: 30px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.01); }
        .section-subtitle { font-size: 16px; font-weight: 700; color: #1a1a1a; margin-bottom: 20px; text-decoration: underline; text-underline-offset: 4px; }

        .meta-row { display: flex; margin-bottom: 14px; font-size: 15px; }
        .meta-label { width: 160px; font-weight: 700; color: #333; }
        .meta-value { color: #4a4a4a; font-weight: 500; }

        /* Financial table */
        .fin-table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 8px; }
        .fin-table td { padding: 10px 0; border-bottom: 1px solid var(--border-color); }
        .fin-table tr:last-child td { border-bottom: none; font-weight: 700; font-size: 16px; color: var(--primary-navy); }
        .fin-table .fin-label { color: var(--text-muted); }
        .fin-table .fin-val { text-align: right; font-weight: 600; }

        /* Status badge */
        .badge { display: inline-block; padding: 5px 14px; border-radius: 50px; font-size: 13px; font-weight: 600; }
        .s-submitted    { background:#eef2ff; color:#4f46e5; }
        .s-under-review { background:#fef3c7; color:#d97706; }
        .s-finance-review { background:#e0f2fe; color:#0369a1; }
        .s-approved     { background:#ecfdf5; color:#059669; }
        .s-rejected     { background:#fef2f2; color:#dc2626; }

        /* Decision form */
        .decision-card { background: var(--card-bg); border-radius: 16px; border: 1px solid var(--border-color); padding: 30px; position: sticky; top: 20px; }
        .form-label { font-size: 13px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 6px; }
        textarea { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 14px; background: #fafafa; resize: vertical; min-height: 100px; }
        textarea:focus { outline: none; border-color: var(--primary-navy); }
        .btn-approve { display: block; width: 100%; background-color: #16a34a; color: #fff; border: none; padding: 14px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; margin-bottom: 10px; transition: opacity 0.2s; }
        .btn-approve:hover { opacity: 0.9; }
        .btn-reject { display: block; width: 100%; background-color: #dc2626; color: #fff; border: none; padding: 14px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; margin-bottom: 10px; transition: opacity 0.2s; }
        .btn-reject:hover { opacity: 0.9; }
        .btn-back { display: block; width: 100%; background-color: #fff; color: #4a5568; border: 1px solid #cbd5e0; padding: 13px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; text-align: center; transition: background-color 0.2s; }
        .btn-back:hover { background-color: #f7fafc; }
        .already-reviewed { background-color: #f7f7f7; border-radius: 8px; padding: 16px; text-align: center; font-size: 14px; color: #4a5568; }
        .reason-box { background:#fef2f2; border:1px solid #fecaca; border-radius:6px; padding:12px; font-size:13px; color:#dc2626; margin-top:10px; }

        /* Timeline */
        .timeline { padding-left: 20px; border-left: 2px solid var(--border-color); }
        .tl-item { position: relative; margin-bottom: 16px; }
        .tl-dot { position: absolute; left: -25px; top: 4px; width: 10px; height: 10px; background: var(--primary-navy); border-radius: 50%; }
        .tl-action { font-size: 13px; font-weight: 600; }
        .tl-meta { font-size: 12px; color: var(--text-muted); }
        .tl-record { font-size: 11px; color: #a0aec0; }

        /* Modal */
        .modal-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center; z-index:9999; opacity:0; pointer-events:none; transition:opacity 0.3s ease; }
        .modal-overlay.active { opacity:1; pointer-events:auto; }
        .modal-card { background:#EAEAEA; width:440px; padding:40px 30px; border-radius:28px; box-shadow:0 10px 25px rgba(0,0,0,0.15); text-align:center; transform:scale(0.8); transition:transform 0.3s ease; display:flex; align-items:center; gap:20px; }
        .modal-overlay.active .modal-card { transform:scale(1); }
        .modal-icon { width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:26px; }
        .icon-approve { background:#16a34a; }
        .icon-reject  { background:#dc2626; }
        .modal-body { display:flex; flex-direction:column; align-items:flex-start; gap:12px; }
        .modal-title { font-size:22px; font-weight:700; color:#000; }
        .modal-sub { font-size:13px; color:#4a4a4a; text-align:left; line-height:1.5; }
        .modal-btns { display:flex; gap:10px; margin-top:4px; }
        .btn-modal-confirm { background:#1e1e1e; color:#fff; font-size:14px; font-weight:700; border:none; padding:10px 24px; border-radius:10px; cursor:pointer; }
        .btn-modal-cancel  { background:#D1CDCD; color:#000; font-size:14px; font-weight:700; border:1px solid #A6A2A2; padding:10px 24px; border-radius:10px; cursor:pointer; }

        .system-footer { text-align:center; font-size:11px; color:#a0aec0; margin-top:auto; padding-top:40px; letter-spacing:1px; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="workspace">

    <div class="header-area">
        <h1 class="header-title">Review Workspace</h1>
        <div class="logo-container"><img src="ktmb_logo.jpg" alt="KTMB Logo"></div>
    </div>

    <div style="margin-bottom:20px;">
        <a href="review_dashboard.php" class="btn-back" style="width:auto;display:inline-block;padding:10px 20px;">&#8592; Back to Dashboard</a>
    </div>

    <div class="details-grid">

        <!-- LEFT: Invoice Details -->
        <div>
            <div class="card">
                <h2 class="section-subtitle">Invoice Details</h2>
                <div class="meta-row"><span class="meta-label">Invoice Number</span><span class="meta-value"><?php echo htmlspecialchars($invoice_data['invoice_num']); ?></span></div>
                <div class="meta-row"><span class="meta-label">DO Reference</span><span class="meta-value"><?php echo htmlspecialchars($invoice_data['DO_ID']); ?></span></div>
                <div class="meta-row"><span class="meta-label">Supplier ID</span><span class="meta-value"><?php echo htmlspecialchars($invoice_data['supplier_ID']); ?></span></div>
                <div class="meta-row"><span class="meta-label">Invoice Date</span><span class="meta-value"><?php echo date('d M Y', strtotime($invoice_data['invoice_date'])); ?></span></div>
                <div class="meta-row"><span class="meta-label">Billing Address</span><span class="meta-value"><?php echo htmlspecialchars($invoice_data['billing_address']); ?></span></div>
                <div class="meta-row"><span class="meta-label">Description</span><span class="meta-value"><?php echo htmlspecialchars($invoice_data['description'] ?? '-'); ?></span></div>
                <div class="meta-row">
                    <span class="meta-label">Status</span>
                    <span class="meta-value">
                        <?php $slug = 's-'.strtolower(str_replace(' ','-',$invoice_data['invoice_status'])); ?>
                        <span class="badge <?php echo $slug; ?>"><?php echo htmlspecialchars($invoice_data['invoice_status']); ?></span>
                    </span>
                </div>
            </div>

            <div class="card">
                <h2 class="section-subtitle">Financial Breakdown</h2>
                <table class="fin-table">
                    <tr><td class="fin-label">Subtotal</td><td class="fin-val">MYR <?php echo number_format($invoice_data['subtotal'],2); ?></td></tr>
                    <tr><td class="fin-label">Tax (SST 6%)</td><td class="fin-val">MYR <?php echo number_format($invoice_data['tax'],2); ?></td></tr>
                    <tr><td class="fin-label">Discount / Credit Note</td><td class="fin-val" style="color:#16a34a;">- MYR <?php echo number_format($invoice_data['credit_note'],2); ?></td></tr>
                    <?php if (!empty($invoice_data['penalty'])): ?>
                    <tr><td class="fin-label">Penalty (1%)</td><td class="fin-val" style="color:#dc2626;">- MYR <?php echo number_format($invoice_data['penalty'],2); ?></td></tr>
                    <?php endif; ?>
                    <tr><td class="fin-label">Total Claim</td><td class="fin-val">MYR <?php echo number_format($invoice_data['total'],2); ?></td></tr>
                </table>
            </div>

            <!-- Review History Timeline -->
            <?php if (!empty($audit_history)): ?>
            <div class="card">
                <h2 class="section-subtitle">Review History</h2>
                <div class="timeline">
                    <?php foreach($audit_history as $log): ?>
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

        <!-- RIGHT: Decision Panel -->
        <div>
            <div class="decision-card">
                <h2 class="section-subtitle">Review Decision</h2>

                <?php if (in_array($invoice_data['invoice_status'], ['Submitted', 'Under Review'])): ?>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Review Remarks <span style="color:#dc2626;">*</span></label>
                    <textarea id="remarksInput" placeholder="Enter evaluation remarks or reason for rejection..."></textarea>
                </div>
                <button class="btn-approve" onclick="openModal('Verified')">&#10003; Approve Invoice</button>
                <button class="btn-reject"  onclick="openModal('Rejected')">&#10007; Reject Invoice</button>
                <a href="review_dashboard.php" class="btn-back">Cancel</a>

                <?php else: ?>
                <div class="already-reviewed">
                    <p>This invoice is currently <strong><?php echo htmlspecialchars($invoice_data['invoice_status']); ?></strong> and cannot be reviewed again.</p>
                    <?php if (!empty($invoice_data['reason'])): ?>
                    <div class="reason-box"><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($invoice_data['reason']); ?></div>
                    <?php endif; ?>
                </div>
                <br>
                <a href="review_dashboard.php" class="btn-back">Back to Dashboard</a>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- end grid -->

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-card">
            <div class="modal-icon" id="modalIcon">✓</div>
            <div class="modal-body">
                <div class="modal-title" id="modalTitle">Confirm Decision</div>
                <div class="modal-sub" id="modalSub">Are you sure?</div>
                <div class="modal-btns">
                    <button class="btn-modal-confirm" id="modalConfirmBtn">Confirm</button>
                    <button class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for submission -->
    <form id="decisionForm" method="POST" action="review_decision.php" style="display:none;">
        <input type="hidden" name="invoice_id"      value="<?php echo $invoice_data['invoice_ID']; ?>">
        <input type="hidden" name="action"           id="actionInput">
        <input type="hidden" name="review_remarks"   id="remarksHidden">
    </form>

    <footer class="system-footer">© 2026 KTMEDOIS INTEGRATED PORTAL</footer>
</main>

<script>
let pendingAction = '';

function openModal(action) {
    const remarks = document.getElementById('remarksInput').value.trim();
    if (!remarks) {
        document.getElementById('remarksInput').style.borderColor = '#dc2626';
        document.getElementById('remarksInput').focus();
        return;
    }
    document.getElementById('remarksInput').style.borderColor = '#cbd5e0';

    pendingAction = action;
    const isApprove = (action === 'Verified');
    document.getElementById('modalIcon').className = 'modal-icon ' + (isApprove ? 'icon-approve' : 'icon-reject');
    document.getElementById('modalIcon').textContent = isApprove ? '✓' : '✗';
    document.getElementById('modalTitle').textContent = isApprove ? 'Confirm Approval' : 'Confirm Rejection';
    document.getElementById('modalSub').textContent   = isApprove
        ? 'Approve this invoice and route to Finance Review queue?'
        : 'Reject this invoice? The vendor will be notified with your remarks.';
    document.getElementById('confirmModal').classList.add('active');
}

function closeModal() {
    document.getElementById('confirmModal').classList.remove('active');
}

document.getElementById('modalConfirmBtn').addEventListener('click', function() {
    document.getElementById('actionInput').value    = pendingAction;
    document.getElementById('remarksHidden').value  = document.getElementById('remarksInput').value;
    closeModal();
    document.getElementById('decisionForm').submit();
});
</script>
</body>
</html>
<?php $conn->close(); ?>
