<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. DATABASE CONNECTION CONFIGURATION
$host = "127.0.0.1:3307"; 
$user = "root";
$password = "";
$database = "group5"; 

$link = mysqli_connect($host, $user, $password);
if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}
if (!mysqli_select_db($link, $database)) {
    die("Database selection failed: " . mysqli_error($link));
}

// Initialize system flash status indicators
$error_msg = "";
$success_msg = "";

$userQuery = "SELECT profilePhoto FROM user WHERE role = 'Admin' LIMIT 1";
$userResult = mysqli_query($link, $userQuery);

if ($userResult && mysqli_num_rows($userResult) > 0) {
    $userRow = mysqli_fetch_assoc($userResult);
    if (!empty($userRow['profilePhoto'])) {
        $user_blob_string = base64_encode($userRow['profilePhoto']);
    }
}

// 2. HANDLE FORM SUBMISSION TRANSACTION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_committee'])) {
    $clubID   = mysqli_real_escape_string($link, $_POST['clubID']);
    $userID   = mysqli_real_escape_string($link, $_POST['userID']);
    $position = mysqli_real_escape_string($link, $_POST['position']);

    // Check configuration variables validation constraints
    if (empty($clubID) || empty($userID) || empty($position)) {
        $error_msg = "All selection fields are strictly required to create an assignment.";
    } else {
        // Integrity Guard: Check if student is already signed up to this specific club committee
        $checkQuery = "SELECT * FROM club_committee WHERE userID = '$userID' AND clubID = '$clubID'";
        $checkResult = mysqli_query($link, $checkQuery);

        if (mysqli_num_rows($checkResult) > 0) {
            $error_msg = "Error: This specific student is already assigned to a committee role within this club.";
        } else {
            // Safe Database Write Action Execution
            $insertQuery = "INSERT INTO club_committee (userID, clubID, position) 
                            VALUES ('$userID', '$clubID', '$position')";
            
            if (mysqli_query($link, $insertQuery)) {
                $_SESSION['msg'] = "Committee assignment created successfully!";
                header("Location: manageCommittee/manage_committee.php");
                exit();
            } else {
                $error_msg = "Database Error: Unable to assign committee role. " . mysqli_error($link);
            }
        }
    }
}

// 3. FETCH AVAILABLE OPTIONS FOR INPUT FORMS
// Fetch all existing clubs from the `club` table
$clubsQuery = "SELECT clubID, clubName FROM club ORDER BY clubName ASC";
$clubsResult = mysqli_query($link, $clubsQuery);

// Fetch all available students from the `student` table
$studentsQuery = "SELECT userID FROM student ORDER BY userID ASC";
$studentsResult = mysqli_query($link, $studentsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Club Committee - FK Student Club & Event Management</title>
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

        /* Layout Container Split */
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
            max-width: 750px;
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

        /* Form Layout Component Styling Box Matching Wireframe */
        .form-card-container {
            background-color: #ffffff;
            border: 1px solid #b2bec3;
            border-radius: 6px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.01);
        }

        .form-group-row {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 22px;
        }

        .form-group-row label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }

        .input-control-select, .input-control-date {
            width: 100%;
            padding: 12px 14px;
            font-size: 0.95rem;
            border: 1px solid #b2bec3;
            border-radius: 5px;
            background-color: #ffffff;
            outline: none;
            color: #333;
            transition: border-color 0.2s;
        }

        .input-control-select:focus, .input-control-date:focus {
            border-color: #3498db;
        }

        .form-actions-footer-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }

        .btn {
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.2s;
            min-width: 160px;
        }

        .btn-submit {
            background-color: #2ecc71;
            color: white;
            border: 1px solid #27ae60;
        }
        .btn-submit:hover {
            background-color: #27ae60;
        }

        .btn-cancel {
            background-color: #ffffff;
            color: #333;
            border: 1px solid #b2bec3;
        }
        .btn-cancel:hover {
            background-color: #f1f2f6;
        }

        .alert {
            padding: 12px 18px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: center;
            border: 1px solid transparent;
            margin-bottom: 5px;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
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
            <div class="admin-name">Admin Name</div>
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
                        <a href="manageCommittee/manage_committee.php" class="sub-nav-item">Manage Committee</a>
                        <a href="create_committee.php" class="sub-nav-item active-sub">Create Committee</a>
                    </div>
                </div>

                <a href="manageClub/manage_club.php" class="nav-item">Clubs</a>
                <a href="#" class="nav-item">Events</a>
                <a href="../module 4/admin/dashboard.php" class="nav-item">Attendance</a>
                <a href="../module 4/admin/dashboard.php" class="nav-item">Reports</a>
            </nav>
            <a href="../module1/logout.php" class="btn-logout">logout</a>
        </aside>

        <main class="main-content">
            <div class="workspace-stack">
                
                <h2 class="page-title">Create Club Committee</h2>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>

                <form action="create_committee.php" method="POST" class="form-card-container">
                    
                    <div class="form-group-row">
                        <label for="clubID">Club Name</label>
                        <select name="clubID" id="clubID" class="input-control-select" required>
                            <option value="">-- Choose Club --</option>
                            <?php 
                            if ($clubsResult && mysqli_num_rows($clubsResult) > 0) {
                                while ($club = mysqli_fetch_assoc($clubsResult)) {
                                    echo "<option value='".htmlspecialchars($club['clubID'])."'>" . htmlspecialchars($club['clubName']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group-row">
                        <label for="userID">Select Student</label>
                        <select name="userID" id="userID" class="input-control-select" required>
                            <option value="">-- Select Student ID --</option>
                            <?php 
                            if ($studentsResult && mysqli_num_rows($studentsResult) > 0) {
                                while ($student = mysqli_fetch_assoc($studentsResult)) {
                                    echo "<option value='".htmlspecialchars($student['userID'])."'>" . htmlspecialchars($student['userID']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group-row">
                        <label for="position">Position</label>
                        <select name="position" id="position" class="input-control-select" required>
                            <option value="">-- Assign Role --</option>
                            <option value="President">President</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Treasurer">Treasurer</option>
                            <option value="Committee">Committee</option>
                        </select>
                    </div>

                    <div class="form-group-row">
                        <label for="assignedDate">Assigned Date</label>
                        <input type="date" id="assignedDate" name="assignedDate" class="input-control-date" value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-actions-footer-bar">
                        <button type="submit" name="assign_committee" class="btn btn-submit">Assign Committee</button>
                        <a href="manageCommittee/manage_committee.php" class="btn btn-cancel">Cancel</a>
                    </div>

                </form>

            </div>
        </main>
    </div>

</body>
</html>
<?php 
mysqli_close($link); 
?>