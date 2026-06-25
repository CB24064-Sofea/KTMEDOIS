<?php
// =========================================================================
// MODULE 4 — DO Details + Approve/Reject (SDD_CLS_402 extension)
// =========================================================================
include 'db.php';
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_GET['id']) || empty($_GET['id'])) { header("Location: do_list.php"); exit; }
$do_id = $conn->real_escape_string($_GET['id']);

$stmt = $conn->prepare(
    "SELECT d.*, s.supplier_name, s.phone, s.email, p.PO_amount
     FROM delivery_order d
     INNER JOIN supplier s ON d.supplier_ID = s.supplier_ID
     INNER JOIN purchase_order p ON d.PO_ID = p.PO_ID
     WHERE d.DO_ID = ? LIMIT 1"
);
$stmt->bind_param("s", $do_id);
$stmt->execute();
$do = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$do) { header("Location: do_list.php"); exit; }

// Get related audit history
$audit_res = $conn->query(
    "SELECT a.action, a.record_ID, a.timestamp, k.staff_name
     FROM audit_log a
     INNER JOIN ktmb_staff k ON a.staff_ID = k.staff_ID
     INNER JOIN invoice i ON a.invoice_ID = i.invoice_ID
     WHERE i.DO_ID = '$do_id'
     ORDER BY a.timestamp DESC LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Portal - DO Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color:#f3f5f9; --card-bg:#ffffff; --primary-navy:#002D62; --dark-gray:#1a1a1a; --border-color:#e2e8f0; --text-muted:#718096; }
        * { box-sizing:border-box; margin:0; padding:0; font-family:'Inter',sans-serif; }
        body { background-color:var(--bg-color); display:flex; height:100vh; overflow:hidden; color:#333; }
        .workspace { flex-grow:1; padding:40px; overflow-y:auto; max-width:1200px; margin:0 auto; width:100%; display:flex; flex-direction:column; min-height:100vh; }
        .header-area { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .header-title { font-size:28px; font-weight:700; color:var(--primary-navy); }
        .logo-container { height:50px; display:flex; align-items:center; margin-left:auto; }
        .logo-container img { height:100%; width:auto; object-fit:contain; }
        .btn-back { display:inline-block; background:#f1f5f9; color:#4a5568; text-decoration:none; padding:10px 18px; border-radius:8px; font-weight:600; font-size:13px; border:1px solid #cbd5e1; margin-bottom:24px; transition:all 0.2s; }
        .btn-back:hover { background:#e2e8f0; }
        .grid-layout { display:grid; grid-template-columns:1.4fr 1fr; gap:24px; align-items:start; }
        .card { background:var(--card-bg); border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.02); border:1px solid var(--border-color); padding:28px; margin-bottom:20px; }
        .section-title { font-size:16px; font-weight:700; color:var(--dark-gray); margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid var(--border-color); }
        .meta-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }
        .meta-item label { font-size:12px; color:var(--text-muted); display:block; margin-bottom:4px; font-weight:500; }
        .meta-item span { font-size:14px; color:var(--dark-gray); font-weight:600; }
        .doc-item { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:#f8fafc; border-radius:8px; border:1px solid var(--border-color); margin-bottom:10px; font-size:14px; }
        .btn-download { background:var(--primary-navy); color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; font-size:13px; font-weight:600; }
        .badge { display:inline-block; padding:6px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .status-approved { background:#ecfdf5; color:#059669; }
        .status-pending  { background:#fef3c7; color:#d97706; }
        .status-rejected { background:#fef2f2; color:#dc2626; }
        .remarks-label { font-size:13px; font-weight:600; color:#4a5568; margin-bottom:8px; display:block; }
        textarea { width:100%; padding:12px 14px; border:1px solid #cbd5e0; border-radius:8px; font-size:14px; background:#fafafa; resize:vertical; min-height:90px; font-family:'Inter',sans-serif; }
        textarea:focus { outline:none; border-color:var(--primary-navy); }
        .action-row { display:flex; gap:12px; margin-top:16px; }
        .btn-approve { background:#16a34a; color:#fff; border:none; padding:13px 24px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; flex:1; transition:opacity 0.2s; }
        .btn-approve:hover { opacity:0.9; }
        .btn-reject  { background:#dc2626; color:#fff; border:none; padding:13px 24px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; flex:1; transition:opacity 0.2s; }
        .btn-reject:hover  { opacity:0.9; }
        .already-done { background:#f8fafc; border-radius:8px; padding:18px; text-align:center; font-size:14px; color:#4a5568; }
        .timeline { padding-left:18px; border-left:2px solid var(--border-color); }
        .tl-item { position:relative; margin-bottom:14px; }
        .tl-dot { position:absolute; left:-23px; top:4px; width:10px; height:10px; background:var(--primary-navy); border-radius:50%; }
        .tl-action { font-size:13px; font-weight:600; }
        .tl-meta { font-size:12px; color:var(--text-muted); }
        /* Modal — same style as Module 3 */
        .modal-overlay { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center; z-index:9999; opacity:0; pointer-events:none; transition:opacity 0.3s; }
        .modal-overlay.active { opacity:1; pointer-events:auto; }
        .modal-card { background:#EAEAEA; width:420px; padding:36px 28px; border-radius:24px; box-shadow:0 10px 30px rgba(0,0,0,0.15); text-align:center; transform:scale(0.85); transition:transform 0.3s; }
        .modal-overlay.active .modal-card { transform:scale(1); }
        .modal-title { font-size:20px; font-weight:700; margin-bottom:8px; color:#000; }
        .modal-sub { font-size:14px; color:#4a5568; margin-bottom:22px; }
        .modal-btns { display:flex; gap:12px; justify-content:center; }
        .btn-modal-yes { background:#1e1e1e; color:#fff; border:none; padding:11px 28px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; }
        .btn-modal-no  { background:#D1CDCD; color:#000; border:1px solid #A6A2A2; padding:11px 24px; border-radius:10px; font-weight:600; font-size:14px; cursor:pointer; }
        .system-footer { text-align:center; font-size:11px; color:#a0aec0; margin-top:auto; padding-top:40px; letter-spacing:1px; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="workspace">
    <div class="header-area">
        <h1 class="header-title">Delivery Order Details</h1>
        <div class="logo-container"><img src="ktmb_logo.jpg" alt="KTMB Logo"></div>
    </div>
    <a href="do_list.php" class="btn-back">&#8592; Back to DO List</a>

    <div class="grid-layout">
        <!-- LEFT -->
        <div>
            <div class="card">
                <div class="section-title">DO Details</div>
                <div class="meta-grid">
                    <div class="meta-item"><label>DO Number</label><span><?php echo htmlspecialchars($do['DO_ID']); ?></span></div>
                    <div class="meta-item"><label>PO Number</label><span><?php echo htmlspecialchars($do['PO_ID']); ?></span></div>
                    <div class="meta-item"><label>Supplier</label><span><?php echo htmlspecialchars($do['supplier_name']); ?></span></div>
                    <div class="meta-item"><label>Submission Date</label><span><?php echo date('d M Y, H:i', strtotime($do['created_date'])); ?></span></div>
                    <div class="meta-item"><label>PO Amount</label><span>MYR <?php echo number_format($do['PO_amount'], 2); ?></span></div>
                    <div class="meta-item"><label>Project Reference</label><span><?php echo htmlspecialchars($do['project_reference'] ?? '-'); ?></span></div>
                    <div class="meta-item"><label>Current Status</label>
                        <span class="badge status-<?php echo strtolower($do['PO_status']); ?>"><?php echo htmlspecialchars($do['PO_status']); ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="section-title">Supporting Documents</div>
                <div class="doc-item">
                    <span>📄 <?php echo htmlspecialchars(basename($do['proof_of_delivery'])); ?></span>
                    <a href="../<?php echo htmlspecialchars($do['proof_of_delivery']); ?>" target="_blank" class="btn-download">Download</a>
                </div>
            </div>

            <?php if ($audit_res && $audit_res->num_rows > 0): ?>
            <div class="card">
                <div class="section-title">Related Audit History</div>
                <div class="timeline">
                    <?php while ($log = $audit_res->fetch_assoc()): ?>
                    <div class="tl-item">
                        <div class="tl-dot"></div>
                        <div class="tl-action"><?php echo htmlspecialchars($log['action']); ?></div>
                        <div class="tl-meta">By <?php echo htmlspecialchars($log['staff_name']); ?> · <?php echo date('d M Y, H:i', strtotime($log['timestamp'])); ?></div>
                        <div style="font-size:11px;color:#a0aec0;">TRX: <?php echo htmlspecialchars($log['record_ID']); ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Decision Panel -->
        <div>
            <div class="card" style="position:sticky;top:20px;">
                <div class="section-title">Review Decision</div>
                <?php if ($do['PO_status'] === 'Approved'): ?>
                    <div class="already-done">✅ This Delivery Order has already been <strong>Approved</strong>.</div>
                <?php elseif ($do['PO_status'] === 'Rejected'): ?>
                    <div class="already-done" style="color:#dc2626;">❌ This Delivery Order has been <strong>Rejected</strong>.</div>
                <?php else: ?>
                    <label class="remarks-label">Review Remarks <span style="color:#dc2626;">*</span></label>
                    <textarea id="remarksInput" placeholder="Enter your remarks before approving or rejecting..."></textarea>
                    <div class="action-row">
                        <button class="btn-approve" onclick="openModal('Approved')">✓ Approve DO</button>
                        <button class="btn-reject"  onclick="openModal('Rejected')">✗ Reject DO</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-card">
            <div class="modal-title" id="modalTitle">Confirm Decision</div>
            <div class="modal-sub" id="modalSub">Are you sure?</div>
            <div class="modal-btns">
                <button class="btn-modal-yes" id="modalYesBtn">Yes, Confirm</button>
                <button class="btn-modal-no"  onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>

    <form id="decisionForm" method="POST" action="do_decision.php" style="display:none;">
        <input type="hidden" name="do_id"         value="<?php echo htmlspecialchars($do['DO_ID']); ?>">
        <input type="hidden" name="action"         id="actionInput">
        <input type="hidden" name="review_remarks" id="remarksHidden">
    </form>

    <footer class="system-footer">© 2026 KTMEDOIS INTEGRATED PORTAL</footer>
</main>
<script>
let pendingAction = '';
function openModal(action) {
    const r = document.getElementById('remarksInput').value.trim();
    if (!r) { document.getElementById('remarksInput').style.borderColor='#dc2626'; document.getElementById('remarksInput').focus(); return; }
    document.getElementById('remarksInput').style.borderColor = '#cbd5e0';
    pendingAction = action;
    document.getElementById('modalTitle').textContent = (action==='Approved') ? 'Confirm Approval' : 'Confirm Rejection';
    document.getElementById('modalSub').textContent   = (action==='Approved')
        ? 'Approve this Delivery Order and forward to invoice processing?'
        : 'Reject this Delivery Order? The vendor will be notified.';
    document.getElementById('confirmModal').classList.add('active');
}
function closeModal() { document.getElementById('confirmModal').classList.remove('active'); }
document.getElementById('modalYesBtn').addEventListener('click', function() {
    document.getElementById('actionInput').value   = pendingAction;
    document.getElementById('remarksHidden').value = document.getElementById('remarksInput').value;
    closeModal();
    document.getElementById('decisionForm').submit();
});
</script>
</body>
</html>
<?php $conn->close(); ?>
