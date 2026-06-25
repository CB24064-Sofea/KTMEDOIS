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

// Connect 
$link = mysqli_connect($host, $user, $password) or die("Connection failed: " . mysqli_connect_error());

// Select
mysqli_select_db($link, $database) or die("Database selection failed: " . mysqli_error($link));

$clubID = "";
$clubName = "";
$advisor = "";
$email = "";
$description = "";
$status = "";
$errorMessage = "";

$userQuery = "SELECT profilePhoto FROM user WHERE role = 'Admin' LIMIT 1";
$userResult = mysqli_query($link, $userQuery);

if ($userResult && mysqli_num_rows($userResult) > 0) {
    $userRow = mysqli_fetch_assoc($userResult);
    if (!empty($userRow['profilePhoto'])) {
        $user_blob_string = base64_encode($userRow['profilePhoto']);
    }
} 

// 2. Fetch Club Data Based on URL Parameter
if (isset($_GET['id'])) {
    $viewID = mysqli_real_escape_string($link, $_GET['id']);
    
    $query = "SELECT * FROM club WHERE clubID = '$viewID'";
    $result = mysqli_query($link, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $clubID = $row['clubID'];
        $clubName = $row['clubName'];
        $advisor = $row['advisor'];
        $email = $row['email'];
        $description = $row['description'];
        $status = $row['status'];
    } else {
        $errorMessage = "Club details could not be found.";
    }
} else {
    $errorMessage = "No club ID specified for viewing.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Detail - FK Student Club & Event Management</title>
    <style>
        /* Base Resets & Typography */
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

        /* Header Styling */
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

        /* Sidebar Styling (Matches Admin Structure) */
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

        .nav-item:hover, .btn-logout:hover {
            background-color: #f1f2f6;
            border-color: #b2bec3;
        }

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
        }

        .sub-nav-item:last-child {
            border-bottom: none;
        }

        .active-sub {
            background-color: #3498db !important;
            color: white;
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
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .wireframe-stack {
            width: 100%;
            max-width: 850px;
            display: flex;
            flex-direction: column;
            gap: 25px;
            margin: 0 auto;
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

        .section-block {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 6px;
            border: 1px solid #b2bec3;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .section-title {
            font-size: 1.2rem;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #3498db;
        }

        .detail-row {
            display: flex;
            align-items: center;
            border: 1px solid #dcdde1;
            margin-bottom: 12px;
            border-radius: 4px;
            overflow: hidden;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            width: 180px;
            background-color: #f1f2f6;
            padding: 12px 15px;
            font-weight: 600;
            color: #4b6584;
            border-right: 1px solid #dcdde1;
        }

        .detail-value {
            flex: 1;
            padding: 12px 15px;
            color: #2c3e50;
            background-color: #ffffff;
        }

        .placeholder-list {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .placeholder-list th, .placeholder-list td {
            border: 1px solid #dcdde1;
            padding: 10px 12px;
            text-align: left;
        }

        .placeholder-list th {
            background-color: #fafafa;
            color: #7f8c8d;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .alert-error {
            padding: 15px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            text-align: center;
            font-weight: 600;
        }

        .action-container {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .btn {
            padding: 10px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-block;
            text-align: center;
        }

        .btn-secondary {
            background-color: #7f8c8d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #636e72;
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
                <a href="manageCommittee/manage_committee.php" class="nav-item">Committee</a>

                <div class="submenu-container">
                    <button class="nav-item">Clubs</button>
                    <div class="submenu">
                        <a href="manage_club.php" class="sub-nav-item active-sub">Manage Club</a>
                        <a href="create_club.php" class="sub-nav-item">Create Club</a>
                    </div>
                </div>

                <a href="#" class="nav-item">Events</a>
                <a href="../module 4/admin/dashboard.php#" class="nav-item">Attendance</a>
                <a href="../module 4/admin/reports.php" class="nav-item">Reports</a>
            </nav>
            <a href="../module1/logout.php" class="btn-logout">Logout</a>
        </aside>

        <main class="main-content">
            <div class="wireframe-stack">
                
                <h2 class="page-title">Club Detail</h2>
                
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert-error"><?php echo $errorMessage; ?></div>
                    <div class="action-container">
                        <a href="manage_club.php" class="btn btn-secondary">Back</a>
                    </div>
                <?php else: ?>
                    
                    <section class="section-block">
                        <div class="detail-row">
                            <div class="detail-label">Club ID</div>
                            <div class="detail-value"><?php echo htmlspecialchars($clubID); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Club Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($clubName); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Advisor</div>
                            <div class="detail-value"><?php echo htmlspecialchars($advisor); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($email); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Description</div>
                            <div class="detail-value" style="white-space: pre-wrap;"><?php echo htmlspecialchars($description); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Status</div>
                            <div class="detail-value"><?php echo htmlspecialchars($status); ?></div>
                        </div>
                    </section>

                    <section class="section-block">
                        <h3 class="section-title">Committee Members</h3>
                        <table class="placeholder-list">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Member Name</th>
                                    <th>Faculty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>President</td>
                                    <td>Ahmad Farhan</td>
                                    <td>Faculty of Computing</td>
                                </tr>
                                <tr>
                                    <td>Vice President</td>
                                    <td>Siti Aminah</td>
                                    <td>Faculty of Computing</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <section class="section-block">
                        <h3 class="section-title">Past and Upcoming Events</h3>
                        <table class="placeholder-list">
                            <thead>
                                <tr>
                                    <th>Event Name</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Computing Career Fair 2026</td>
                                    <td>24 June 2026</td>
                                    <td><span style="color:#2ecc71; font-weight:600;">Upcoming</span></td>
                                </tr>
                                <tr>
                                    <td>Introduction to Web Engineering</td>
                                    <td>12 April 2026</td>
                                    <td><span style="color:#7f8c8d;">Past</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <div class="action-container">
                        <a href="manage_club.php" class="btn btn-secondary">Back to List</a>
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