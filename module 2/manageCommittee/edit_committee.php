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

// Initialize messaging variables
$success_msg = "";
$error_msg = "";

$current_uid = "";
$current_cid = "";
$committee_record = null;

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

if (isset($_GET['uid']) && isset($_GET['cid'])) {
    $current_uid = mysqli_real_escape_string($link, $_GET['uid']);
    $current_cid = mysqli_real_escape_string($link, $_GET['cid']);
    
    $fetch_query = "SELECT cc.*, c.clubName 
                    FROM club_committee cc
                    JOIN club c ON cc.clubID = c.clubID
                    WHERE cc.userID = '$current_uid' AND cc.clubID = '$current_cid' 
                    LIMIT 1";
    $fetch_result = mysqli_query($link, $fetch_query);
    
    if ($fetch_result && mysqli_num_rows($fetch_result) > 0) {
        $committee_record = mysqli_fetch_assoc($fetch_result);
    } else {
        $error_msg = "Error: Committee record assignment could not be located.";
    }
} else if (!isset($_POST['update_committee'])) {
    $error_msg = "Invalid access query configuration parameters.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_committee'])) {
    $old_uid = mysqli_real_escape_string($link, $_POST['old_uid']);
    $old_cid = mysqli_real_escape_string($link, $_POST['old_cid']);
    $new_uid = mysqli_real_escape_string($link, $_POST['student_id']);
    $position = mysqli_real_escape_string($link, $_POST['position']);
    
    mysqli_begin_transaction($link);
    try {
        $update_query = "UPDATE club_committee 
                         SET userID = '$new_uid', position = '$position' 
                         WHERE userID = '$old_uid' AND clubID = '$old_cid'";
                         
        if (mysqli_query($link, $update_query)) {
            mysqli_commit($link);
            $_SESSION['msg'] = "Committee assignment successfully updated!";
            header("Location: manage_committee.php");
            exit();
        } else {
            throw new Exception(mysqli_error($link));
        }
    } catch (Exception $e) {
        mysqli_rollback($link);
        $error_msg = "Failed to apply updates to the database: " . $e->getMessage();
    }
}

$students_query = "SELECT userID, name FROM user WHERE role = 'student' OR role IS NULL ORDER BY userID ASC";
$students_result = mysqli_query($link, "SHOW COLUMNS FROM user LIKE 'name'");
if (mysqli_num_rows($students_result) == 0) {
    $students_query = "SELECT userID, userID as name FROM user ORDER BY userID ASC";
}
$students_list = mysqli_query($link, $students_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Committee - FK Student Club & Event Management</title>
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
            text-decoration: none;  
            display: block;         
            color: #333333;
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

        .form-panel {
            background-color: #ffffff;
            border: 1px solid #b2bec3;
            border-radius: 6px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .form-field-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-field-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #4a5568;
        }

        .input-text-disabled {
            width: 100%;
            padding: 10px 14px;
            font-size: 0.95rem;
            background-color: #edf2f7;
            border: 1px solid #cbd5e0;
            border-radius: 4px;
            color: #4a5568;
            cursor: not-allowed;
        }

        .select-input-box {
            width: 100%;
            padding: 10px 14px;
            font-size: 0.95rem;
            border: 1px solid #b2bec3;
            border-radius: 4px;
            background-color: #ffffff;
            outline: none;
            color: #2d3748;
        }

        .select-input-box:focus {
            border-color: #3498db;
        }

        .form-actions-row {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
        }

        .btn-submit-action {
            padding: 10px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            background-color: #ffffff;
            color: #2c3e50;
            border: 1px solid #b2bec3;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-submit-action:hover {
            background-color: #f1f2f6;
        }

        .btn-cancel-action {
            padding: 10px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            background-color: #ffffff;
            color: #333;
            border: 1px solid #b2bec3;
            border-radius: 4px;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
        }

        .btn-cancel-action:hover {
            background-color: #f1f2f6;
        }

        .alert {
            padding: 12px 18px;
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
                
                <h2 class="page-title">Update Committee</h2>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert"><?php echo htmlspecialchars($error_msg); ?></div>
                    <div class="form-actions-row">
                        <a href="manage_committee.php" class="btn-cancel-action">Back to List</a>
                    </div>
                <?php else: ?>

                    <form action="edit_committee.php" method="POST" class="form-panel">
                        
                        <input type="hidden" name="old_uid" value="<?php echo htmlspecialchars($committee_record['userID']); ?>">
                        <input type="hidden" name="old_cid" value="<?php echo htmlspecialchars($committee_record['clubID']); ?>">

                        <div class="form-field-group">
                            <label class="form-field-label">Club Name</label>
                            <input type="text" class="input-text-disabled" value="<?php echo htmlspecialchars($committee_record['clubName']); ?>" readonly>
                        </div>

                        <div class="form-field-group">
                            <label class="form-field-label">Select Student</label>
                            <select name="student_id" class="select-input-box" required>
                                <option value="">-- Choose Student Record --</option>
                                <?php 
                                if ($students_list && mysqli_num_rows($students_list) > 0) {
                                    while ($student = mysqli_fetch_assoc($students_list)) {
                                        $selected = ($student['userID'] == $committee_record['userID']) ? "selected" : "";
                                        echo "<option value='".htmlspecialchars($student['userID'])."' $selected>".htmlspecialchars($student['userID']." - ".$student['name'])."</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-field-group">
                            <label class="form-field-label">Position</label>
                            <select name="position" class="select-input-box" required>
                                <option value="">-- Choose Position Role --</option>
                                <option value="President" <?php echo ($committee_record['position'] == 'President') ? 'selected' : ''; ?>>President</option>
                                <option value="Vice President" <?php echo ($committee_record['position'] == 'Vice President') ? 'selected' : ''; ?>>Vice President</option>
                                <option value="Secretary" <?php echo ($committee_record['position'] == 'Secretary') ? 'selected' : ''; ?>>Secretary</option>
                                <option value="Treasurer" <?php echo ($committee_record['position'] == 'Treasurer') ? 'selected' : ''; ?>>Treasurer</option>
                                <option value="Committee Member" <?php echo ($committee_record['position'] == 'Committee Member') ? 'selected' : ''; ?>>Committee Member</option>
                            </select>
                        </div>

                        <div class="form-actions-row">
                            <button type="submit" name="update_committee" class="btn-submit-action">Update Committee</button>
                            <a href="manage_committee.php" class="btn-cancel-action">Cancel</a>
                        </div>

                    </form>

                <?php endif; ?>

            </div>
        </main>
    </div>

</body>
</html>
<?php 
mysqli_close($link); 
?>