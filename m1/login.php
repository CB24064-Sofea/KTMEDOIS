<?php
// ==========================================
// [SESSION INITIALIZATION & REDIRECT CHECK]
// ==========================================
session_start();

// Merujuk kepada fail db_connect.php automatik yang mengesan rantaian folder m1/../
$database_files = [
    'db_connect.php',
    'database.php',
    'db.php',
    'connection.php'
];

$db_loaded = false;
foreach ($database_files as $file) {
    $path = __DIR__ . '/../' . $file;
    if (file_exists($path)) {
        require_once $path;
        $db_loaded = true;
        break;
    }
}

if (!$db_loaded) {
    die("<div style='padding:20px; font-family:sans-serif; color:#721c24; background:#f8d7da; border:1px solid #f5c6cb; border-radius:5px; margin:20px;'>
            <strong>Ralat Struktur Fail:</strong> Fail sambungan pangkalan data tidak ditemui di folder utama <code>KTMEDOIS-main/</code>.
         </div>");
}

/** @var mysqli $conn */

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
    $password = trim($_POST['password'] ?? ''); 

    if (empty($email)) {
        $error = "E-mel Vendor diperlukan.";
    } else {
        $sql = "SELECT SUPPLIERID, SUPPLIER_COMP_NAME, SUPPLIER_EMAIL_ADD, SUPPLIER_CTC_STATUS 
                FROM supplier 
                WHERE UPPER(TRIM(SUPPLIER_EMAIL_ADD)) = UPPER(?) 
                LIMIT 1";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $supplier = $result->fetch_assoc();

            if ($supplier) {
                if (strtoupper(trim($supplier['SUPPLIER_CTC_STATUS'])) === 'ACTIVE') {
                    
                    $_SESSION['vendor_auth'] = [
                        "supplier_id"   => $supplier['SUPPLIERID'],
                        "company_name"  => $supplier['SUPPLIER_COMP_NAME'],
                        "email"         => $supplier['SUPPLIER_EMAIL_ADD'],
                        "role"          => "Vendor"
                    ];
                    
                    $_SESSION['current_module'] = 'vendor';

                    $stmt->close();
                    header("Location: vendor_dashboard.php");
                    exit();
                } else {
                    $error = "Akses Ditolak: Status akaun anda adalah '" . htmlspecialchars($supplier['SUPPLIER_CTC_STATUS']) . "'.";
                }
            } else {
                $error = "Gagal Log Masuk: Alamat e-mel tidak berdaftar.";
            }
            $stmt->close();
        } else {
            $error = "Ralat Sistem: Gagal menyediakan pertanyaan pangkalan data.";
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
            /* Memanggil imej yang anda muat naik */
            background-image: url('../ktmb_login.jpg'); 
            background-size: cover;
            background-position: left center; /* Memastikan bahagian kiri imej kereta api diberi keutamaan */
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
        }

        .login-card {
            border: none;
            border-top: 5px solid #ff6600; /* Warna oren penegas atas */
            background-color: rgba(255, 255, 255, 0.94); 
            backdrop-filter: blur(8px); /* Kesan kabur kaca yang premium */
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
                        <strong>Akses Ditahan:</strong> <?php echo htmlspecialchars($error); ?>
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

    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>
</body>

</html>