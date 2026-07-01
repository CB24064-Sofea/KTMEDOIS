<?php
// =========================================================================
// FILE        : notifications.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : NotificationUI — used by Supplier, Officer, Finance
// DESCRIPTION : View all system notifications. Displays the notification
//               table with type, content, status (Unread/Read), and timestamp.
//               Officers can mark notifications as read or mark all as read.
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";

// ── Session guard ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['staff_auth'])) {
    header("Location: " . app_url('m1/staff_login.php')); exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// ── Mark as read if requested ─────────────────────────────────────────────────
if (isset($_POST['mark_read']) && !empty($_POST['noti_id'])) {
    $nid = intval($_POST['noti_id']);
    $stmt = $conn->prepare("UPDATE notification SET status = 'Read' WHERE noti_ID = ?");
    $stmt->bind_param("i", $nid);
    $stmt->execute();
    $stmt->close();
}

// ── Mark ALL as read ──────────────────────────────────────────────────────────
if (isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE notification SET status = 'Read' WHERE status = 'Unread'");
}

// ── Fetch notifications ───────────────────────────────────────────────────────
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
if ($filter === 'unread') {
    $result = $conn->query("SELECT * FROM notification WHERE status = 'Unread' ORDER BY created_at DESC");
} else {
    $result = $conn->query("SELECT * FROM notification ORDER BY created_at DESC");
}

$unread_count = (int)$conn->query("SELECT COUNT(*) as c FROM notification WHERE status = 'Unread'")->fetch_assoc()['c'];
$total_count  = (int)$conn->query("SELECT COUNT(*) as c FROM notification")->fetch_assoc()['c'];

// Map notification type to icon
function notiIcon($type) {
    $map = [
        'Invoice Approved'           => '&#10003;',
        'Invoice Rejected'           => '&#10007;',
        'Additional Info Requested'  => '&#8635;',
        'Reviewer Assigned'          => '&#128204;',
        'Payment Processing'         => '&#128184;',
        'Payment Completed'          => '&#9989;',
    ];
    return $map[$type] ?? '&#128276;';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM eDOIS – Notifications</title>
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
        .stats-row   { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:24px; max-width:360px; }
        .stat-card   { background:#fff; border-radius:10px; border:1px solid var(--border); padding:18px; }
        .stat-label  { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.6px; margin-bottom:8px; font-weight:600; }
        .stat-number { font-size:28px; font-weight:700; }

        /* ── FILTER BAR ────────────────────────────────────────────────── */
        .filter-bar   { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:10px; }
        .filter-links { display:flex; gap:8px; }
        .filter-btn   { padding:8px 18px; border-radius:7px; font-size:13px; font-weight:600; text-decoration:none; border:1px solid var(--border); background:#fff; color:#4a5568; transition:all 0.2s; }
        .filter-btn:hover  { background:#f1f5f9; }
        .filter-btn.active { background:var(--navy); color:#fff; border-color:var(--navy); }
        .btn-mark-all { background:#f1f5f9; color:#4a5568; border:1px solid var(--border); padding:8px 16px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; transition:background 0.2s; }
        .btn-mark-all:hover { background:#e2e8f0; }

        /* ── NOTIFICATION CARDS ────────────────────────────────────────── */
        .noti-card    { background:#fff; border-radius:10px; border:1px solid var(--border); margin-bottom:10px; padding:18px 20px; display:flex; align-items:flex-start; gap:16px; transition:box-shadow 0.2s; }
        .noti-card.unread { border-left:4px solid var(--navy); }
        .noti-card:hover  { box-shadow:0 2px 8px rgba(0,0,0,0.06); }

        .noti-icon    { width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
        .icon-approved { background:#ecfdf5; color:#059669; }
        .icon-rejected { background:#fef2f2; color:#dc2626; }
        .icon-review   { background:#fef3c7; color:#d97706; }
        .icon-assign   { background:#e0f2fe; color:#0369a1; }
        .icon-default  { background:#f1f5f9; color:#718096; }

        .noti-body   { flex:1; }
        .noti-type   { font-size:12px; font-weight:700; color:var(--navy); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; }
        .noti-content { font-size:14px; color:#1a1a1a; line-height:1.5; margin-bottom:6px; }
        .noti-time   { font-size:12px; color:var(--muted); display:flex; align-items:center; gap:10px; }
        .badge-unread { background:#eef2ff; color:#4f46e5; padding:2px 10px; border-radius:50px; font-size:11px; font-weight:600; }
        .badge-read   { background:#f1f5f9; color:#718096; padding:2px 10px; border-radius:50px; font-size:11px; font-weight:600; }

        .btn-mark    { background:#f1f5f9; border:1px solid var(--border); padding:6px 14px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; color:#4a5568; flex-shrink:0; }
        .btn-mark:hover { background:#e2e8f0; }

        .empty-state { text-align:center; padding:60px; color:var(--muted); font-size:14px; }
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
                <h1 class="page-title">&#128276; Notifications</h1>
            </div>

            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-label">Unread</div>
                    <div class="stat-number" style="color:#4f46e5;"><?php echo $unread_count; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total</div>
                    <div class="stat-number" style="color:var(--navy);"><?php echo $total_count; ?></div>
                </div>
            </div>

            <div class="filter-bar">
                <div class="filter-links">
                    <a href="notifications.php?filter=all"    class="filter-btn <?php echo $filter==='all'?'active':''; ?>">All Notifications</a>
                    <a href="notifications.php?filter=unread" class="filter-btn <?php echo $filter==='unread'?'active':''; ?>">
                        Unread
                        <?php if ($unread_count > 0): ?><span style="background:#dc2626;color:#fff;border-radius:50%;padding:1px 7px;font-size:10px;margin-left:5px;"><?php echo $unread_count; ?></span><?php endif; ?>
                    </a>
                </div>
                <?php if ($unread_count > 0): ?>
                <form method="POST" action="notifications.php?filter=<?php echo $filter; ?>">
                    <button type="submit" name="mark_all_read" class="btn-mark-all">&#10003; Mark All as Read</button>
                </form>
                <?php endif; ?>
            </div>

            <?php if ($result && $result->num_rows > 0):
                while ($noti = $result->fetch_assoc()):
                    $isUnread = $noti['status'] === 'Unread';
                    $type = $noti['type'];
                    $iconClass = 'icon-default';
                    if (strpos($type,'Approved') !== false || $type === 'Payment Completed') $iconClass = 'icon-approved';
                    elseif (strpos($type,'Rejected') !== false) $iconClass = 'icon-rejected';
                    elseif (strpos($type,'Review') !== false || strpos($type,'Info') !== false) $iconClass = 'icon-review';
                    elseif (strpos($type,'Assign') !== false || strpos($type,'Payment') !== false) $iconClass = 'icon-assign';
            ?>
            <div class="noti-card <?php echo $isUnread ? 'unread' : ''; ?>">
                <div class="noti-icon <?php echo $iconClass; ?>"><?php echo notiIcon($type); ?></div>
                <div class="noti-body">
                    <div class="noti-type"><?php echo htmlspecialchars($noti['type']); ?></div>
                    <div class="noti-content"><?php echo htmlspecialchars($noti['content']); ?></div>
                    <div class="noti-time">
                        <span><?php echo date('d M Y, H:i', strtotime($noti['created_at'])); ?></span>
                        <span class="<?php echo $isUnread ? 'badge-unread' : 'badge-read'; ?>"><?php echo $noti['status']; ?></span>
                    </div>
                </div>
                <?php if ($isUnread): ?>
                <form method="POST" action="notifications.php?filter=<?php echo $filter; ?>">
                    <input type="hidden" name="noti_id" value="<?php echo $noti['noti_ID']; ?>">
                    <button type="submit" name="mark_read" class="btn-mark">Mark Read</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endwhile;
            else: ?>
                <div class="empty-state">&#128276; No notifications found.</div>
            <?php endif; ?>

            <div class="system-footer">© <?php echo date('Y'); ?> Keretapi Tanah Melayu Berhad &nbsp;|&nbsp; KTM eDOIS Internal Review Module</div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
