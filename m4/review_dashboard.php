<?php
// =========================================================================
// FILE        : review_dashboard.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : SDD_CLS_401 — officerMainDashboardUI
// DESCRIPTION : Main dashboard for KTMB Procurement Officers. Displays all
//               incoming invoice claims with status filter and stat summary
//               cards. Officers click 'Review' to enter the review workspace.
// AUTHOR      : Module 4 Developer
// DATE        : June 2026
// =========================================================================

include 'db.php'; // Load shared database connection
$current_page = basename($_SERVER['PHP_SELF']); // Used by sidebar to highlight active link

// ── Stat counts — query each invoice status for the summary cards ─────────────
$total     = $conn->query("SELECT COUNT(*) as c FROM invoice")->fetch_assoc()['c'];
$submitted = $conn->query("SELECT COUNT(*) as c FROM invoice WHERE invoice_status='Submitted'")->fetch_assoc()['c'];
$reviewing = $conn->query("SELECT COUNT(*) as c FROM invoice WHERE invoice_status='Under Review'")->fetch_assoc()['c'];
$finance   = $conn->query("SELECT COUNT(*) as c FROM invoice WHERE invoice_status='Finance Review'")->fetch_assoc()['c'];
$rejected  = $conn->query("SELECT COUNT(*) as c FROM invoice WHERE invoice_status='Rejected'")->fetch_assoc()['c'];

// ── Filter logic — show all invoices or filter by specific status ─────────────
// $_GET['status'] comes from the stat card links at the top of the page
$filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build WHERE clause only if a specific status is requested
if ($filter !== 'all') {
    $safe_filter = $conn->real_escape_string($filter);
    $where = "WHERE i.invoice_status = '$safe_filter'";
} else {
    $where = ''; // No filter — show all invoices
}

// ── Main invoice query — fetch all invoices with optional status filter ───────
$sql = "SELECT i.invoice_ID, i.invoice_num, i.DO_ID, i.total,
               i.invoice_status, i.invoice_date
        FROM invoice i
        $where
        ORDER BY i.invoice_ID DESC"; // Most recent first
$result = $conn->query($sql);

// ── Decision feedback — show success alert after approve/reject action ────────
$decision        = isset($_GET['decision']) ? $_GET['decision'] : '';
$decision_action = isset($_GET['action'])   ? $_GET['action']   : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Portal - Review Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f3f5f9;
            --card-bg: #ffffff;
            --primary-navy: #002D62;
            --dark-gray: #1a1a1a;
            --border-color: #e2e8f0;
            --text-muted: #718096;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); display: flex; height: 100vh; overflow: hidden; color: #333; }
        .workspace { flex-grow: 1; padding: 40px; overflow-y: auto; width: 100%; display: flex; flex-direction: column; }
        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-title { font-size: 28px; font-weight: 700; color: var(--primary-navy); }
        .logo-container { height: 50px; display: flex; align-items: center; margin-left: auto; }
        .logo-container img { height: 100%; width: auto; object-fit: contain; }

        /* Alert / feedback messages */
        .alert { padding: 14px 18px; border-radius: 10px; font-size: 14px; font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger  { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* Stat cards row */
        .stats-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 30px; }
        .stat-card { background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border-color); padding: 20px; text-decoration: none; display: block; transition: box-shadow 0.2s; }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .stat-card.active-filter { border-color: var(--primary-navy); box-shadow: 0 0 0 2px rgba(0,45,98,0.2); }
        .stat-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .stat-number { font-size: 28px; font-weight: 700; }

        /* Table card */
        .card { background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border-color); padding: 24px; margin-bottom: 20px; }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background-color: #f8fafc; color: #4a5568; font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; padding: 16px; border-bottom: 2px solid var(--border-color); }
        td { padding: 16px; border-bottom: 1px solid var(--border-color); color: var(--dark-gray); vertical-align: middle; }
        tr:hover td { background-color: #f8fafc; }
        .empty-state { text-align: center; padding: 40px; color: var(--text-muted); font-size: 14px; }

        /* Status badges */
        .badge { display: inline-block; padding: 5px 12px; border-radius: 50px; font-size: 12px; font-weight: 600; }
        .s-submitted     { background-color: #eef2ff; color: #4f46e5; }
        .s-under-review  { background-color: #fef3c7; color: #d97706; }
        .s-finance-review{ background-color: #e0f2fe; color: #0369a1; }
        .s-approved      { background-color: #ecfdf5; color: #059669; }
        .s-rejected      { background-color: #fef2f2; color: #dc2626; }

        .btn-action { display: inline-block; background-color: #f1f5f9; color: var(--primary-navy); text-decoration: none; padding: 8px 14px; border-radius: 6px; font-weight: 600; font-size: 13px; border: 1px solid #cbd5e1; transition: all 0.2s; }
        .btn-action:hover { background-color: var(--primary-navy); color: #ffffff; }
        .text-bold { font-weight: 600; color: #000; }
        .system-footer { text-align: center; font-size: 11px; color: #a0aec0; margin-top: auto; padding-top: 40px; letter-spacing: 1px; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="workspace">

    <div class="header-area">
        <h1 class="header-title">Review Dashboard</h1>
        <div class="logo-container"><img src="ktmb_logo.jpg" alt="KTMB Logo"></div>
    </div>

    <?php
    // ── Feedback alert after approve/reject ───────────────────────────────────
    // Shows a colour-coded message to confirm the officer's action was saved
    if ($decision === 'success'):
        if ($decision_action === 'Verified'): ?>
            <div class="alert alert-success">&#10003; Invoice approved successfully. Routed to Finance Review queue. Vendor has been notified.</div>
        <?php elseif ($decision_action === 'Rejected'): ?>
            <div class="alert alert-danger">&#10007; Invoice rejected. Rejection reason has been saved and vendor notified.</div>
        <?php endif;
    endif; ?>

    <!-- Stat Cards — clicking filters the table below -->
    <div class="stats-row">
        <?php
        // Define each stat card: label, count value, filter key, colour
        $cards = [
            ['label' => 'Total Claims',   'val' => $total,     'key' => 'all',           'color' => '#002D62'],
            ['label' => 'Submitted',      'val' => $submitted, 'key' => 'Submitted',     'color' => '#4f46e5'],
            ['label' => 'Under Review',   'val' => $reviewing, 'key' => 'Under Review',  'color' => '#d97706'],
            ['label' => 'Finance Review', 'val' => $finance,   'key' => 'Finance Review','color' => '#0369a1'],
            ['label' => 'Rejected',       'val' => $rejected,  'key' => 'Rejected',      'color' => '#dc2626'],
        ];
        foreach ($cards as $c):
            // Highlight the active filter card with a border
            $active = ($filter === $c['key']) ? 'active-filter' : '';
        ?>
        <a href="review_dashboard.php?status=<?php echo urlencode($c['key']); ?>" class="stat-card <?php echo $active; ?>">
            <div class="stat-label"><?php echo $c['label']; ?></div>
            <div class="stat-number" style="color:<?php echo $c['color']; ?>"><?php echo $c['val']; ?></div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Invoice Claims Table -->
    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Invoice No.</th>
                        <th>DO Reference</th>
                        <th>Claim Amount</th>
                        <th>Invoice Date</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()):
                        // Build CSS class for status badge from invoice_status value
                        $slug = 's-' . strtolower(str_replace(' ', '-', $row['invoice_status']));
                    ?>
                    <tr>
                        <td><span class="text-bold"><?php echo htmlspecialchars($row['invoice_num']); ?></span></td>
                        <td><code><?php echo htmlspecialchars($row['DO_ID']); ?></code></td>
                        <td><span class="text-bold">MYR <?php echo number_format($row['total'], 2); ?></span></td>
                        <td><?php echo date('d M Y', strtotime($row['invoice_date'])); ?></td>
                        <td>
                            <span class="badge <?php echo $slug; ?>">
                                <?php echo htmlspecialchars($row['invoice_status']); ?>
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <!-- Links to review_workspace.php passing the invoice ID -->
                            <a href="review_workspace.php?id=<?php echo $row['invoice_ID']; ?>" class="btn-action">Review</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Empty state: shown when no invoices match the filter -->
                    <tr>
                        <td colspan="6" class="empty-state">
                            No invoices found for this filter. Try selecting a different status above.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="system-footer">© 2026 KTMEDOIS INTEGRATED PORTAL</footer>
</main>
</body>
</html>
<?php $conn->close(); // Always close DB connection when done ?>
