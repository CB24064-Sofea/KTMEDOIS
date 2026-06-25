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

$link = mysqli_connect($host, $user, $password) or die("Connection failed: " . mysqli_connect_error());
mysqli_select_db($link, $database) or die("Database selection failed: " . mysqli_error($link));

$clubsListQuery = "SELECT clubID, clubName FROM club ORDER BY clubName ASC";
$clubsListResult = mysqli_query($link, $clubsListQuery);

$filterClubID = isset($_GET['filterClubID']) ? mysqli_real_escape_string($link, $_GET['filterClubID']) : '';

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

if (!empty($filterClubID)) {
    $query = "SELECT c.clubID, c.clubName, c.advisor, s.userID AS studentID, s.userID AS studentName, c.email AS studentEmail 
              FROM membership m 
              JOIN club c ON m.clubID = c.clubID 
              JOIN student s ON m.userID = s.userID
              WHERE m.clubID = '$filterClubID' AND m.status = 'Active'
              ORDER BY s.userID ASC";
} else {
    $query = "SELECT clubID, clubName, advisor, email AS studentEmail, NULL AS studentID, NULL AS studentName 
              FROM club 
              ORDER BY clubID ASC";
}

$result = mysqli_query($link, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Club - FK Student Club & Event Management</title>
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
        }

        .filter-select {
            padding: 8px 12px;
            font-size: 0.95rem;
            border: 1px solid #b2bec3;
            border-radius: 4px;
            outline: none;
            min-width: 220px;
            background-color: #fff;
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
        </div>
    </header>

    <div class="app-container">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a href="../admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="../manageCommittee/manage_committee.php" class="nav-item">Committee</a>

                <div class="submenu-container">
                    <button class="nav-item">Clubs</button>
                    <div class="submenu">
                        <a href="manage_club.php" class="sub-nav-item active-sub">Manage Club</a>
                        <a href="../create_club.php" class="sub-nav-item">Create Club</a>
                    </div>
                </div>

                <a href="#" class="nav-item">Events</a>
                <a href="../../module 4/admin/dashboard.php" class="nav-item">Attendance</a>
                <a href="../../module 4/admin/reports.php" class="nav-item">Reports</a>
            </nav>
            <a href="../../module1/logout.php" class="btn-logout">Logout</a>
        </aside>

        <main class="main-content">
            <div class="workspace-stack">
                
                <h2 class="page-title">Manage Club Members</h2>

                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="alert <?php echo $_SESSION['msgClass']; ?>">
                        <?php 
                            echo $_SESSION['msg']; 
                            unset($_SESSION['msg']);
                            unset($_SESSION['msgClass']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="filter-actions-bar">
                    <form action="manage_club.php" method="GET" class="filter-form">
                        <label for="filterClubID" style="font-weight: 600; color: #2c3e50;">Filter by Club:</label>
                        <select name="filterClubID" id="filterClubID" class="filter-select" onchange="this.form.submit()">
                            <option value="">-- View All General Clubs --</option>
                            <?php 
                            if ($clubsListResult && mysqli_num_rows($clubsListResult) > 0) {
                                while ($clubOption = mysqli_fetch_assoc($clubsListResult)) {
                                    $isSelected = ($clubOption['clubID'] == $filterClubID) ? 'selected' : '';
                                    echo "<option value='".htmlspecialchars($clubOption['clubID'])."'$isSelected>".htmlspecialchars($clubOption['clubName'])."</option>";
                                }
                            }
                            ?>
                        </select>
                    </form>
                    
                    <a href="../create_club.php" class="btn btn-add">Add New Club</a>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Club ID</th>
                                <th>Club Name</th>
                                <?php if (!empty($filterClubID)): ?>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                <?php endif; ?>
                                <th>Advisor</th>
                                <th>Email</th>
                                <th style="width: 260px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['clubID']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['clubName']); ?></td>
                                        
                                        <?php if (!empty($filterClubID)): ?>
                                            <td><span style="color: #2980b9; font-weight: 600;"><?php echo htmlspecialchars($row['studentID']); ?></span></td>
                                            <td><?php echo htmlspecialchars($row['studentName']); ?></td>
                                        <?php endif; ?>
                                        
                                        <td><?php echo htmlspecialchars($row['advisor']); ?></td>
                                        <td><?php echo htmlspecialchars($row['studentEmail']); ?></td>
                                        <td style="text-align: center;">
                                            <div class="action-cell" style="justify-content: center;">
                                                <a href="edit_club.php?id=<?php echo urlencode($row['clubID']); ?>" class="btn btn-edit">Edit</a>
                                                <a href="delete_club.php?id=<?php echo urlencode($row['clubID']); ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this club record?');">Delete</a>
                                                <a href="../club_details.php?id=<?php echo urlencode($row['clubID']); ?>" class="btn btn-view">View</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="<?php echo !empty($filterClubID) ? '7' : '5'; ?>" style="text-align: center; color: #7f8c8d; padding: 30px;">
                                        No data found.
                                    </td>
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