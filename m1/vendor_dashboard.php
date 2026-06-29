<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/VendorController.php";

// Initialize backend session guard engine 
$controller = new VendorController($conn);
$vendorProfile = $controller->enforceActiveSessionGuard($_SESSION['vendor_auth'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KTM eDOIS - Vendor Hub Dashboard</title>
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <style>
        * { font-family: 'Segoe UI', sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        .app-layout-wrapper { display: flex; flex-direction: column; width: 100%; height: 100vh; background-color: #f8fafc; }
        .lower-split-container { display: flex; flex-grow: 1; }
        .content-body { padding: 32px; overflow-y: auto; flex-grow: 1; }
        .profile-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; max-width: 700px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .info-row { display: grid; grid-template-columns: 200px 1fr; padding: 12px 0; border-bottom: 1px solid #edf2f7; }
        .info-label { font-weight: 700; color: #718096; text-transform: uppercase; font-size: 11px; }
        .info-value { font-size: 14px; color: #2d3748; }
        .badge-active { background-color: #c6f6d5; color: #22543d; padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 12px; }
        .shortcut-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 24px; max-width: 700px; }
        .shortcut-box { background: #002D62; color: white; padding: 20px; border-radius: 8px; text-decoration: none; font-weight: 600; text-align: center; transition: background 0.2s; }
        .shortcut-box:hover { background: #001937; }
    </style>
</head>
<body>

<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>

    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>

        <div class="content-body">
            <div style="margin-bottom: 24px;">
                <h2 style="color: #002D62;">Welcome, <?php echo htmlspecialchars($vendorProfile['company_name']); ?></h2>
                <p style="color: #718096; font-size: 14px;">Manage corporate registration parameters and logistics profiles.</p>
            </div>

            <div class="profile-card">
                <h3 style="color: #2d3748; font-size: 16px; margin-bottom: 16px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">Corporate Registry Entity Details</h3>
                
                <div class="info-row">
                    <span class="info-label">Supplier Tracking Number</span>
                    <span class="info-value" style="font-weight: 600; color: #002D62;"><?php echo htmlspecialchars($vendorProfile['supplier_ID']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Corporate Title</span>
                    <span class="info-value"><?php echo htmlspecialchars($vendorProfile['supplier_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Registered Contact Channel</span>
                    <span class="info-value"><?php echo htmlspecialchars($vendorProfile['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Telephone Network</span>
                    <span class="info-value"><?php echo htmlspecialchars($vendorProfile['phone'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row" style="border: none;">
                    <span class="info-label">Platform Operating Status</span>
                    <span class="info-value">
                        <span class="badge-active"><?php echo htmlspecialchars($vendorProfile['status']); ?></span>
                    </span>
                </div>
            </div>

            <div class="shortcut-grid">
                <a href="/KTMEDOIS/m2/do_submission.php" class="shortcut-box">🚀 Proceed to Submit New Delivery Order (M2)</a>
                <a href="/KTMEDOIS/m3/invoiceCreationUI.php" class="shortcut-box" style="background:#ff6600;">📄 Generate Claims Invoice Workspace (M3)</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>