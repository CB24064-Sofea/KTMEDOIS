<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/DOController.php";

$controller = new DOController($conn);
$feedback = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = $controller->handleSubmission($_POST, $_FILES);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KTM eDOIS - Submit Delivery Order</title>
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <style>
        * { font-family: 'Segoe UI', sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        .app-layout-wrapper { display: flex; flex-direction: column; width: 100%; height: 100vh; background-color: #f8fafc; }
        .lower-split-container { display: flex; flex-grow: 1; }
        .content-body { padding: 32px; overflow-y: auto; flex-grow: 1; }
        .form-card { background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; max-width: 600px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 20px; }
        label { font-size: 11px; font-weight: 700; color: #718096; text-transform: uppercase; }
        input { padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; }
        .btn-submit { background-color: #002D62; color: white; border: none; padding: 14px; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
        .alert-success { background-color: #c6f6d5; color: #22543d; border-left: 5px solid #48bb78; }
        .alert-error { background-color: #fed7d7; color: #742a2a; border-left: 5px solid #f56565; }
    </style>
</head>
<body>
<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>
    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>
        <div class="content-body">
            <h2 style="color: #002D62; margin-bottom: 20px;">Submit New Delivery Order</h2>
            
            <div class="form-card">
                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback['status']; ?>">
                        <?php echo htmlspecialchars($feedback['message']); ?>
                    </div>
                <?php endif; ?>

                <form action="do_submission.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Delivery Order (DO) Number *</label>
                        <input type="text" name="do_id" placeholder="e.g., DO002" required>
                    </div>
                    <div class="form-group">
                        <label>Associated Purchase Order (PO) ID *</label>
                        <input type="text" name="po_id" placeholder="e.g., PO001" required>
                    </div>
                    <div class="form-group">
                        <label>Supplier ID *</label>
                        <input type="text" name="supplier_id" placeholder="e.g., SUP001" required>
                    </div>
                    <div class="form-group">
                        <label>Project Reference (Optional)</label>
                        <input type="text" name="project_ref" placeholder="e.g., PRJ-KTM-001">
                    </div>
                    <div class="form-group">
                        <label>Proof of Delivery Document (PDF/Image) *</label>
                        <input type="file" name="proof_file" accept=".pdf,image/*" required>
                    </div>
                    <button type="submit" class="btn-submit">Submit to System</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>