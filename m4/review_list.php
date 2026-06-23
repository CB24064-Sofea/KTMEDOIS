<?php
// =========================================================================
// 1. DATABASE CONNECTION
// =========================================================================
include 'db.php';

// =========================================================================
// 2. FETCH STAT COUNTS — SDD_CLS_401 officerMainDashboardUI
// =========================================================================
$count_all        = $conn->query("SELECT COUNT(*) as c FROM invoice")->fetch_assoc()['c'];
$count_submitted  = $conn->query("SELECT COUNT(*) as c FROM invoice WHERE invoice_status = 'Submitted'")->fetch_assoc()['c'];
$count_review     = $conn->query("SELECT COUNT(*) as c FROM invoice WHERE invoice_status = 'Under Review'")->fetch_assoc()['c'];
$count_finance    = $conn->query("SELECT COUNT(*) as c FROM invoice WHERE invoice_status = 'Finance Review'")->fetch_assoc()['c'];
$count_approved   = $conn->query("SELECT COUNT(*) as c FROM invoice WHERE invoice_status = 'Approved'")->fetch_assoc()['c'];
$count_rejected   = $conn->query("SELECT COUNT(*) as c FROM invoice WHERE invoice_status = 'Rejected'")->fetch_assoc()['c'];

// =========================================================================
// 3. FILTER LOGIC
// =========================================================================
$filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$where = "";
if ($filter !== 'all') {
    $safe = $conn->real_escape_string($filter);
    $where = "WHERE i.invoice_status = '$safe'";
}

// =========================================================================
// 4. FETCH INVOICES
// =========================================================================
$sql = "SELECT i.invoice_ID, i.invoice_num, i.DO_ID, i.total,
               i.invoice_status, i.invoice_date,
               d.supplier_ID
        FROM invoice i
        LEFT JOIN delivery_order d ON i.DO_ID = d.DO_ID
        $where
        ORDER BY i.invoice_ID DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Portal - Internal Review</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f3f5f9;
            --card-bg: #ffffff;
            --primary-navy: #002D62;
            --dark-gray: #1a1a1a;
            --border-color: #e2e8f0;
            --status-submitted: #eef2ff; --text-submitted: #4f46e5;
            --status-review: #fef3c7;    --text-review: #d97706;
            --status-approved: #ecfdf5;  --text-approved: #059669;
            --status-rejected: #fef2f2;  --text-rejected: #dc2626;
            --status-finance: #e0f2fe;   --text-finance: #0284c7;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); display: flex; height: 100vh; overflow: hidden; color: #333; }

        .workspace { flex-grow: 1; padding: 40px; overflow-y: auto; max-width: 1200px; margin: 0 auto; width: 100%; display: flex; flex-direction: column; min-height: 100vh; }

        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-title { font-size: 28px; font-weight: 700; color: var(--primary-navy); }
        .logo-container { height: 50px; display: flex; align-items: center; margin-left: auto; }
        .logo-container img { height: 100%; width: auto; object-fit: contain; }

        /* Stat cards */
        .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 28px; }
        .stat-card { background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border-color); padding: 18px; }
        .stat-label { font-size: 11px; color: #718096; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
        .stat-number { font-size: 28px; font-weight: 700; }

        /* Filter buttons */
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        .filter-label { font-size: 13px; font-weight: 600; color: #4a5568; }
        .filter-btn { padding: 8px 16px; border-radius: 6px; border: 1px solid var(--border-color); background: white; font-size: 13px; font-weight: 500; color: #4a5568; text-decoration: none; transition: all .2s; }
        .filter-btn:hover { background: #f1f5f9; }
        .filter-btn.active { background: #1e1e1e; color: white; border-color: #1e1e1e; }

        /* Table */
        .card { background: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); border: 1px solid var(--border-color); padding: 24px; margin-bottom: 20px; }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th { background-color: #f8fafc; color: #4a5568; font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; padding: 16px; border-bottom: 2px solid var(--border-color); }
        td { padding: 16px; border-bottom: 1px solid var(--border-color); color: var(--dark-gray); vertical-align: middle; }
        tr:hover td { background-color: #f8fafc; }

        .badge { display: inline-block; padding: 6px 12px; border-radius: 50px; font-size: 12px; font-weight: 600; }
        .status-submitted     { background: var(--status-submitted); color: var(--text-submitted); }
        .status-under-review  { background: var(--status-review);    color: var(--text-review); }
        .status-finance-review{ background: var(--status-finance);   color: var(--text-finance); }
        .status-approved      { background: var(--status-approved);  color: var(--text-approved); }
        .status-rejected      { background: var(--status-rejected);  color: var(--text-rejected); }

        .btn-action { display: inline-block; background-color: #f1f5f9; color: var(--primary-navy); text-decoration: none; padding: 8px 14px; border-radius: 6px; font-weight: 600; font-size: 13px; border: 1px solid #cbd5e1; transition: all .2s; }
        .btn-action:hover { background-color: var(--primary-navy); color: #ffffff; border-color: var(--primary-navy); }

        .text-bold { font-weight: 600; color: #000; }
        .system-footer { text-align: center; font-size: 11px; color: #a0aec0; margin-top: auto; padding-top: 40px; letter-spacing: 1px; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="workspace">

        <div class="header-area">
            <h1 class="header-title">Internal Review & Approval</h1>
            <div class="logo-container">
                <img src="../m3/ktmb_logo.jpg" alt="KTMB Corporate Logo">
            </div>
        </div>

        <!-- Stat Cards — SDD_CLS_401 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Claims</div>
                <div class="stat-number" style="color:#002D62;"><?php echo $count_all; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Submitted</div>
                <div class="stat-number" style="color:#4f46e5;"><?php echo $count_submitted; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Under Review</div>
                <div class="stat-number" style="color:#d97706;"><?php echo $count_review; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Approved</div>
                <div class="stat-number" style="color:#059669;"><?php echo $count_approved; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Rejected</div>
                <div class="stat-number" style="color:#dc2626;"><?php echo $count_rejected; ?></div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <span class="filter-label">Filter:</span>
            <?php
            $filters = ['all' => 'All', 'Submitted' => 'Submitted', 'Under Review' => 'Under Review', 'Finance Review' => 'Finance Review', 'Approved' => 'Approved', 'Rejected' => 'Rejected'];
            foreach ($filters as $val => $label):
                $active = ($filter === $val) ? 'active' : '';
            ?>
                <a href="review_list.php?status=<?php echo urlencode($val); ?>" class="filter-btn <?php echo $active; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Invoice Table -->
        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice No.</th>
                            <th>DO No.</th>
                            <th>Supplier</th>
                            <th>Invoice Date</th>
                            <th>Claim Amount</th>
                            <th>Status</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php $slug = 'status-' . strtolower(str_replace(' ', '-', $row['invoice_status'])); ?>
                                <tr>
                                    <td><span class="text-bold"><?php echo htmlspecialchars($row['invoice_num']); ?></span></td>
                                    <td><code><?php echo htmlspecialchars($row['DO_ID']); ?></code></td>
                                    <td><?php echo htmlspecialchars($row['supplier_ID'] ?? '-'); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['invoice_date'])); ?></td>
                                    <td><span class="text-bold">MYR <?php echo number_format($row['total'], 2); ?></span></td>
                                    <td><span class="badge <?php echo $slug; ?>"><?php echo htmlspecialchars($row['invoice_status']); ?></span></td>
                                    <td style="text-align:center;">
                                        <a href="review_workspace.php?id=<?php echo $row['invoice_ID']; ?>" class="btn-action">Review</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:30px; color:#718096;">No invoices found.</td>
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
<?php $conn->close(); ?>
