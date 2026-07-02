<?php
// ==========================================
// [SESSION INITIALIZATION & REDIRECT CHECK]
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_path = dirname(__DIR__) . '/db.php';
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    die("Database configuration file (db.php) not found.");
}

/** @var mysqli $conn */

// If staff session is already running, redirect to dashboard
if (isset($_SESSION['staff_auth'])) {
    header("Location: ktm_dashboard.php");
    exit();
}

$error = '';

// ==========================================
// [POST LOGIN VALIDATION FLOW]
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; 

    if (empty($email) || empty($password)) {
        $error = "Email and Password are required.";
    } else {
        // Querying ktmb_staff table
        $sql = "SELECT staff_ID, staff_name, email, password, role
                FROM ktmb_staff 
                WHERE UPPER(TRIM(email)) = UPPER(?) 
                LIMIT 1";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $staff = $result->fetch_assoc();

            // Verify password using hash
            if ($staff && password_verify($password, $staff['password'])) {
                
                // Populate staff authorization sessions.
                // IMPORTANT: "role" / "sub_role" must carry the REAL role
                // fetched from ktmb_staff (Procurement Officer / Finance
                // Officer / Administrator) — sidebar.php uses this value
                // to decide which of the three staff sidebars to render.
                // Hardcoding "Staff" here previously broke that routing:
                // every staff member always fell through to the generic
                // KTM Officer sidebar regardless of their real role.
                $_SESSION['staff_auth'] = [
                    "staff_id"   => $staff['staff_ID'],
                    "staff_ID"   => $staff['staff_ID'],
                    "name"       => $staff['staff_name'],
                    "staff_name" => $staff['staff_name'],
                    "email"      => $staff['email'],
                    "sub_role"   => $staff['role'],
                    "role"       => $staff['role']
                ];
                $_SESSION['staff_id']       = $staff['staff_ID'];
                $_SESSION['user_id']        = $staff['staff_ID'];
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_name']      = $staff['staff_name'];
                $_SESSION['user_role']      = $staff['role'];
                $_SESSION['current_module'] = 'staff';

                $stmt->close();
                header("Location: ktm_dashboard.php");
                exit();
            } else {
                $error = "Login Failed: Invalid credentials or account not found.";
            }
            $stmt->close();
        } else {
            $error = "System Error: Failed to compile database query.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS - Staff Authentication</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { 
            background-image: url('../ktmb_login.jpg'); 
            background-size: cover; 
            background-position: left center; 
            background-attachment: fixed;
        }
        .login-card { 
            border: none; 
            border-top: 5px solid #ff6600; /* Distinct orange border for staff */
            background-color: rgba(255, 255, 255, 0.94); 
            backdrop-filter: blur(8px); 
            border-radius: 8px; 
        }
        .btn-ktm-staff { background-color: #ff6600; color: #ffffff; }
        .btn-ktm-staff:hover { background-color: #e65c00; color: #ffffff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center justify-content-md-end">
            <div class="col-12 col-md-6 col-lg-4 me-lg-5">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="card shadow-lg login-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-dark">KTM Staff Portal</h3>
                            <small class="text-muted">Internal Staff Access Only</small>
                        </div>
                        <form action="staff_login.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Staff Email</label>
                                <input type="email" name="email" class="form-control py-2" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Password</label>
                                <input type="password" name="password" class="form-control py-2" required>
                            </div>
                            <button type="submit" class="btn btn-ktm-staff w-100 fw-bold py-2">Sign In</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
