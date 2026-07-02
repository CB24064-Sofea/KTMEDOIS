<?php
// =========================================================================
// FILE        : do_list.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : doInspectionListUI — used by Procurement Officer / Staff
// DESCRIPTION : Lists all delivery orders for inspection. Supports search
//               (matches the topbar global search redirect target:
//               m4/do_list.php?search=...) and status filtering.
//               Officers drill into do_details.php to inspect a single DO.
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/DOReviewController.php";

// ── Session guard ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['staff_auth']) && !isset($_SESSION['vendor_auth'])) {
    header("Location: " . app_url('m1/login.php')); exit;
}

$staffId = $_SESSION['staff_auth']['staff_id'] ?? $_SESSION['staff_auth']['staff_ID'] ?? 'STF001';

$controller   = new DOReviewController($conn, $staffId);
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$stats        = $controller->getDOStats();
$result       = $controller->fetchDeliveryOrders($search, $statusFilter);
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS – DO Inspections List</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/KTMEDOIS/sidebar.css">
    <style>
        .app-layout-wrapper    { display:flex; flex-direction:column; width:100%; height:100vh; background:#f3f5f9; }
        .lower-split-container { display:flex; flex-grow:1; overflow:hidden; }
        .content-body          { flex-grow:1; padding:36px; overflow-y:auto; font-family:'Inter',sans-serif; color:#333; }

        :root { --navy:#002D62; --border:#e2e8f0; --muted:#718096; }
        .page-header  { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-title   { font-size:26px; font-weight:700; color:var(--navy); }

        /* ── STAT CARDS ────────────────────────────────────────────────── */
        .stats-row      { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:14px; margin-bottom:24px; }
        .stat-card      { background:#fff; border-radius:10px; border:1px solid var(--border); padding:18px; text-decoration:none; display:block; transition:box-shadow 0.2s; color:inherit; }
        .stat-card:hover{ box-shadow:0 4px 14px rgba(0,0,0,0.08); }
        .stat-card.active-filter { border-color:var(--navy); box-shadow:0 0 0 2px rgba(0,45,98,0.15); }
        .stat-label     { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.6px; margin-bottom:8px; font-weight:600; }
        .stat-number    { font-size:28px; font-weight:700; }

        /* ── TOOLBAR ───────────────────────────────────────────────────── */
        .toolbar      { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap; }
        .search-form  { display:flex; gap:8px; }
        .search-input { padding:10px 15px; border-radius:7px; border:1px solid var(--border); font-size:14px; outline:none; font-family:'Inter',sans-serif; width:300px; }
        .search-input:focus { border-color:var(--navy); }
        .btn-search   { background:var(--navy); color:#fff; border:none; padding:10px 18px; border-radius:7px; font-weight:600; font-size:14px; cursor:pointer; }
        .btn-clear    { background:#f1f5f9; color:#4a5568; border:1px solid var(--border); padding:10px 14px; border-radius:7px; font-weight:600; font-size:14px; text-decoration:none; display:flex; align-items:center; }

        /* ── TABLE ─────────────────────────────────────────────────────── */
        .table-card  { background:#fff; border-radius:10px; border:1px solid var(--border); overflow:hidden; }
        table        { width:100%; border-collapse:collapse; font-size:14px; }
        th           { background:#f8fafc; color:#4a5568; font-weight:700; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; padding:14px 18px; border-bottom:2px solid var(--border); text-align:left; }
        td           { padding:15px 18px; border-bottom:1px solid var(--border); color:#1a1a1a; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td  { background:#f8fafc; }

        .badge            { display:inline-block; padding:4px 12px; border-radius:50px; font-size:12px; font-weight:600; }
        .p-pending        { background:#fef3c7; color:#d97706; }
        .p-approved       { background:#ecfdf5; color:#059669; }
        .p-cancelled      { background:#fef2f2; color:#dc2626; }
        .p-completed      { background:#e0f2fe; color:#0369a1; }

        .btn-inspect      { display:inline-flex; align-items:center; gap:6px; background:var(--navy); color:#fff; text-decoration:none; padding:7px 14px; border-radius:6px; font-weight:600; font-size:13px; transition:opacity 0.2s; }
        .btn-inspect:hover{ opacity:0.85; }

        .empty-cell  { text-align:center; padding:50px; color:var(--muted); font-size:14px; }
        .system-footer { text-align:center; font-size:11px; color:#a0aec0; padding-top:32px; letter-spacing:1px; }
    </style>
</head>
<body>
<div class="app-layout-wrapper">
    <?php include('../topbar.php'); ?>
    <div class="lower-split-container">
        <?php include('../sidebar.php'); ?>
        <div class="content-body">

            <div class="page-header">
                <h1 class="page-title">&#128230; DO Inspections List</h1>
            </div>

            <!-- ── Stat cards / status filter ──────────────────────────────── -->
            <div class="stats-row">
                <?php
                $cards = [
                    ['label'=>'Total Delivery Orders', 'val'=>$stats['total'],    'key'=>'all',       'color'=>'#002D62'],
                    ['label'=>'Pending Inspection',     'val'=>$stats['pending'], 'key'=>'Pending',   'color'=>'#d97706'],
                    ['label'=>'Approved',               'val'=>$stats['approved'],'key'=>'Approved',  'color'=>'#059669'],
                    ['label'=>'Cancelled',              'val'=>$stats['rejected'],'key'=>'Cancelled', 'color'=>'#dc2626'],
                ];
                foreach ($cards as $c):
                    $active = ($statusFilter === $c['key']) ? 'active-filter' : '';
                ?>
                <a href="do_list.php?status=<?php echo urlencode($c['key']); ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="stat-card <?php echo $active; ?>">
                    <div class="stat-label"><?php echo $c['label']; ?></div>
                    <div class="stat-number" style="color:<?php echo $c['color']; ?>"><?php echo $c['val']; ?></div>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- ── Search toolbar ───────────────────────────────────────────── -->
            <div class="toolbar">
                <form method="GET" action="do_list.php" class="search-form">
                    <?php if ($statusFilter !== 'all'): ?>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                    <?php endif; ?>
                    <input type="text" name="search" class="search-input"
                           placeholder="Search by DO No., Supplier, or PO No..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-search">&#128269; Search</button>
                    <?php if (!empty($search) || $statusFilter !== 'all'): ?>
                        <a href="do_list.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>DO Number</th>
                            <th>PO Reference</th>
                            <th>Supplier</th>
                            <th>Project Ref.</th>
                            <th>Date Created</th>
                            <th>Status</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $slug = 'p-' . strtolower($row['PO_status']);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['DO_ID']); ?></strong></td>
                            <td><code style="font-size:12px;color:#718096;"><?php echo htmlspecialchars($row['PO_number']); ?></code></td>
                            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                            <td style="font-size:13px;color:#718096;"><?php echo htmlspecialchars($row['project_reference'] ?? '—'); ?></td>
                            <td style="font-size:13px;color:#718096;"><?php echo date('d M Y', strtotime($row['created_date'])); ?></td>
                            <td><span class="badge <?php echo $slug; ?>"><?php echo htmlspecialchars($row['PO_status']); ?></span></td>
                            <td style="text-align:center;">
                                <a href="do_details.php?id=<?php echo urlencode($row['DO_ID']); ?>" class="btn-inspect">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                    Inspect
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-cell">&#128203; No delivery orders found<?php echo $search ? ' for "' . htmlspecialchars($search) . '"' : ''; ?>.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="system-footer">© <?php echo date('Y'); ?> Keretapi Tanah Melayu Berhad &nbsp;|&nbsp; KTM eDOIS Internal Review Module</div>

        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
