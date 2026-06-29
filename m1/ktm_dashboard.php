<?php
// ==========================================
// [SESSION INITIALIZATION & SECURITY CHECK]
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php'; // Ensure your connection path is correct
require_once __DIR__ . '/StaffController.php';

// Initialize MVC components
$staffController = new StaffController($conn);

// ✅ FIX 1: Guard the page using the controller. This verifies database state instantly.
// If the session array doesn't exist, the controller handles the redirection automatically.
$currentStaff = $staffController->enforceActiveSessionGuard($_SESSION['staff_auth'] ?? []);

$sub_role  = $currentStaff['role'];
$staffName = $currentStaff['name'];

$isAdministrator = ($sub_role === 'Administrator');
$isFinanceStaff  = in_array($sub_role, ['Administrator', 'Finance Officer', 'Finance Reviewer'], true);
$isProcurementStaff = in_array($sub_role, ['Administrator', 'Procurement Officer', 'KTM Staff'], true);

$_SESSION['staff_auth'] = [
    "staff_id" => $currentStaff['staff_ID'],
    "name"     => $currentStaff['name'],
    "email"    => $currentStaff['email'],
    "sub_role" => $currentStaff['role'],
    "role"     => $currentStaff['role']
];
$_SESSION['staff_id'] = $currentStaff['staff_ID'];
$_SESSION['user_id'] = $currentStaff['staff_ID'];
$_SESSION['current_module'] = 'staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTMB Staff Dashboard - eDOIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ktm-blue: #002B49;
            --ktm-orange: #ff6600;
        }
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 70px; /* Prevents fixed footer from overlapping content */
        }
        .navbar-ktm {
            background-color: var(--ktm-blue);
            border-bottom: 4px solid var(--ktm-orange);
        }
        .role-badge {
            font-size: 0.85rem;
            padding: 0.4em 0.8em;
            border-radius: 20px;
        }
        .card-custom {
            border: none;
            border-radius: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
        .feature-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .bg-admin { background-color: #e2d9ff; color: #4b23ad; }
        .bg-finance { background-color: #d1f2e5; color: #0f764e; }
        .bg-staff { background-color: #e3f2fd; color: #0d47a1; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-ktm shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fa-solid fa-train me-2"></i>KTM eDOIS <span class="fw-light text-muted">| Staff Portal</span>
            </a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3 d-none d-md-inline">Welcome, <strong><?php echo htmlspecialchars($staffName); ?></strong></span>
                <a href="logout.php" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-right-from-bracket me-1"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">

        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-warning shadow-sm"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="bg-white p-4 rounded shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h2 class="fw-bold text-dark m-0">System Control Centre</h2>
                        <p class="text-muted m-0">Manage delivery orders, validation pipelines, and architectural rules.</p>
                    </div>
                    <div>
                        <?php if ($isAdministrator): ?>
                            <span class="badge bg-admin role-badge fw-bold shadow-sm"><i class="fa-solid fa-user-shield me-1"></i> Mode: Administrator</span>
                        <?php elseif ($sub_role === 'Finance Officer' || $sub_role === 'Finance Reviewer'): ?>
                            <span class="badge bg-finance role-badge fw-bold shadow-sm"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Mode: Finance Officer</span>
                        <?php else: ?>
                            <span class="badge bg-staff role-badge fw-bold shadow-sm"><i class="fa-solid fa-user-gear me-1"></i> Mode: Procurement Officer</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <h4 class="fw-bold text-secondary mb-3">Your Accessible Applications</h4>
        <div class="row g-4">

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card card-custom h-100 shadow-sm bg-white">
                    <div class="card-body p-4">
                        <div class="feature-icon text-primary"><i class="fa-solid fa-magnifying-glass-doc"></i></div>
                        <h5 class="fw-bold card-title">Track & Search Orders</h5>
                        <p class="card-text text-muted">Inquire, inspect, and evaluate active incoming supplier delivery tracking metrics.</p>
                        <a href="../m2/do_dashboard.php" class="btn btn-sm btn-outline-primary fw-semibold mt-2">Launch Engine &rarr;</a>
                    </div>
                </div>
            </div>

            <?php if ($isFinanceStaff): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-custom h-100 shadow-sm bg-white" style="border-left: 4px solid #0f764e;">
                        <div class="card-body p-4">
                            <div class="feature-icon text-success"><i class="fa-solid fa-stamp"></i></div>
                            <h5 class="fw-bold card-title">Invoice Validation & Audits</h5>
                            <p class="card-text text-muted">Verify incoming legal accounting documentation clearances against matching warehouse operations parameters.</p>
                            <a href="../m4/review_workspace.php" class="btn btn-sm btn-success fw-semibold mt-2">Review Pending Queues</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($isProcurementStaff): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-custom h-100 shadow-sm bg-white">
                        <div class="card-body p-4">
                            <div class="feature-icon text-info"><i class="fa-solid fa-boxes-stacked"></i></div>
                            <h5 class="fw-bold card-title">Physical Dispatches Verification</h5>
                            <p class="card-text text-muted">Acknowledge item acceptance directly from ground operations yards at cargo waypoints.</p>
                            <a href="../m4/assign_reviewer.php" class="btn btn-sm btn-info text-white fw-semibold mt-2">Open Operations Yard</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($isAdministrator): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-custom h-100 shadow-sm text-white" style="background-color: #3b1c8c;">
                        <div class="card-body p-4">
                            <div class="feature-icon text-warning"><i class="fa-solid fa-users-gear"></i></div>
                            <h5 class="fw-bold card-title">Vendor Account Management</h5>
                            <p class="text-white-50">Approve registrations, reset access tokens, modify systemic validation schemas, and assign vendor risk status ratings.</p>
                            <a href="admin_vendor_list.php" class="btn btn-sm btn-warning fw-semibold mt-2">Enter Enterprise Dashboard</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-custom h-100 shadow-sm bg-dark text-white">
                        <div class="card-body p-4">
                            <div class="feature-icon text-danger"><i class="fa-solid fa-terminal"></i></div>
                            <h5 class="fw-bold card-title">System Audit Trails & Configuration</h5>
                            <p class="text-muted">Review atomic structural exceptions, inspect security errors, and view absolute action logs.</p>
                            <a href="../m4/audit_log.php" class="btn btn-sm btn-outline-danger fw-semibold mt-2">Examine Logs</a>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-custom h-100 shadow-sm bg-white" style="border-left: 4px solid #002B49;">
                        <div class="card-body p-4">
                            <div class="feature-icon text-primary"><i class="fa-solid fa-chart-line"></i></div>
                            <h5 class="fw-bold card-title">Operations Center Overview</h5>
                            <p class="card-text text-muted">Monitor pending claims, review queues, and overall finance workflow performance.</p>
                            <a href="../m4/review_dashboard.php" class="btn btn-sm btn-primary fw-semibold mt-2">Open Operations Center</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <footer class="bg-white border-top py-3 fixed-bottom">
        <div class="container text-center">
            <small class="text-muted">&copy; <?php echo date('Y'); ?> Keretapi Tanah Melayu Berhad. Internal Infrastructure Node.</small>
        </div>
    </footer>

</body>
</html>