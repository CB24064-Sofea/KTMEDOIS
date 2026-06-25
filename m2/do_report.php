<?php
// Initialize system session parameters
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Import centralized database configurations 
require_once dirname(__DIR__) . "/db.php";

// Initialize filter criteria metrics
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : 'All';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Dynamically formulate secure filtering queries using actual database schema columns
$query = "SELECT * FROM delivery_order WHERE 1=1";
$params = [];
$types = "";

if ($status_filter !== 'All') {
    // Mapping internal filters to the actual database field 'PO_status'
    $query .= " AND PO_status = ?";
    $params[] = ($status_filter === 'Pending') ? 'Pending' : $status_filter;
    $types .= "s";
}

if (!empty($start_date)) {
    // Map 'delivery_date' filter inputs safely to database 'created_date'
    $query .= " AND created_date >= ?";
    $params[] = $start_date . " 00:00:00";
    $types .= "s";
}

if (!empty($end_date)) {
    // Map 'delivery_date' filter inputs safely to database 'created_date'
    $query .= " AND created_date <= ?";
    $params[] = $end_date . " 23:59:59";
    $types .= "s";
}

$query .= " ORDER BY created_date DESC";

// Execute prepared parameters injection safely
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$report_result = $stmt->get_result();

// Gather localized count for verification summary boxes
$count_rows = $report_result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS - Generate DO Report</title>
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">  
    <style>
        * { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        
        .app-layout-wrapper { display: flex; flex-direction: column; width: 100%; height: 100vh; overflow: hidden; background-color: #f8fafc; }
        .lower-split-container { display: flex; flex-grow: 1; width: 100%; overflow: hidden; }
        .content-body { padding: 32px; overflow-y: auto; flex-grow: 1; }

        .filter-card { background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03); }
        .filter-grid { display: grid; grid-template-columns: repeat(3, 1fr) auto; gap: 16px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        
        label { font-size: 11px; font-weight: 700; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; }
        select, input[type="date"] { padding: 10px 14px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; color: #1a202c; outline: none; background-color: #ffffff; }
        select:focus, input:focus { border-color: #002D62; }

        .btn-filter { background-color: #002D62; color: #ffffff; padding: 11px 24px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color 0.2s; }
        .btn-filter:hover { background-color: #001f44; }
        
        .btn-print { background-color: #ffffff; border: 1px solid #cbd5e1; color: #4a5568; padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn-print:hover { background-color: #f8fafc; }

        .report-table-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
        .report-header-toolbar { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background-color: #f8fafc; padding: 14px 24px; font-size: 12px; font-weight: 700; color: #718096; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        td { padding: 16px 24px; font-size: 14px; color: #2d3748; border-bottom: 1px solid #edf2f7; }
        
        .status-pill { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-pending, .status-submitted { background-color: #ebf8ff; color: #2b6cb0; }
        .status-approved { background-color: #c6f6d5; color: #22543d; }
        .status-rejected { background-color: #fed7d7; color: #742a2a; }

        @media print {
            .topbar, .sidebar, .filter-card, .btn-print { display: none !important; }
            .app-layout-wrapper, .lower-split-container, .content-body { display: block !important; height: auto !important; overflow: visible !important; padding: 0 !important; }
            .report-table-card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>

<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>

    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>

        <div class="content-body">
            
            <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="color: #002D62; font-size: 22px; font-weight: 700;">Delivery Order Summary Reports</h2>
                    <p style="color: #718096; font-size: 14px; margin-top: 2px;">Filter, isolate, and audit historical material arrival summaries across system nodes.</p>
                </div>
                <button class="btn-print" onclick="window.print();">🖨️ Print/Save PDF</button>
            </div>

            <div class="filter-card">
                <form action="do_report.php" method="GET">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label for="status_filter">Verification Status</label>
                            <select id="status_filter" name="status_filter">
                                <option value="All" <?php echo $status_filter === 'All' ? 'selected' : ''; ?>>Show All Statuses</option>
                                <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending Validation</option>
                                <option value="Approved" <?php echo $status_filter === 'Approved' ? 'selected' : ''; ?>>Approved Records</option>
                                <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected Records</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="start_date">From (Delivery Date)</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                        </div>

                        <div class="filter-group">
                            <label for="end_date">To (Delivery Date)</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                        </div>

                        <button type="submit" class="btn-filter">Apply Filters</button>
                    </div>
                </form>
            </div>

            <div class="report-table-card">
                <div class="report-header-toolbar">
                    <span style="font-size: 14px; font-weight: 600; color: #4a5568;">
                        Filtered Output Results: <strong><?php echo $count_rows; ?></strong> entry match(es) located.
                    </span>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>DO Number</th>
                            <th>Purchase Order</th>
                            <th>Supplier ID</th>
                            <th>Target Unit</th>
                            <th>Delivery Date</th>
                            <th>Current State</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($report_result && $report_result->num_rows > 0): ?>
                            <?php while($row = $report_result->fetch_assoc()): 
                                $status_val = !empty($row['PO_status']) ? $row['PO_status'] : 'Pending';
                                $display_status = ($status_val === 'Pending') ? 'Pending' : $status_val;
                            ?>
                                <tr>
                                    <td style="font-weight: 600; color: #002D62;"><?php echo htmlspecialchars($row['DO_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['PO_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['supplier_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_ID']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['created_date'])); ?></td>
                                    <td>
                                        <span class="status-pill status-<?php echo strtolower($display_status); ?>">
                                            <?php echo htmlspecialchars($display_status); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #a0aec0; padding: 40px 0; font-style: italic;">
                                    No database matches fit your selected timeline parameters.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

</body>
</html>
<?php 
$stmt->close();
$conn->close();
?>