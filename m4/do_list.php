<?php
// =========================================================================
// MODULE 4 — DO Review List (SDD_CLS_401 extension)
// =========================================================================
include 'db.php';
$current_page = basename($_SERVER['PHP_SELF']);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where  = '';
if (!empty($search)) {
    $s = $conn->real_escape_string($search);
    $where = "WHERE d.DO_ID LIKE '%$s%' OR s.supplier_name LIKE '%$s%'";
}

$result = $conn->query(
    "SELECT d.DO_ID, d.PO_ID, d.PO_status, d.created_date, s.supplier_name
     FROM delivery_order d
     INNER JOIN supplier s ON d.supplier_ID = s.supplier_ID
     $where
     ORDER BY d.created_date DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Portal - DO Review</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color:#f3f5f9; --card-bg:#ffffff; --primary-navy:#002D62; --dark-gray:#1a1a1a; --border-color:#e2e8f0; --text-muted:#718096; }
        * { box-sizing:border-box; margin:0; padding:0; font-family:'Inter',sans-serif; }
        body { background-color:var(--bg-color); display:flex; height:100vh; overflow:hidden; color:#333; }
        .workspace { flex-grow:1; padding:40px; overflow-y:auto; max-width:1200px; margin:0 auto; width:100%; display:flex; flex-direction:column; min-height:100vh; }
        .header-area { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
        .header-title { font-size:28px; font-weight:700; color:var(--primary-navy); }
        .logo-container { height:50px; display:flex; align-items:center; margin-left:auto; }
        .logo-container img { height:100%; width:auto; object-fit:contain; }
        .search-container { margin-bottom:25px; width:100%; max-width:400px; }
        .search-input { width:100%; padding:12px 16px; border-radius:8px; border:1px solid var(--border-color); font-size:14px; outline:none; transition:border-color 0.2s; }
        .search-input:focus { border-color:var(--primary-navy); box-shadow:0 0 0 3px rgba(0,45,98,0.1); }
        .card { background:var(--card-bg); border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.02); border:1px solid var(--border-color); padding:24px; margin-bottom:20px; }
        .table-responsive { width:100%; overflow-x:auto; }
        table { width:100%; border-collapse:collapse; text-align:left; font-size:14px; }
        th { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:16px; border-bottom:2px solid var(--border-color); }
        td { padding:16px; border-bottom:1px solid var(--border-color); color:var(--dark-gray); vertical-align:middle; }
        tr:hover td { background:#f8fafc; }
        .text-bold { font-weight:600; color:#000; }
        .badge { display:inline-block; padding:6px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .status-approved { background:#ecfdf5; color:#059669; }
        .status-pending  { background:#fef3c7; color:#d97706; }
        .status-rejected { background:#fef2f2; color:#dc2626; }
        .btn-action { display:inline-block; background:#f1f5f9; color:var(--primary-navy); text-decoration:none; padding:8px 14px; border-radius:6px; font-weight:600; font-size:13px; border:1px solid #cbd5e1; transition:all 0.2s; }
        .btn-action:hover { background:var(--primary-navy); color:#fff; border-color:var(--primary-navy); }
        .system-footer { text-align:center; font-size:11px; color:#a0aec0; margin-top:auto; padding-top:40px; letter-spacing:1px; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="workspace">
    <div class="header-area">
        <h1 class="header-title">Delivery Order Review</h1>
        <div class="logo-container"><img src="ktmb_logo.jpg" alt="KTMB Logo"></div>
    </div>

    <div class="search-container">
        <form method="GET" action="do_list.php">
            <input type="text" name="search" class="search-input"
                   placeholder="Search DO Number or Supplier..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </form>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>DO Number</th>
                        <th>Supplier</th>
                        <th>PO Reference</th>
                        <th>Submission Date</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()):
                        $slug = 'status-' . strtolower($row['PO_status']);
                    ?>
                    <tr>
                        <td><span class="text-bold"><?php echo htmlspecialchars($row['DO_ID']); ?></span></td>
                        <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                        <td><code><?php echo htmlspecialchars($row['PO_ID']); ?></code></td>
                        <td><?php echo date('d M Y', strtotime($row['created_date'])); ?></td>
                        <td><span class="badge <?php echo $slug; ?>"><?php echo htmlspecialchars($row['PO_status']); ?></span></td>
                        <td style="text-align:center;">
                            <a href="do_details.php?id=<?php echo urlencode($row['DO_ID']); ?>" class="btn-action">View Details</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:#718096;">No delivery orders found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="system-footer">© 2026 KTMEDOIS INTEGRATED PORTAL</footer>
</main>
<script>
// Live search on keyup
document.querySelector('.search-input').addEventListener('keyup', function() {
    this.form.submit();
});
</script>
</body>
</html>
<?php $conn->close(); ?>
