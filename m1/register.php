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
            <strong>File Structure Error:</strong> Database connection file (db.php) not found in the main project root directory.
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
        body {
            background-image: url('../ktmb_login.jpg'); 
            background-size: cover;
            background-position: left center; 
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
        }

        .register-card {
            border: none;
            border-top: 5px solid #ff6600; /* KTM Orange accent bar line */
            background-color: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(8px); 
            border-radius: 8px;
        }

        .btn-ktm {
            background-color: #002B49;
            color: #ffffff;
            transition: all 0.3s ease;
        }
        .btn-ktm:hover {
            background-color: #ff6600;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(255, 102, 0, 0.3);
        }
        .form-label {
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center justify-content-md-end">
            
            <div class="col-12 col-md-7 col-lg-5 me-lg-5 my-4">
                
                <?php if ($feedback && $feedback['status'] === 'error'): ?>
                    <div class="alert alert-danger shadow-sm border-0 alert-dismissible fade show" role="alert">
                        <strong>Registration Blocked:</strong> <?php echo $feedback['message']; ?>
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
                                <label for="supplier_name" class="form-label fw-bold text-secondary">Authorized Representative Name</label>
                                <input type="text" class="form-control py-2 shadow-sm" id="supplier_name" name="supplier_name" 
                                       placeholder="Enter representative's full name" required autocomplete="name">
                            </div>

                            <div class="mb-3">
                                <label for="company_name" class="form-label fw-bold text-secondary">Official Corporate Title / Company Name</label>
                                <input type="text" class="form-control py-2 shadow-sm" id="company_name" name="company_name" 
                                       placeholder="e.g., Enterprise Sdn Bhd" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label fw-bold text-secondary">Contact Phone Number</label>
                                <input type="tel" class="form-control py-2 shadow-sm" id="phone" name="phone" 
                                       placeholder="e.g., +60123456789" required autocomplete="tel">
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label fw-bold text-secondary">Registered Enterprise Email Address</label>
                                <input type="email" class="form-control py-2 shadow-sm" id="email" name="email" 
                                       placeholder="vendor@company.com" required autocomplete="email">
                            </div>

                            <button type="submit" class="btn btn-ktm w-100 fw-bold py-2 shadow mb-3">
                                Register Enterprise Credentials
                            </button>
                            
                            <div class="text-center">
                                <small class="text-muted">Already have a corporate profile? <a href="login.php" class="text-decoration-none fw-bold" style="color: #ff6600;">Log In here</a></small>
                            </div>
                        </form>

                    </div>
                </div>

                <div class="text-center text-md-end mt-3 pe-2">
                    <small class="text-white" style="text-shadow: 1px 1px 5px rgba(0,0,0,0.9); font-weight: 500;">
                        &copy; <?php echo date('Y'); ?> Keretapi Tanah Melayu Berhad. All Rights Reserved.
                    </small>
                </div>

            </div>
        </div>
    </div>

    <?php if ($feedback && $feedback['status'] === 'success'): ?>
        <div class="modal fade" id="successModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold">✓ Profile Created Successfully</h5>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <p class="text-muted mb-3">Your vendor profile registration has been parsed inside the KTM master registry matrix directory.</p>
                        <div class="p-3 bg-light rounded border border-success-subtle mb-3">
                            <?php echo $feedback['message']; ?>
                        </div>
                        <p class="small text-danger mb-0">⚠️ Write down or copy your ID before clicking continue.</p>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <a href="login.php" class="btn btn-ktm w-100 fw-bold py-2 shadow">Proceed to Login Portal</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Automatically pop open modal window if server passes clean operational success
        document.addEventListener("DOMContentLoaded", function() {
            var myModalEl = document.getElementById('successModal');
            if (myModalEl) {
                var myModal = new bootstrap.Modal(myModalEl);
                myModal.show();
            }
        });
    </script>
</body>
</html>