<?php
// ==========================================
// [SESSION INITIALIZATION & REDIRECT CHECK]
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Directly require the main database configuration object class 
$db_path = dirname(__DIR__) . '/db.php';
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    die("<div style='padding:20px; font-family:sans-serif; color:#721c24; background:#f8d7da; border:1px solid #f5c6cb; border-radius:5px; margin:20px;'>
            <strong>File Structure Error:</strong> Database connection file (db.php) not found in the main project root directory.
         </div>");
}

/** @var mysqli $conn */

// If vendor session is already running, bypass authentication form entirely
if (isset($_SESSION['vendor_auth'])) {
    header("Location: vendor_dashboard.php");
    exit();
}

$error = '';

// ==========================================
// [POST LOGIN VALIDATION FLOW]
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? ''); // Keep parameters prepared for eventual password hashing checks

    if (empty($email)) {
        $error = "Vendor Email is required.";
    } else {
        // ✅ FIXED: Updated table columns to match your ktm_edois.sql schema definitions perfectly
        $sql = "SELECT supplier_ID, company_name, email, status 
                FROM supplier 
                WHERE UPPER(TRIM(email)) = UPPER(?) 
                LIMIT 1";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $supplier = $result->fetch_assoc();

            if ($supplier) {
                // ✅ FIXED: Comparing against the 'status' column from your schema
                if (strtoupper(trim($supplier['status'])) === 'ACTIVE') {
                    
                    // Populate corporate authorization sessions cleanly
                    $_SESSION['vendor_auth'] = [
                        "supplier_id"   => $supplier['supplier_ID'],
                        "company_name"  => $supplier['company_name'],
                        "email"         => $supplier['email'],
                        "role"          => "Vendor"
                    ];
                    
                    $_SESSION['current_module'] = 'vendor';

                    $stmt->close();
                    header("Location: vendor_dashboard.php");
                    exit();
                } else {
                    $error = "Access Denied: Your account status is currently set to '" . htmlspecialchars($supplier['status']) . "'.";
                }
            } else {
                $error = "Login Failed: Provided email address is not registered in our system repository.";
            }
            $stmt->close();
        } else {
            $error = "System Error: Failed to compile analytical statement bindings.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS - Vendor Authentication Portal</title>
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

        .login-card {
            border: none;
            border-top: 5px solid #ff6600; 
            background-color: rgba(255, 255, 255, 0.94); 
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
    </style>
</head>

<body>
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center justify-content-md-end">
            
            <div class="col-12 col-md-6 col-lg-4 me-lg-5">
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger shadow-sm border-0 alert-dismissible fade show" role="alert">
                        <strong>Authentication Intercepted:</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="card shadow-lg login-card">
                    <div class="card-body p-4 p-md-5">
                        
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-dark m-0">KTM eDOIS Portal</h3>
                            <small class="text-muted fw-semibold">Electronic Delivery Order & Invoice System</small>
                        </div>

                        <form action="login.php" method="POST">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold text-secondary">Registered Vendor Email</label>
                                <input type="email" class="form-control py-2 shadow-sm" id="email" name="email" 
                                       placeholder="Enter email address" required autocomplete="email">
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-bold text-secondary">System Password</label>
                                <input type="password" class="form-control py-2 shadow-sm" id="password" name="password" 
                                       placeholder="••••••••">
                            </div>

                            <button type="submit" class="btn btn-ktm w-100 fw-bold py-2 shadow">
                                Authenticate Account
                            </button>
                            
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
</body>
</html>