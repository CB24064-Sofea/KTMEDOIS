<?php
// =========================================================================
// MODULE 4 — Generate Report (SRS Section 3.8 — <<extend>> from Module 4)
// =========================================================================
include 'db.php';
$current_page = basename($_SERVER['PHP_SELF']);

// Handle report generation
$report_data  = [];
$filter_applied = false;
$date_from = isset($_POST['date_from']) ? $_POST['date_from'] : '';
$date_to   = isset($_POST['date_to'])   ? $_POST['date_to']   : '';
$status    = isset($_POST['status'])    ? $_POST['status']    : 'all';
$export    = isset($_POST['export'])    ? $_POST['export']    : '';

if (isset($_POST['generate'])) {
    $filter_applied = true;
    $where_parts = [];

    if (!empty($date_from)) $where_parts[] = "i.invoice_date >= '" . $conn->real_escape_string($date_from) . " 00:00:00'";
    if (!empty($date_to))   $where_parts[] = "i.invoice_date <= '" . $conn->real_escape_string($date_to) . " 23:59:59'";
    if ($status !== 'all')  $where_parts[] = "i.invoice_status = '" . $conn->real_escape_string($status) . "'";

    $where = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

    $result = $conn->query(
        "SELECT i.invoice_num, i.DO_ID, i.total, i.invoice_status, i.invoice_date,
                s.supplier_name
         FROM invoice i
         INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
         INNER JOIN supplier s ON d.supplier_ID = s.supplier_ID
         $where
         ORDER BY i.invoice_date DESC"
    );

    if ($result) {
        while ($row = $result->fetch_assoc()) $report_data[] = $row;
    }

    // CSV/Excel export
    if ($export === 'excel' && !empty($report_data)) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="KTMeDOIS_Review_Report_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['No.','Invoice No.','DO Reference','Supplier','Amount (MYR)','Status','Date']);
        foreach ($report_data as $i => $row) {
            fputcsv($out, [
                $i+1,
                $row['invoice_num'],
                $row['DO_ID'],
                $row['supplier_name'],
                number_format($row['total'],2),
                $row['invoice_status'],
                date('d M Y', strtotime($row['invoice_date']))
            ]);
        }
        fclose($out);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Portal - Generate Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color:#f3f5f9; --card-bg:#ffffff; --primary-navy:#002D62; --dark-gray:#1a1a1a; --border-color:#e2e8f0; --text-muted:#718096; }
        * { box-sizing:border-box; margin:0; padding:0; font-family:'Inter',sans-serif; }
        body { background-color:var(--bg-color); display:flex; height:100vh; overflow:hidden; color:#333; }
        .workspace { flex-grow:1; padding:40px; overflow-y:auto; max-width:1200px; margin:0 auto; width:100%; display:flex; flex-direction:column; min-height:100vh; }
        .header-area { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
        .header-title { font-size:28px; font-weight:700; color:var(--primary-navy); }
        .logo-container { height:50px; display:flex; align-items:center; margin-left:auto; }
        .logo-container img { height:100%; width:auto; object-fit:contain; }
        .card { background:var(--card-bg); border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.02); border:1px solid var(--border-color); padding:28px; margin-bottom:24px; }
        .section-title { font-size:16px; font-weight:700; color:var(--dark-gray); margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color); }
        .filter-grid { display:grid; grid-template-columns:repeat(3,1fr) auto; gap:16px; align-items:end; }
        .form-group label { font-size:13px; font-weight:600; color:#4a5568; display:block; margin-bottom:6px; }
        .form-control { width:100%; padding:11px 14px; border:1px solid var(--border-color); border-radius:8px; font-size:14px; font-family:'Inter',sans-serif; outline:none; background:#fff; }
        .form-control:focus { border-color:var(--primary-navy); box-shadow:0 0 0 3px rgba(0,45,98,0.08); }
        .btn-generate { background:var(--primary-navy); color:#fff; border:none; padding:11px 24px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; white-space:nowrap; transition:opacity 0.2s; }
        .btn-generate:hover { opacity:0.9; }
        /* Status chips for filter */
        .status-chips { display:flex; gap:10px; flex-wrap:wrap; margin-top:4px; }
        .chip { padding:8px 16px; border-radius:6px; font-size:13px; font-weight:500; cursor:pointer; border:1px solid var(--border-color); background:#f8fafc; color:#4a5568; transition:all 0.2s; }
        .chip.active, .chip:hover { background:var(--primary-navy); color:#fff; border-color:var(--primary-navy); }
        /* Result section */
        .result-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
        .result-count { font-size:14px; color:var(--text-muted); }
        .export-btns { display:flex; gap:10px; }
        .btn-export { padding:9px 18px; border-radius:8px; font-weight:600; font-size:13px; cursor:pointer; border:1px solid; transition:all 0.2s; }
        .btn-pdf   { background:#fff; color:#dc2626; border-color:#dc2626; }
        .btn-pdf:hover   { background:#dc2626; color:#fff; }
        .btn-excel { background:#fff; color:#059669; border-color:#059669; }
        .btn-excel:hover { background:#059669; color:#fff; }
        .table-responsive { width:100%; overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        th { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:14px 16px; border-bottom:2px solid var(--border-color); }
        td { padding:14px 16px; border-bottom:1px solid var(--border-color); color:var(--dark-gray); vertical-align:middle; }
        tr:hover td { background:#f8fafc; }
        .text-bold { font-weight:600; }
        .badge { display:inline-block; padding:5px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .status-submitted    { background:#eef2ff; color:#4f46e5; }
        .status-under-review { background:#fef3c7; color:#d97706; }
        .status-finance-review { background:#e0f2fe; color:#0369a1; }
        .status-approved     { background:#ecfdf5; color:#059669; }
        .status-rejected     { background:#fef2f2; color:#dc2626; }
        .empty-state { text-align:center; padding:40px; color:var(--text-muted); font-size:14px; }
        /* Print area */
        @media print {
            .sidebar, .filter-card, .export-btns, .header-area .logo-container { display:none !important; }
            body { background:#fff; }
            .workspace { padding:0; }
            .card { box-shadow:none; border:none; }
            .print-header { display:block !important; text-align:center; margin-bottom:20px; }
        }
        .print-header { display:none; }
        .system-footer { text-align:center; font-size:11px; color:#a0aec0; margin-top:auto; padding-top:40px; letter-spacing:1px; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="workspace">
    <div class="header-area">
        <h1 class="header-title">Generate Report</h1>
        <div class="logo-container"><img src="ktmb_logo.jpg" alt="KTMB Logo"></div>
    </div>

    <!-- Filter Card -->
    <div class="card filter-card">
        <div class="section-title">Report Filters</div>
        <form method="POST" action="generate_report.php" id="reportForm">
            <div class="filter-grid">
                <div class="form-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="form-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="form-group">
                    <label>Claim Status</label>
                    <select name="status" class="form-control">
                        <option value="all"            <?php echo ($status==='all')?'selected':''; ?>>All Status</option>
                        <option value="Submitted"      <?php echo ($status==='Submitted')?'selected':''; ?>>Submitted</option>
                        <option value="Under Review"   <?php echo ($status==='Under Review')?'selected':''; ?>>Under Review</option>
                        <option value="Finance Review" <?php echo ($status==='Finance Review')?'selected':''; ?>>Finance Review</option>
                        <option value="Approved"       <?php echo ($status==='Approved')?'selected':''; ?>>Approved</option>
                        <option value="Rejected"       <?php echo ($status==='Rejected')?'selected':''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" name="generate" value="1" class="btn-generate">Generate Report</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Results -->
    <?php if ($filter_applied): ?>
    <div class="card">
        <!-- Print header (only shows when printing) -->
        <div class="print-header">
            <h2 style="color:#002D62;font-size:20px;">KERETAPI TANAH MELAYU BERHAD</h2>
            <h3 style="font-size:16px;margin-top:4px;">Internal Review & Approval Report</h3>
            <p style="font-size:12px;color:#718096;margin-top:6px;">
                Generated: <?php echo date('d M Y, H:i'); ?> |
                Status: <?php echo $status==='all'?'All':htmlspecialchars($status); ?> |
                Period: <?php echo $date_from?date('d M Y',strtotime($date_from)):'All'; ?> – <?php echo $date_to?date('d M Y',strtotime($date_to)):'All'; ?>
            </p>
            <hr style="margin:12px 0;">
        </div>

        <div class="result-header">
            <div class="result-count">
                <?php echo count($report_data); ?> record(s) found
                <?php if ($date_from || $date_to): ?>
                    | Period: <?php echo $date_from?date('d M Y',strtotime($date_from)):'Start'; ?> – <?php echo $date_to?date('d M Y',strtotime($date_to)):'Today'; ?>
                <?php endif; ?>
            </div>
            <?php if (!empty($report_data)): ?>
            <div class="export-btns">
                <button class="btn-export btn-pdf" onclick="window.print()">🖨 Print / Save PDF</button>
                <form method="POST" action="generate_report.php" style="display:inline;">
                    <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    <input type="hidden" name="date_to"   value="<?php echo htmlspecialchars($date_to); ?>">
                    <input type="hidden" name="status"    value="<?php echo htmlspecialchars($status); ?>">
                    <input type="hidden" name="generate"  value="1">
                    <input type="hidden" name="export"    value="excel">
                    <button type="submit" class="btn-export btn-excel">⬇ Export Excel</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($report_data)): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Invoice No.</th>
                        <th>DO Reference</th>
                        <th>Supplier</th>
                        <th>Amount (MYR)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($report_data as $i => $row):
                    $slug = 'status-' . strtolower(str_replace(' ','-',$row['invoice_status']));
                ?>
                <tr>
                    <td style="color:var(--text-muted);"><?php echo $i+1; ?></td>
                    <td><span class="text-bold"><?php echo htmlspecialchars($row['invoice_num']); ?></span></td>
                    <td><code><?php echo htmlspecialchars($row['DO_ID']); ?></code></td>
                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                    <td><span class="text-bold">MYR <?php echo number_format($row['total'],2); ?></span></td>
                    <td><span class="badge <?php echo $slug; ?>"><?php echo htmlspecialchars($row['invoice_status']); ?></span></td>
                    <td><?php echo date('d M Y', strtotime($row['invoice_date'])); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            ⚠ No data available for the selected filters.<br>
            <small style="color:#a0aec0;">Try adjusting the date range or status filter.</small>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <footer class="system-footer">© 2026 KTMEDOIS INTEGRATED PORTAL</footer>
</main>
</body>
</html>
<?php $conn->close(); ?>
