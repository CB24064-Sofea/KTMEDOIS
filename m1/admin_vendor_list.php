<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/VendorModel.php";

$model = new VendorModel($conn);
$search = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : '';
$supplier_list = $model->filterSuppliers($search);
$metrics = $model->getSystemStatusSummary();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KTM eDOIS - Admin Vendor Registry</title>
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <style>
        * { font-family: 'Segoe UI', sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        .app-layout-wrapper { display: flex; flex-direction: column; width: 100%; height: 100vh; background-color: #f8fafc; }
        .lower-split-container { display: flex; flex-grow: 1; }
        .content-body { padding: 32px; overflow-y: auto; flex-grow: 1; }
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #ffffff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; border-left: 4px solid #48bb78; }
        .search-bar { padding: 10px; width: 300px; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .table-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 14px 24px; text-align: left; font-size: 11px; color: #718096; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;}
        td { padding: 16px 24px; font-size: 14px; border-bottom: 1px solid #edf2f7; }
    </style>
</head>
<body>
<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>
    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>
        <div class="content-body">
            <h2 style="color:#002D62; margin-bottom: 20px;">Internal Vendor Registry Master Directory</h2>

            <div class="stats-row">
                <div class="stat-card"><strong>Active:</strong> <?php echo $metrics['Active']; ?></div>
                <div class="stat-card" style="border-left-color: #ecc94b;"><strong>Restricted:</strong> <?php echo $metrics['Restricted']; ?></div>
                <div class="stat-card" style="border-left-color: #f56565;"><strong>Inactive:</strong> <?php echo $metrics['Inactive']; ?></div>
            </div>

            <form action="admin_vendor_list.php" method="GET">
                <input type="text" name="search_keyword" class="search-bar" placeholder="Search Title, Company or ID..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" style="padding:10px 16px; background:#002D62; color:white; border:none; border-radius:6px; cursor:pointer;">Filter</button>
            </form>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Supplier ID</th>
                            <th>Company Name</th>
                            <th>Email Anchor</th>
                            <th>Status State</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($supplier_list && $supplier_list->num_rows > 0): ?>
                            <?php while($row = $supplier_list->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight:600; color:#002D62;"><?php echo htmlspecialchars($row['supplier_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['status']); ?></strong></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding:30px; color:#a0aec0;">No registry data matches query criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>