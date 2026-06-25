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

$totalClubs = 0;
$activeClubs = 0;
$totalStudentsJoined = 450; 

$totalQuery = "SELECT COUNT(*) as total FROM club";
$totalResult = mysqli_query($link, $totalQuery);
if ($totalResult) {
    $row = mysqli_fetch_assoc($totalResult);
    $totalClubs = $row['total'];
}

$activeQuery = "SELECT COUNT(*) as active FROM club WHERE status = 'Active'";
$activeResult = mysqli_query($link, $activeQuery);
if ($activeResult) {
    $row = mysqli_fetch_assoc($activeResult);
    $activeClubs = $row['active'];
}


$userQuery = "SELECT profilePhoto FROM user WHERE role = 'Admin' LIMIT 1";
$userResult = mysqli_query($link, $userQuery);

if ($userResult && mysqli_num_rows($userResult) > 0) {
    $userRow = mysqli_fetch_assoc($userResult);
    if (!empty($userRow['profilePhoto'])) {
        $user_blob_string = base64_encode($userRow['profilePhoto']);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FK Student Club & Event Management</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .nav-item, .btn-logout {
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

        .nav-item.active {
            background-color: #3498db;
            color: white;
            border-color: #2980b9;
            font-weight: bold;
        }

        .btn-logout {
            margin-top: auto;
            background-color: #feeaee;
            color: #c0392b;
            border-color: #fab1a0;
            text-align: center;
            font-weight: 600;
        }

        .btn-logout:hover {
            background-color: #e74c3c;
            color: white;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .dashboard-stack {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .page-title {
            text-align: center;
            font-size: 1.5rem;
            color: #2c3e50;
            border: 1px solid #b2bec3;
            background-color: #ffffff;
            padding: 12px;
            border-radius: 5px;
        }

        .metrics-panel {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 6px;
            border: 1px solid #b2bec3;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .metric-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #dcdde1;
            border-radius: 5px;
            padding: 15px 25px;
            background-color: #ffffff;
        }

        .metric-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: #4b6584;
        }

        .metric-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2c3e50;
            background-color: #f1f2f6;
            padding: 4px 16px;
            border-radius: 4px;
            border: 1px solid #dcdde1;
            min-width: 60px;
            text-align: center;
        }

        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .chart-card {
            background-color: #ffffff;
            border: 1px solid #b2bec3;
            border-radius: 6px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 340px;
        }

        .chart-title {
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
            width: 100%;
        }

        .chart-container {
            position: relative;
            flex: 1;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
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
                <a href="admin_dashboard.php" class="nav-item active">Dashboard</a>
                <a href="manageCommittee/manage_committee.php" class="nav-item">Committee</a>
                <a href="manageClub/manage_club.php" class="nav-item">Clubs</a>
                <a href="#" class="nav-item">Events</a>
                <a href="../module 4/admin/dashboard.php" class="nav-item">Attendance</a>
                <a href="../module 4/admin/reports.php" class="nav-item">Reports</a>
            </nav>
            <a href="../module1/logout.php" class="btn-logout">logout</a>
        </aside>

        <main class="main-content">
            <div class="dashboard-stack">
                
                <h2 class="page-title">Dashboard</h2>
                
                <section class="metrics-panel">
                    <div class="metric-strip">
                        <span class="metric-label">Total Clubs</span>
                        <span class="metric-value"><?php echo $totalClubs; ?></span>
                    </div>
                    
                    <div class="metric-strip">
                        <span class="metric-label">Active Clubs</span>
                        <span class="metric-value"><?php echo $activeClubs; ?></span>
                    </div>
                    
                    <div class="metric-strip">
                        <span class="metric-label">Total Students Joined</span>
                        <span class="metric-value"><?php echo $totalStudentsJoined; ?></span>
                    </div>
                </section>

                <div class="charts-row">
                    
                    <div class="chart-card">
                        <h3 class="chart-title">Club Participation Overview</h3>
                        <div class="chart-container">
                            <canvas id="participationBarChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3 class="chart-title">User Distribution by Role</h3>
                        <div class="chart-container">
                            <canvas id="userPieChart"></canvas>
                        </div>
                    </div>
                    
                </div>

            </div>
        </main>
    </div>

    <script>
        const barCtx = document.getElementById('participationBarChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Inovators Club', 'Business Club', 'Earth Club', 'Debate Club'], 
                datasets: [{
                    label: 'Students Registered',
                    data: [120, 165, 85, 140], 
                    backgroundColor: '#2c3e50',
                    borderColor: '#1a252f',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#e0e0e0' }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false } 
                }
            }
        });

        const pieCtx = document.getElementById('userPieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: ['Students', 'Committee', 'Advisors'], 
                datasets: [{
                    data: [450, 50, 5], 
                    backgroundColor: [
                        '#2c3e50', 
                        '#7f8c8d',
                        '#bdc3c7'  
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom', 
                        labels: {
                            boxWidth: 12,
                            padding: 15,
                            font: { size: 12 }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
<?php 
mysqli_close($link); 
?>