<?php
// =========================================================================
// FILE        : generate_report.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : SDD_CLS_404 — generateReportUI
// DESCRIPTION : Management report generator. Uses OOP InvoiceModel to query
//               filtered invoice/claim records and renders a formal report
//               with summary statistics, a printable PDF layout, and a CSV
//               (Excel-compatible) export.
//               - selectCriteria()  → date range + status filter form
//               - generateReport()  → InvoiceModel.fetchReportData()
//               - exportPDF()       → window.print() with print stylesheet
//               - exportExcel()     → CSV download
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/InvoiceModel.php";

// ── Session guard ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['staff_auth'])) {
    header("Location: " . app_url('m1/staff_login.php')); exit;
}
$staffName = htmlspecialchars($_SESSION['staff_auth']['name'] ?? 'Staff');

$dateFrom      = isset($_POST['date_from']) ? $_POST['date_from'] : '';
$dateTo        = isset($_POST['date_to'])   ? $_POST['date_to']   : '';
$status        = isset($_POST['status'])    ? $_POST['status']    : 'all';
$export        = isset($_POST['export'])    ? $_POST['export']    : '';
$filterApplied = false;
$reportData    = [];
$model         = new InvoiceModel($conn);

if (isset($_POST['generate'])) {
    $filterApplied = true;
    $reportData = $model->fetchReportData($dateFrom, $dateTo, $status);   // OOP call

    // ── SDD_CLS_404: exportExcel() — CSV download ─────────────────────────
    if ($export === 'excel' && !empty($reportData)) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="KTMeDOIS_Report_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['No.','Invoice No.','DO Reference','Supplier','Amount (RM)','Status','Payment Status','Date']);
        foreach ($reportData as $i => $row) {
            fputcsv($out, [
                $i + 1,
                $row['invoice_num'],
                $row['DO_ID'],
                $row['supplier_name'],
                number_format($row['total'], 2),
                $row['invoice_status'],
                $row['payment_status'] ?? '—',
                date('d M Y', strtotime($row['invoice_date']))
            ]);
        }
        fclose($out);
        exit;
    }
}

// Summary calculations for the applied filter
$reportTotal = 0;
$reportCounts = ['Submitted'=>0,'Under Review'=>0,'Finance Review'=>0,'Approved'=>0,'Rejected'=>0];
foreach ($reportData as $r) {
    $reportTotal += (float)$r['total'];
    if (isset($reportCounts[$r['invoice_status']])) $reportCounts[$r['invoice_status']]++;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS – Generate Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/KTMEDOIS-main/sidebar.css">
    <style>
        .app-layout-wrapper    { display:flex; flex-direction:column; width:100%; height:100vh; background:#f3f5f9; }
        .lower-split-container { display:flex; flex-grow:1; overflow:hidden; }
        .content-body          { flex-grow:1; padding:36px; overflow-y:auto; font-family:'Inter',sans-serif; color:#333; }

        :root { --navy:#002D62; --border:#e2e8f0; --muted:#718096; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-title  { font-size:26px; font-weight:700; color:var(--navy); }

        .card        { background:#fff; border-radius:10px; border:1px solid var(--border); padding:26px; margin-bottom:22px; }
        .card-title  { font-size:15px; font-weight:700; color:#1a1a1a; margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid var(--border); }

        /* ── FILTER FORM ───────────────────────────────────────────────── */
        .filter-grid { display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:16px; align-items:end; }
        .form-group label { font-size:13px; font-weight:600; color:#4a5568; display:block; margin-bottom:6px; }
        .form-control { width:100%; padding:10px 13px; border:1px solid var(--border); border-radius:7px; font-size:14px; font-family:'Inter',sans-serif; outline:none; background:#fff; }
        .form-control:focus { border-color:var(--navy); box-shadow:0 0 0 3px rgba(0,45,98,0.1); }

        .btn-generate { background:var(--navy); color:#fff; border:none; padding:10px 22px; border-radius:7px; font-weight:700; font-size:14px; cursor:pointer; white-space:nowrap; transition:opacity 0.2s; }
        .btn-generate:hover { opacity:0.88; }

        /* ── SUMMARY STAT STRIP ───────────────────────────────────────── */
        .summary-strip  { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:22px; }
        .summary-card    { background:#fff; border-radius:10px; border:1px solid var(--border); padding:18px; }
        .summary-label   { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.6px; margin-bottom:8px; font-weight:600; }
        .summary-value   { font-size:24px; font-weight:700; color:var(--navy); }

        .result-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px; }
        .result-count  { font-size:14px; color:var(--muted); }
        .export-btns   { display:flex; gap:10px; }

        .btn-export    { padding:8px 16px; border-radius:7px; font-weight:600; font-size:13px; cursor:pointer; border:1px solid; background:#fff; transition:all 0.2s; }
        .btn-pdf       { color:#dc2626; border-color:#dc2626; }
        .btn-pdf:hover { background:#dc2626; color:#fff; }
        .btn-excel     { color:#059669; border-color:#059669; }
        .btn-excel:hover { background:#059669; color:#fff; }

        table  { width:100%; border-collapse:collapse; font-size:14px; }
        th     { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:13px 16px; border-bottom:2px solid var(--border); text-align:left; }
        td     { padding:13px 16px; border-bottom:1px solid var(--border); color:#1a1a1a; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#f8fafc; }

        tfoot td      { font-weight:700; background:#f8fafc; border-top:2px solid var(--border); }

        .badge                { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .status-submitted     { background:#eef2ff; color:#4f46e5; }
        .status-under-review  { background:#fef3c7; color:#d97706; }
        .status-finance-review{ background:#e0f2fe; color:#0369a1; }
        .status-approved      { background:#ecfdf5; color:#059669; }
        .status-rejected      { background:#fef2f2; color:#dc2626; }

        .empty-cell  { text-align:center; padding:40px; color:var(--muted); font-size:14px; }
        .system-footer { text-align:center; font-size:11px; color:#a0aec0; padding-top:32px; letter-spacing:1px; }

        /* ── PRINT STYLES — SDD_CLS_404 exportPDF() via window.print() ──── */
        @media print {
            .sidebar, .lower-split-container > :first-child,
            .filter-card, .export-btns, .topbar { display:none !important; }
            body, .app-layout-wrapper, .lower-split-container, .content-body { display:block !important; height:auto !important; overflow:visible !important; padding:0 !important; background:#fff !important; }
            .print-header { display:block !important; }
            .summary-strip { grid-template-columns:repeat(4,1fr) !important; }
            .card { border:none !important; box-shadow:none !important; }
        }
        .print-header { display:none; text-align:center; margin-bottom:20px; }
    </style>
</head>
<body>
<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>
    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>
        <div class="content-body">

            <div class="page-header">
                <h1 class="page-title">&#128202; Generate Management Report</h1>
            </div>

            <!-- ── SDD_CLS_404: selectCriteria() — filter form ─────────────── -->
            <div class="card filter-card">
                <div class="card-title">Report Filters</div>
                <form method="POST" action="generate_report.php">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        <div class="form-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                        <div class="form-group">
                            <label>Claim Status</label>
                            <select name="status" class="form-control">
                                <option value="all"            <?php echo $status==='all'?'selected':''; ?>>All Status</option>
                                <option value="Submitted"      <?php echo $status==='Submitted'?'selected':''; ?>>Submitted</option>
                                <option value="Under Review"   <?php echo $status==='Under Review'?'selected':''; ?>>Under Review</option>
                                <option value="Finance Review" <?php echo $status==='Finance Review'?'selected':''; ?>>Finance Review</option>
                                <option value="Approved"       <?php echo $status==='Approved'?'selected':''; ?>>Approved</option>
                                <option value="Rejected"       <?php echo $status==='Rejected'?'selected':''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="generate" value="1" class="btn-generate">&#128200; Generate</button>
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($filterApplied): ?>
            <div class="card">

                <!-- Print header — only shows when printing -->
                <div class="print-header">
                    <h2 style="color:#002D62;font-size:20px;">KERETAPI TANAH MELAYU BERHAD</h2>
                    <h3 style="font-size:16px;margin-top:4px;">Internal Review &amp; Approval — Management Report</h3>
                    <p style="font-size:12px;color:#718096;margin-top:6px;">
                        Generated by: <?php echo $staffName; ?> on <?php echo date('d M Y, H:i'); ?> |
                        Status: <?php echo $status==='all'?'All':htmlspecialchars($status); ?> |
                        Period: <?php echo $dateFrom?date('d M Y',strtotime($dateFrom)):'All dates'; ?> – <?php echo $dateTo?date('d M Y',strtotime($dateTo)):'Today'; ?>
                    </p>
                    <hr style="margin:12px 0;">
                </div>

                <!-- ── Summary stat strip ─────────────────────────────────── -->
                <div class="summary-strip">
                    <div class="summary-card">
                        <div class="summary-label">Total Records</div>
                        <div class="summary-value"><?php echo count($reportData); ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Total Claim Value</div>
                        <div class="summary-value">RM <?php echo number_format($reportTotal, 2); ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Approved</div>
                        <div class="summary-value" style="color:#059669;"><?php echo $reportCounts['Approved']; ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Rejected</div>
                        <div class="summary-value" style="color:#dc2626;"><?php echo $reportCounts['Rejected']; ?></div>
                    </div>
                </div>

                <div class="result-header">
                    <div class="result-count"><?php echo count($reportData); ?> record(s) found</div>
                    <?php if (!empty($reportData)): ?>
                    <div class="export-btns">
                        <!-- SDD_CLS_404: exportPDF() -->
                        <button class="btn-export btn-pdf" onclick="window.print()">&#128424; Print / PDF</button>
                        <!-- SDD_CLS_404: exportExcel() -->
                        <form method="POST" action="generate_report.php" style="display:inline;">
                            <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            <input type="hidden" name="date_to"   value="<?php echo htmlspecialchars($dateTo); ?>">
                            <input type="hidden" name="status"    value="<?php echo htmlspecialchars($status); ?>">
                            <input type="hidden" name="generate"  value="1">
                            <input type="hidden" name="export"    value="excel">
                            <button type="submit" class="btn-export btn-excel">&#11015; Export Excel (CSV)</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($reportData)): ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Invoice No.</th>
                                <th>DO Reference</th>
                                <th>Supplier</th>
                                <th>Amount (RM)</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reportData as $i => $row):
                            $slug = 'status-' . strtolower(str_replace(' ','-',$row['invoice_status']));
                        ?>
                        <tr>
                            <td style="color:var(--muted);"><?php echo $i+1; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['invoice_num']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($row['DO_ID']); ?></code></td>
                            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                            <td><strong>RM <?php echo number_format($row['total'],2); ?></strong></td>
                            <td><span class="badge <?php echo $slug; ?>"><?php echo htmlspecialchars($row['invoice_status']); ?></span></td>
                            <td style="font-size:13px;color:var(--muted);"><?php echo htmlspecialchars($row['payment_status'] ?? '—'); ?></td>
                            <td><?php echo date('d M Y', strtotime($row['invoice_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align:right;">Total</td>
                                <td colspan="4"><strong>RM <?php echo number_format($reportTotal,2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <!-- SDD_CLS_404: RQ41 No Data Available -->
                <div class="empty-cell">&#9888; No records found for the selected filters. Try adjusting the date range or status.</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="system-footer">© <?php echo date('Y'); ?> Keretapi Tanah Melayu Berhad &nbsp;|&nbsp; KTM eDOIS Internal Review Module</div>

        </div><!-- /content-body -->
    </div><!-- /lower-split-container -->
</div><!-- /app-layout-wrapper -->
</body>
</html>
<?php $conn->close(); ?>
