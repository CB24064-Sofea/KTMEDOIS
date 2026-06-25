<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/DOModel.php";

$model = new DOModel($conn);
$metrics = $model->getDashboardMetrics();
$table_result = $model->getAllDeliveryOrders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KTM eDOIS - Dashboard</title>
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <style>
        * { font-family: 'Segoe UI', sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        .app-layout-wrapper { display: flex; flex-direction: column; width: 100%; height: 100vh; background-color: #f8fafc; }
        .lower-split-container { display: flex; flex-grow: 1; }
        .content-body { padding: 32px; overflow-y: auto; flex-grow: 1; }
        .metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 30px; }
        .metric-card { background: #ffffff; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #002D62; }
        .table-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background-color: #f8fafc; padding: 14px 24px; font-size: 12px; color: #718096; border-bottom: 1px solid #e2e8f0; text-transform: uppercase;}
        td { padding: 16px 24px; font-size: 14px; border-bottom: 1px solid #edf2f7; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-approved { background-color: #c6f6d5; color: #22543d; }
        .badge-pending { background-color: #ebf8ff; color: #2b6cb0; }
    </style>
</head>
<body>
<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>
    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>
        <div class="content-body">
            <h2 style="color: #002D62; margin-bottom: 20px;">Module 2 Delivery Order Workspace</h2>
            
            <div class="metrics-grid">
                <div class="metric-card">
                    <div style="font-size: 12px; color: #718096; font-weight: bold;">TOTAL DELIVERIES</div>
                    <div style="font-size: 24px; font-weight: bold;"><?php echo $metrics['total']; ?></div>
                </div>
                <div class="metric-card" style="border-left-color: #48bb78;">
                    <div style="font-size: 12px; color: #718096; font-weight: bold;">APPROVED STATUS</div>
                    <div style="font-size: 24px; font-weight: bold;"><?php echo $metrics['approved']; ?></div>
                </div>
                <div class="metric-card" style="border-left-color: #3182ce;">
                    <div style="font-size: 12px; color: #718096; font-weight: bold;">PENDING REVIEWS</div>
                    <div style="font-size: 24px; font-weight: bold;"><?php echo $metrics['pending']; ?></div>
                </div>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>DO Number</th>
                            <th>PO ID</th>
                            <th>Customer Code</th>
                            <th>Verification Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($table_result && $table_result->num_rows > 0): ?>
                            <?php while($row = $table_result->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 600; color: #002D62;"><?php echo htmlspecialchars($row['DO_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['PO_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_ID']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($row['PO_status']); ?>">
                                            <?php echo htmlspecialchars($row['PO_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #a0aec0; padding: 30px;">No records located.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>