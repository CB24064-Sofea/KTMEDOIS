<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. DYNAMIC CONFIGURATION LOADER
$db_path = dirname(__DIR__) . '/db.php';
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    die("<div style='padding:20px; font-family:sans-serif; color:#721c24; background:#f8d7da; border:1px solid #f5c6cb; border-radius:5px; margin:20px;'>
            <strong>File Structure Error:</strong> Database connection file (db.php) not found.
         </div>");
}

require_once __DIR__ . '/VendorController.php';

/** @var mysqli $conn */
$controller = new VendorController($conn);
$feedback = null;

// 2. FORM ACTION CAPTURE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = $controller->handleRegistration($_POST);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS - Vendor Registration Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-image: url('../ktmb_login.jpg'); background-size: cover; background-position: left center; background-attachment: fixed; min-height: 100vh; }
        .register-card { border: none; border-top: 5px solid #ff6600; background-color: rgba(255, 255, 255, 0.95); backdrop-filter: blur(8px); border-radius: 8px; }
        .btn-ktm { background-color: #002B49; color: #ffffff; }
        .btn-ktm:hover { background-color: #ff6600; color: #ffffff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center justify-content-md-end">
            <div class="col-12 col-md-7 col-lg-5 me-lg-5 my-4">
                
                <?php if ($feedback): ?>
                    <div class="alert <?php echo $feedback['status'] === 'success' ? 'alert-success' : 'alert-danger'; ?> shadow-sm border-0">
                        <?php echo $feedback['message']; ?>
                    </div>
                <?php endif; ?>

                <div class="card shadow-lg register-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-dark m-0">Create Vendor Account</h3>
                            <small class="text-muted fw-semibold">Electronic Delivery Order & Invoice System</small>
                        </div>

                        <form action="register.php" method="POST">
                            <div class="mb-3">
                                <label for="supplier_name" class="form-label fw-bold">Authorized Representative Name</label>
                                <input type="text" class="form-control" id="supplier_name" name="supplier_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="company_name" class="form-label fw-bold">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label fw-bold">Contact Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold">Enterprise Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label fw-bold">Account Password</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            </div>

                            <button type="submit" class="btn btn-ktm w-100 fw-bold py-2">Register Enterprise Credentials</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($feedback && $feedback['status'] === 'success'): ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Add a simple logic to show your success modal if needed
            alert("Registration Successful!");
        </script>
    <?php endif; ?>
</body>
</html>