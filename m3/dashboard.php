<?php
// =========================================================================
// 1. SAFE SESSION INITIALIZATION (MUST BE AT THE ABSOLUTE TOP)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================================================
// 2. DATABASE CONNECTION
// =========================================================================
$servername = "127.0.0.1:3307";
$username = "root";
$password = "";
$dbname = "ktm_edois";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Get the current filename for active state styling reference
$current_page = basename($_SERVER['PHP_SELF']);

// =========================================================================
// 3. FETCH MODULE OVERVIEW METRICS
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
// 4. FETCH GLOBAL MODULE STATUS TABLE
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
            flex-direction: column; /* Stacks Topbar above App Container */
            height: 100vh;
            overflow: hidden;
            color: #333;
        }

        /* MASTER ROW CONTAINER FOR SIDEBAR + WORKSPACE CONTAINER */
        .app-container {
            display: flex;
            flex-direction: row;
            flex-grow: 1;
            width: 100%;
            overflow: hidden;
            height: calc(100vh - 70px); /* Adjusts viewport space for topbar layout height */
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
        }

        .header-area { 
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px; 
        }
        
        .header-title { 
            font-size: 28px; 
            font-weight: 700; 
            color: var(--primary-navy); 
        }

        /* Metrics Display Grid Layout */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 35px;
        }

        .metric-card {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.01);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.04);
        }

        .metric-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .metric-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark-gray);
        }

        /* Recent Records Table Card Base */
        .section-card {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.01);
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark-gray);
            margin-bottom: 20px;
        }

        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        
        th {
            background-color: #f8fafc;
            color: #4a5568;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            padding: 16px;
            border-bottom: 2px solid var(--border-color);
        }
        
        td { padding: 16px; border-bottom: 1px solid var(--border-color); color: var(--dark-gray); vertical-align: middle; }
        tr:hover td { background-color: #f8fafc; }

        /* Badge Decorators */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        .status-submitted { background-color: var(--status-submitted); color: var(--text-submitted); }
        .status-under-review { background-color: var(--status-review); color: var(--text-review); }
        .status-finance-review { background-color: var(--status-review); color: var(--text-review); }
        .status-approved { background-color: var(--status-approved); color: var(--text-approved); }
        .status-rejected { background-color: var(--status-rejected); color: var(--text-rejected); }

        .text-bold { font-weight: 600; color: #000; }

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

    <?php include 'topbar.php'; ?>

    <div class="app-container">
        
        <?php include '../sidebar.php'; ?>

        <main class="workspace">
            
            <div class="header-area">
                <h1 class="header-title">Overview Dashboard</h1>
            </div> 

            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-label">Document Orders Active</div>
                    <div class="metric-value"><?php echo number_format($total_do); ?></div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-label">Total Claims Submitted</div>
                    <div class="metric-value"><?php echo number_format($total_invoices); ?></div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-label">Pending Internal Reviews</div>
                    <div class="metric-value"><?php echo number_format($pending_reviews); ?></div>
                </div>
            </div>

            <div class="section-card">
                <h3 class="section-title">Latest Invoice Submissions</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice Number</th>
                                <th>DO Reference</th>
                                <th>Claim Value</th>
                                <th>Current Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <?php 
                                        $status_slug = strtolower(str_replace(' ', '-', $row['invoice_status']));
                                        $status_class = 'status-' . $status_slug;
                                    ?>
                                    <tr>
                                        <td><span class="text-bold"><?php echo htmlspecialchars($row['invoice_num']); ?></span></td>
                                        <td><code><?php echo htmlspecialchars($row['DO_ID']); ?></code></td>
                                        <td><span class="text-bold">MYR <?php echo number_format($row['total'], 2); ?></span></td>
                                        <td>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($row['invoice_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 24px; color: var(--text-muted);">
                                        No active submittals discovered within database query parameters.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <footer class="system-footer">
                © 2026 KTMEDOIS INTEGRATED PORTAL
            </footer>
        </main>

    </div>

</body>
</html>
<?php
$conn->close();
?>