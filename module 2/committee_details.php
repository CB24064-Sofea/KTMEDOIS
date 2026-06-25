<?php
session_start();
$host = "127.0.0.1:3307"; 
$user = "root";
$password = "";
$database = "group5"; 

//not admin, unauthorized
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'Admin') {
    echo "
    <script>
        alert('Unauthorized access! Please login as an Admin.');
        window.location='../module1/login.php'; 
    </script>
    ";
    exit();
}

$link = mysqli_connect($host, $user, $password);
if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!mysqli_select_db($link, $database)) {
    die("Database selection failed: " . mysqli_error($link));
}

// Initialize variables
$committee_data = null;
$error_msg = "";

$user_blob_string = "";
$image_mime_type = "image/jpeg"; 

$userQuery = "SELECT profilePhoto FROM user WHERE role = 'Admin' LIMIT 1";
$userResult = mysqli_query($link, $userQuery);

if ($userResult && mysqli_num_rows($userResult) > 0) {
    $userRow = mysqli_fetch_assoc($userResult);
    if (!empty($userRow['profilePhoto'])) {
        $user_blob_string = base64_encode($userRow['profilePhoto']);
    }
}

// 2. FETCH DETAILS BASED ON URL PARAMETERS
if (isset($_GET['uid']) && isset($_GET['cid'])) {
    $uid = mysqli_real_escape_string($link, $_GET['uid']);
    $cid = mysqli_real_escape_string($link, $_GET['cid']);
    
    // Comprehensive relational query fetching data mapped to your wireframe
    $query = "SELECT cc.*, c.clubName, c.clubID 
              FROM club_committee cc
              JOIN club c ON cc.clubID = c.clubID
              WHERE cc.userID = '$uid' AND cc.clubID = '$cid' 
              LIMIT 1";
              
    $result = mysqli_query($link, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $committee_data = mysqli_fetch_assoc($result);
    } else {
        $error_msg = "Requested committee assignment details could not be found.";
    }
} else {
    $error_msg = "Invalid request parameters. Missing Student ID or Club ID.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Committee Detail - FK Student Club & Event Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f6fa;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-header {
            background-color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            border-bottom: 2px solid #e0e0e0;
            height: 70px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-placeholder img {
            height: 45px;
            width: auto;
            object-fit: contain;
            display: block;
        }

        .header-left h1 {
            font-size: 1.4rem;
            color: #2c3e50;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-name {
            font-weight: 600;
            color: #555;
        }

        .profile-container {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            overflow: hidden;
        }

        .profile-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .profile-fallback {
            font-size: 0.9rem;
            font-weight: 700;
            color: #3498db;
            text-transform: uppercase;
        }

        /* Layout Container */
        .app-container {
            display: flex;
            flex: 1;
        }

        /* Sidebar Styling Architecture */
        .sidebar {
            width: 240px;
            background-color: #ffffff;
            border-right: 2px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px 15px;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .nav-item, .sub-nav-item, .btn-logout {
            width: 100%;
            padding: 12px 15px;
            background: none;
            border: 1px solid #dcdde1;
            border-radius: 5px;
            text-align: left;
            font-size: 0.95rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;  
            display: block;         
            color: #333333;
        }

        .nav-item:hover, .sub-nav-item:hover, .btn-logout:hover {
            background-color: #f1f2f6;
            border-color: #b2bec3;
        }

        /* Dropdown Submenu Layout Styles */
        .submenu-container {
            border: 1px solid #b2bec3;
            border-radius: 5px;
            background-color: #fafafa;
            overflow: hidden;
        }

        .submenu-container .nav-item {
            border: none;
            border-bottom: 1px solid #dcdde1;
            border-radius: 0;
            background-color: #f1f2f6;
            font-weight: bold;
        }

        .submenu {
            display: flex;
            flex-direction: column;
        }

        .sub-nav-item {
            border: none;
            border-bottom: 1px solid #eee;
            border-radius: 0;
            padding-left: 30px;
            font-size: 0.9rem;
            text-decoration: none;
            color: #333;
        }

        /* Wireframe Blue Highlight Class */
        .active-sub {
            background-color: #3498db !important;
            color: white !important;
            border-color: #2980b9 !important;
        }

        .btn-logout {
            margin-top: auto;
            background-color: #feeaee;
            color: #c0392b;
            border-color: #fab1a0;
            text-align: center;
            font-weight: 600;
            text-decoration: none;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .workspace-stack {
            width: 100%;
            max-width: 800px; 
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .page-title {
            text-align: center;
            font-size: 1.6rem;
            color: #2c3e50;
            border: 1px solid #b2bec3;
            background-color: #ffffff;
            padding: 12px;
            border-radius: 5px;
        }

        /* Wireframe Outer Layout Card Group */
        .details-card {
            background-color: #ffffff;
            border: 1px solid #b2bec3;
            border-radius: 6px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        /* Individual Data Block Rows */
        .detail-field-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 100%;
        }

        .detail-field-label {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #7f8c8d;
            letter-spacing: 0.5px;
        }

        .detail-field-value {
            width: 100%;
            padding: 12px 16px;
            font-size: 1rem;
            background-color: #f8f9fa;
            border: 1px solid #dcdde1;
            border-radius: 5px;
            color: #2c3e50;
            font-weight: 500;
        }

        /* Footer Utilities */
        .actions-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }

        .btn-back {
            padding: 10px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            background-color: #7f8c8d;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .btn-back:hover {
            background-color: #616a6b;
        }

        .alert {
            padding: 14px 20px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: center;
            border: 1px solid #f5c6cb;
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="header-left">
            <div class="logo-placeholder">
                <img src="UMP LOGO.png" alt="Logo">
            </div>
            <h1>FK Student Club & Event Management</h1>
        </div>
        <div class="header-right">
             <span class="admin-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            <div class="profile-container">
                <?php if (!empty($user_blob_string)): ?>
                    <img src="data:<?php echo $image_mime_type; ?>;base64,<?php echo $user_blob_string; ?>" alt="User Profile">
                <?php else: ?>
                    <span class="profile-fallback">U</span>
                <?php endif; ?>
            </div>
    </header>

    <div class="app-container">
        
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                
                <div class="submenu-container">
                    <div class="nav-item">Committee</div>
                    <div class="submenu">
                        <a href="manageCommittee/manage_committee.php" class="sub-nav-item active-sub">Manage Committee</a>
                        <a href="create_committee.php" class="sub-nav-item">Create Committee</a>
                    </div>
                </div>

                <a href="manageClub/manage_club.php" class="nav-item">Clubs</a>
                <a href="#" class="nav-item">Events</a>
                <a href="../module 4/admin/dashboard.php" class="nav-item">Attendance</a>
                <a href="../module 4/admin/reports.php" class="nav-item">Reports</a>
            </nav>
            <a href="../module1/logout.php" class="btn-logout">logout</a>
        </aside>

        <main class="main-content">
            <div class="workspace-stack">
                
                <h2 class="page-title">Committee Detail</h2>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert"><?php echo htmlspecialchars($error_msg); ?></div>
                    <div class="actions-row">
                        <a href="manageCommittee/manage_committee.php" class="btn-back">back</a>
                    </div>
                <?php else: ?>

                    <div class="details-card">
                        
                        <div class="detail-field-row">
                            <span class="detail-field-label">Committee ID</span>
                            <div class="detail-field-value">
                                CMTE-<?php echo htmlspecialchars($committee_data['clubID'] . "-" . $committee_data['userID']); ?>
                            </div>
                        </div>

                        <div class="detail-field-row">
                            <span class="detail-field-label">Club Name</span>
                            <div class="detail-field-value">
                                <?php echo htmlspecialchars($committee_data['clubName']); ?>
                            </div>
                        </div>

                        <div class="detail-field-row">
                            <span class="detail-field-label">Student ID</span>
                            <div class="detail-field-value">
                                <?php echo htmlspecialchars($committee_data['userID']); ?>
                            </div>
                        </div>

                        <div class="detail-field-row">
                            <span class="detail-field-label">Student Name</span>
                            <div class="detail-field-value">
                                <?php echo htmlspecialchars($committee_data['userID'] . " - Registered Student Record"); ?>
                            </div>
                        </div>

                        <div class="detail-field-row">
                            <span class="detail-field-label">Club</span>
                            <div class="detail-field-value">
                                ID: <?php echo htmlspecialchars($committee_data['clubID']); ?> — <?php echo htmlspecialchars($committee_data['clubName']); ?>
                            </div>
                        </div>

                        <div class="detail-field-row">
                            <span class="detail-field-label">Position</span>
                            <div class="detail-field-value">
                                <?php echo htmlspecialchars($committee_data['position']); ?>
                            </div>
                        </div>

                        <div class="detail-field-row">
                            <span class="detail-field-label">Assigned Date</span>
                            <div class="detail-field-value">
                                <?php echo isset($committee_data['assigned_date']) ? htmlspecialchars($committee_data['assigned_date']) : date('Y-m-d'); ?>
                            </div>
                        </div>

                    </div>

                    <div class="actions-row">
                        <a href="manageCommittee/manage_committee.php" class="btn-back">back</a>
                    </div>

                <?php endif; ?>

            </div>
        </main>
    </div>

</body>
</html>
<?php 
mysqli_close($link); 
?>