<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . "/db.php";

/**
 * Class DeliveryOrderRepository
 * Handles data collection operations for Delivery Orders using OOP principles
 */
class DeliveryOrderRepository {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Fetch filtered delivery order rows
     */
    public function getFilteredReports($status, $startDate, $endDate) {
        $query = "SELECT * FROM delivery_order WHERE 1=1";
        $params = [];
        $types = "";

        // SWITCHED: Now filtering by delivery_status verification state instead of PO baseline
        if ($status !== 'All') {
            $query .= " AND delivery_status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if (!empty($startDate)) {
            $query .= " AND created_date >= ?";
            $params[] = $startDate . " 00:00:00";
            $types .= "s";
        }

        if (!empty($endDate)) {
            $query .= " AND created_date <= ?";
            $params[] = $endDate . " 23:59:59";
            $types .= "s";
        }

        $query .= " ORDER BY created_date DESC";

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }
}

// Instantiate Object Components
$reportManager = new DeliveryOrderRepository($conn);

$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : 'All';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Retrieve matching records object
$report_result = $reportManager->getFilteredReports($status_filter, $start_date, $end_date);
$count_rows = $report_result ? $report_result->num_rows : 0;
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
        label { font-size: 11px; font-weight: 700; color: #718096; text-transform: uppercase; }
        select, input[type="date"] { padding: 10px 14px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; background-color: #ffffff; }
        .btn-filter { background-color: #002D62; color: #ffffff; padding: 11px 24px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-print { background-color: #ffffff; border: 1px solid #cbd5e1; color: #4a5568; padding: 10px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .report-table-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .report-header-toolbar { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background-color: #f8fafc; padding: 14px 24px; font-size: 12px; font-weight: 700; color: #718096; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        td { padding: 16px 24px; font-size: 14px; color: #2d3748; border-bottom: 1px solid #edf2f7; }
        .status-pill { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-pending { background-color: #fef3c7; color: #d97706; } /* Adjusted to Warm Amber for waiting state */
        .status-approved { background-color: #c6f6d5; color: #22543d; }
        .status-rejected { background-color: #fed7d7; color: #742a2a; }
        @media print {
            .topbar, .sidebar, .filter-card, .btn-print { display: none !important; }
            .app-layout-wrapper, .lower-split-container, .content-body { display: block !important; height: auto !important; padding: 0 !important; }
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
                    <p style="color: #718096; font-size: 14px; margin-top: 2px;">Filter and audit historical material arrival verification metrics.</p>
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
                            <label for="start_date">From (Submission Date)</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                        </div>

                        <div class="filter-group">
                            <label for="end_date">To (Submission Date)</label>
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
                            <th>Submission Date</th>
                            <th>Current State</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($report_result && $report_result->num_rows > 0): ?>
                            <?php while($row = $report_result->fetch_assoc()): 
                                // READ FROM SPECIFIC VERIFICATION STATUS
                                $status_val = !empty($row['delivery_status']) ? $row['delivery_status'] : 'Pending';
                            ?>
                                <tr>
                                    <td style="font-weight: 600; color: #002D62;"><?php echo htmlspecialchars($row['DO_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['PO_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['supplier_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_ID']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['created_date'])); ?></td>
                                    <td>
                                        <span class="status-pill status-<?php echo strtolower($status_val); ?>">
                                            <?php echo htmlspecialchars($status_val); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #a0aec0; padding: 40px 0; font-style: italic;">
                                    No database records found.
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