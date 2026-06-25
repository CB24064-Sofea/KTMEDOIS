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
        window.location='../../module1/login.php'; 
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

// Initialize system messaging from session variables if present
$success_msg = "";
$error_msg = "";

if (isset($_SESSION['msg'])) {
    $success_msg = $_SESSION['msg'];
    unset($_SESSION['msg']);
}

$search_term = "";
if (isset($_GET['search_club'])) {
    $search_term = mysqli_real_escape_string($link, $_GET['search_club']);
}

$query = "SELECT cc.userID, cc.clubID, cc.position, c.clubName 
          FROM club_committee cc
          JOIN club c ON cc.clubID = c.clubID";


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

if (!empty($search_term)) {
    $query .= " WHERE c.clubName LIKE '%$search_term%'";
}
$query .= " ORDER BY c.clubName ASC, cc.userID ASC";
$result = mysqli_query($link, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Club Committee - FK Student Club & Event Management</title>
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

        .app-container {
            display: flex;
            flex: 1;
        }

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
            text-decoration: none;
            color: #333;
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
        }

        .workspace-stack {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
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

        .filter-actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ffffff;
            border: 1px solid #b2bec3;
            padding: 15px;
            border-radius: 6px;
            gap: 15px;
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            max-width: 500px;
        }

        .search-input-box {
            flex: 1;
            padding: 8px 12px;
            font-size: 0.95rem;
            border: 1px solid #b2bec3;
            border-radius: 4px;
            outline: none;
            background-color: #fff;
        }

        .btn-search-submit {
            padding: 8px 16px;
            font-size: 0.9rem;
            font-weight: 600;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-search-submit:hover {
            background-color: #2980b9;
        }

        .alert {
            padding: 12px 18px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: center;
            border: 1px solid transparent;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .table-container {
            background-color: #ffffff;
            border: 1px solid #b2bec3;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .data-table th, .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #dcdde1;
        }

        .data-table th {
            background-color: #f1f2f6;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.95rem;
            border-bottom: 2px solid #b2bec3;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover td {
            background-color: #fafafa;
        }

        .btn {
            padding: 8px 16px;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.2s;
        }

        .btn-add {
            background-color: #2ecc71;
            color: white;
        }
        .btn-add:hover {
            background-color: #27ae60;
        }

        .action-cell {
            display: flex;
            gap: 8px;
        }

        .btn-view {
            background-color: #3498db;
            color: white;
        }
        .btn-view:hover {
            background-color: #2980b9;
        }

        .btn-edit {
            background-color: #f1c40f;
            color: #2c3e50;
        }
        .btn-edit:hover {
            background-color: #f39c12;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }
        .btn-delete:hover {
            background-color: #c0392b;
        }

        .no-records {
            text-align: center;
            color: #7f8c8d;
            padding: 30px !important;
            font-style: italic;
        }
        
        .sub-badge {
            font-size: 0.8rem;
            color: #7f8c8d;
            display: block;
            margin-top: 2px;
        }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="header-left">
            <div class="logo-placeholder">
                <img src="../UMP LOGO.png" alt="Logo">
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
                <a href="../admin_dashboard.php" class="nav-item">Dashboard</a>
                
                <div class="submenu-container">
                    <div class="nav-item">Committee</div>
                    <div class="submenu">
                        <a href="manage_committee.php" class="sub-nav-item active-sub">Manage Committee</a>
                        <a href="../create_committee.php" class="sub-nav-item">Create Committee</a>
                    </div>
                </div>

                <a href="../manageClub/manage_club.php" class="nav-item">Clubs</a>
                <a href="#" class="nav-item">Events</a>
                <a href="../../module 4/admin/dashboard.php" class="nav-item">Attendance</a>
                <a href="../../module 4/admin/reports.php" class="nav-item">Reports</a>
            </nav>
            <a href="../../module1/logout.php" class="btn-logout">logout</a>
        </aside>

        <main class="main-content">
            <div class="workspace-stack">
                
                <h2 class="page-title">Manage Committee</h2>

                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>

                <div class="filter-actions-bar">
                    <form action="manage_committee.php" method="GET" class="filter-form">
                        <input type="text" name="search_club" class="search-input-box" placeholder="Search Clubs..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit" class="btn-search-submit">Search</button>
                    </form>
                    <a href="../create_committee.php" class="btn btn-add">Add New Committee</a>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Club Name</th>
                                <th>Position</th>
                                <th style="width: 260px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['userID']); ?></strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['clubName']); ?>
                                            <span class="sub-badge">Club ID: <?php echo htmlspecialchars($row['clubID']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['position']); ?></td>
                                        <td>
                                            <div class="action-cell">
                                                <a href="edit_committee.php?uid=<?php echo urlencode($row['userID']); ?>&cid=<?php echo urlencode($row['clubID']); ?>" class="btn btn-edit">Edit</a>
                                                <a href="delete_committee.php?action=delete&uid=<?php echo urlencode($row['userID']); ?>&cid=<?php echo urlencode($row['clubID']); ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this committee record?');">Delete</a>
                                                <a href="../committee_details.php?uid=<?php echo urlencode($row['userID']); ?>&cid=<?php echo urlencode($row['clubID']); ?>" class="btn btn-view">View</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="4" class="no-records">No committee assignments matching your criteria were found.</td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>

</body>
</html>
<?php 
mysqli_close($link); 
?>