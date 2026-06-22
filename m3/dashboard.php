<?php
// =========================================================================
// 1. DATABASE CONNECTION
// =========================================================================
$servername = "127.0.0.1:3307";
$username = "root";
$password = "";
$dbname = "ktm_edois";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Get the current filename for active state styling
$current_page = basename($_SERVER['PHP_SELF']);

// =========================================================================
// 2. FETCH MODULE OVERVIEW METRICS
// =========================================================================
// Document Orders Metric
$do_res = $conn->query("SELECT COUNT(*) as count FROM invoice WHERE DO_ID IS NOT NULL AND DO_ID != ''");
$total_do = $do_res ? $do_res->fetch_assoc()['count'] : 0;

// Total Invoices Metric
$inv_res = $conn->query("SELECT COUNT(*) as count FROM invoice");
$total_invoices = $inv_res ? $inv_res->fetch_assoc()['count'] : 0;

// Internal Review Status Metric
$review_res = $conn->query("SELECT COUNT(*) as count FROM invoice WHERE invoice_status LIKE '%Review%'");
$pending_reviews = $review_res ? $review_res->fetch_assoc()['count'] : 0;

// =========================================================================
// 3. FETCH GLOBAL MODULE STATUS TABLE
// =========================================================================
$sql = "SELECT i.invoice_num, i.DO_ID, i.total, i.invoice_status 
        FROM invoice i 
        ORDER BY i.invoice_ID DESC LIMIT 5";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Portal - Overview Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f3f5f9;
            --card-bg: #ffffff;
            --primary-navy: #002D62;
            --dark-gray: #1a1a1a;
            --border-color: #e2e8f0;
            --text-muted: #718096;
            
            /* Status Badge Colors */
            --status-submitted: #eef2ff; --text-submitted: #4f46e5;
            --status-review: #fef3c7; --text-review: #d97706;
            --status-approved: #ecfdf5; --text-approved: #059669;
            --status-rejected: #fef2f2; --text-rejected: #dc2626;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            display: flex;
            height: 100vh;
            overflow: hidden;
            color: #333;
        }

        /* =========================================================================
           NEW SIDEBAR DESIGN IMPLEMENTATION
           ========================================================================= */
        .sidebar {
            width: 260px;
            background-color: #fdfdfd;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px 24px;
            height: 100vh;
            flex-shrink: 0;
        }

        .sidebar-brand h2 {
            font-size: 20px;
            font-weight: 700;
            color: #002D62;
            letter-spacing: 0.5px;
            margin-bottom: 40px;
            text-transform: uppercase;
        }

        .nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            flex-grow: 1;
            padding: 0;
        }

        .nav-item {
            display: block;
            padding: 14px 16px;
            text-decoration: none;
            color: #757575;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .nav-item:hover {
            background-color: #f1f5f9;
            color: #1a1a1a;
        }

        .nav-item.active {
            background-color: #1e1e1e;
            color: #ffffff;
            font-weight: 600;
        }

        .logout-btn {
            background-color: #eaedf2;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-weight: 600;
            color: #4a5568;
            cursor: pointer;
            text-align: center;
            width: 100%;
            margin-top: auto; 
            transition: background-color 0.2s;
        }

        .logout-btn:hover {
            background-color: #e2e8f0;
        }

        /* =========================================================================
           WORKSPACE MAIN CONTENT STYLES
           ========================================================================= */
        .workspace {
            flex-grow: 1;
            padding: 40px;
            overflow-y: auto;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header-area { 
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px; 
        }
        
        .header-title { 
            font-size: 28px; 
            font-weight: 700; 
            color: var(--primary-navy); 
        }

        .logo-container {
            height: 50px;
            display: flex;
            align-items: center;
            margin-left: auto;
        }

        .logo-container img {
            height: 100%;
            width: auto;
            object-fit: contain;
        }

        .system-footer { 
            text-align: center; 
            font-size: 11px; 
            color: #a0aec0; 
            margin-top: auto; 
            padding-top: 40px; 
            letter-spacing: 1px; 
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-brand">
                <h2>KTM Portal</h2>
            </div>
            
            <ul class="nav-list">
                <li>
                    <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                        Overview Dashboard
                    </a>
                </li>
                
                <li>
                    <a href="document_order.php" class="nav-item <?php echo ($current_page == 'document_order.php') ? 'active' : ''; ?>">
                        Document Order
                    </a>
                </li>

                <li>
                    <a href="create_inv.php" class="nav-item <?php echo ($current_page == 'create_inv.php') ? 'active' : ''; ?>">
                        Invoices and Claim
                    </a>
                </li>

                <li>
                    <a href="internal_review.php" class="nav-item <?php echo ($current_page == 'internal_review.php') ? 'active' : ''; ?>">
                        Internal Review
                    </a>
                </li>
            </ul>
        </div>

        <div>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>

    <main class="workspace">
        
        <div class="header-area">
            <h1 class="header-title">Overview Dashboard</h1>
            
            <div class="logo-container">
                <img src="ktmb_logo.jpg" alt="KTMB Corporate Logo">
            </div>
        </div> 

        <div class="dashboard-empty-body">
            </div>

        <footer class="system-footer">
            © 2026 KTMEDOIS INTEGRATED PORTAL
        </footer>
    </main>

</body>
</html>
<?php
$conn->close();
?>