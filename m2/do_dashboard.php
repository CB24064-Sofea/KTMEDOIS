<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . "/db.php";

// 📊 1. METRICS COUNTERS
// Your table doesn't have a specific DO status column yet, so we safely handle the queries.
$total_query = "SELECT COUNT(*) as total FROM delivery_order";

$total_all = $conn->query($total_query)->fetch_assoc()['total'] ?? 0;

// Since your sql schema currently maps 'PO_status' inside delivery_order, let's look for that 
// or set clean fallbacks so your counters display safely instead of throwing errors.
$total_submitted = 0;
$total_review    = 0;
$total_approved  = 0;
$total_rejected  = 0;

// Look up counts checking if PO_status matches any tracking filters
$check_po_status = $conn->query("SELECT PO_status, COUNT(*) as count FROM delivery_order GROUP BY PO_status");
if ($check_po_status) {
    while ($row = $check_po_status->fetch_assoc()) {
        if ($row['PO_status'] == 'Approved') {
            $total_approved = $row['count'];
        } else if ($row['PO_status'] == 'Pending') {
            $total_submitted = $row['count'];
        }
    }
}

// 📑 2. FETCH DATA ROWS (Ordered by your actual 'created_date' column)
$table_query = "SELECT * FROM delivery_order ORDER BY created_date DESC";
$table_result = $conn->query($table_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS - DO Tracking Dashboard</title>
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <style>
        * { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        .app-layout-wrapper { display: flex; flex-direction: column; width: 100%; height: 100vh; overflow: hidden; background-color: #f8fafc; }
        .lower-split-container { display: flex; flex-grow: 1; width: 100%; overflow: hidden; }
        .content-body { padding: 32px; overflow-y: auto; flex-grow: 1; }

        .metrics-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 30px; }
        .metric-card { background: #ffffff; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 6px; }
        .metric-title { font-size: 11px; font-weight: 700; color: #a0aec0; text-transform: uppercase; }
        .metric-value { font-size: 22px; font-weight: 700; color: #1a202c; }
        
        .card-total { border-left: 4px solid #002D62; }
        .card-submitted { border-left: 4px solid #3182ce; }
        .card-review { border-left: 4px solid #ecc94b; }
        .card-approved { border-left: 4px solid #48bb78; }
        .card-rejected { border-left: 4px solid #f56565; }

        .table-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background-color: #f8fafc; padding: 14px 24px; font-size: 12px; font-weight: 700; color: #718096; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        td { padding: 16px 24px; font-size: 14px; color: #2d3748; border-bottom: 1px solid #edf2f7; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-submitted { background-color: #ebf8ff; color: #2b6cb0; }
        .badge-under_review { background-color: #fefcbf; color: #b7791f; }
        .badge-approved { background-color: #c6f6d5; color: #22543d; }
        .badge-rejected { background-color: #fed7d7; color: #742a2a; }
    </style>
</head>
<body>

<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>

    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>

        <div class="content-body">
            <div class="metrics-grid">
                <div class="metric-card card-total">
                    <span class="metric-title">Total Logs</span>
                    <span class="metric-value"><?php echo $total_all; ?></span>
                </div>
                <div class="metric-card card-submitted">
                    <span class="metric-title">Submitted</span>
                    <span class="metric-value"><?php echo $total_submitted; ?></span>
                </div>
                <div class="metric-card card-review">
                    <span class="metric-title">Under Review</span>
                    <span class="metric-value"><?php echo $total_review; ?></span>
                </div>
                <div class="metric-card card-approved">
                    <span class="metric-title">Approved</span>
                    <span class="metric-value"><?php echo $total_approved; ?></span>
                </div>
                <div class="metric-card card-rejected">
                    <span class="metric-title">Rejected</span>
                    <span class="metric-value"><?php echo $total_rejected; ?></span>
                </div>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>DO Number</th>
                            <th>Purchase Order</th>
                            <th>Destination Unit</th>
                            <th>Delivery Date</th>
                            <th>Verification Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($table_result && $table_result->num_rows > 0): ?>
                            <?php while($row = $table_result->fetch_assoc()): 
                                // Safely fetch validation status using your PO_status values 
                                $display_status = !empty($row['PO_status']) ? $row['PO_status'] : 'Submitted';
                                if ($display_status === 'Pending') { $display_status = 'Submitted'; }
                                $badge_class = strtolower(str_replace(' ', '_', $display_status));
                            ?>
                                <tr>
                                    <td style="font-weight: 600; color: #002D62;"><?php echo htmlspecialchars($row['DO_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['PO_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_ID']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['created_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($display_status); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #a0aec0; padding: 40px 0;">No delivery orders found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>