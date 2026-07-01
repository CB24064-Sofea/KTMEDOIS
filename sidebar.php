<?php
// Initialize session safely if not done yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('app_url')) {
    require_once __DIR__ . '/db.php';
}

// Get the current filename to track active item styling
$current_page = basename($_SERVER['PHP_SELF']);

// Establish defaults
$userRole = 'Guest';
$userName = 'Anonymous';

// Determine logged-in context
if (isset($_SESSION['vendor_auth'])) {
    $userRole = 'Supplier';
    $userName = $_SESSION['vendor_auth']['company_name'] ?? 'Vendor Entity';
} else if (isset($_SESSION['staff_auth'])) {
    $userRole = trim($_SESSION['staff_auth']['sub_role'] ?? $_SESSION['staff_auth']['role'] ?? 'Staff');
    $userName = $_SESSION['staff_auth']['name'] ?? 'KTMB Staff';
}
?>
<style>
    :root {
        --sidebar-width: 270px;
        --sidebar-collapsed-width: 72px;
        --ktmb-navy: #0e1e38;
        --ktmb-navy-hover: #172b4d;
        --ktmb-gold: #ffc72c;
    }

    .sidebar {
        width: var(--sidebar-width);
        background-color: var(--ktmb-navy);
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 24px 16px;
        height: 100%;
        box-sizing: border-box;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-x: hidden;
        position: relative;
        flex-shrink: 0;
        font-family: sans-serif;
    }

    /* Collapsed State Styles */
    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
        padding: 24px 10px;
    }

    .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 25px;
        padding: 0 8px;
        flex-shrink: 0;
    }

    .sidebar-brand h2 {
        font-size: 20px;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: 0.5px;
        margin: 0;
        white-space: nowrap;
        transition: opacity 0.2s ease;
    }

    .sidebar.collapsed .sidebar-brand h2 {
        opacity: 0;
        pointer-events: none;
        width: 0;
    }

    .toggle-menu-btn {
        background: none;
        border: none;
        color: #cbd5e1;
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s;
    }

    .toggle-menu-btn:hover {
        background-color: var(--ktmb-navy-hover);
        color: #ffffff;
    }

    .nav-container {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 4px;
    }

    .nav-container::-webkit-scrollbar {
        width: 4px;
    }
    .nav-container::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }

    .nav-section-title {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: #94a3b8;
        margin: 22px 0 6px 8px;
        font-weight: 700;
        white-space: nowrap;
        transition: opacity 0.2s ease;
    }

    .sidebar.collapsed .nav-section-title {
        opacity: 0;
    }

    .nav-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 0;
        margin: 0;
    }

    .nav-item {
        display: flex;
        align-items: center;
        padding: 11px 12px;
        text-decoration: none;
        color: #e2e8f0;
        font-size: 13.5px;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .nav-item:hover {
        background-color: var(--ktmb-navy-hover);
        color: var(--ktmb-gold);
    }

    .nav-item.active {
        background-color: var(--ktmb-gold);
        color: var(--ktmb-navy);
        font-weight: 600;
    }

    .nav-icon {
        margin-right: 12px;
        font-size: 16px;
        display: inline-block;
        width: 20px;
        text-align: center;
        flex-shrink: 0;
    }

    .nav-text {
        transition: opacity 0.2s ease;
    }

    .sidebar.collapsed .nav-text {
        opacity: 0;
        pointer-events: none;
    }
    
    .sidebar.collapsed .nav-item {
        justify-content: center;
    }
    .sidebar.collapsed .nav-icon {
        margin-right: 0;
    }

    .logout-container {
        flex-shrink: 0;
        padding-top: 15px;
        background-color: var(--ktmb-navy); 
    }

    .logout-btn {
        background-color: rgba(234, 67, 53, 0.15);
        border: none;
        padding: 14px;
        border-radius: 8px;
        font-weight: 600;
        color: #fca5a5;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        transition: all 0.2s;
        white-space: nowrap;
        text-decoration: none;
        box-sizing: border-box;
    }
    .logout-btn:hover {
        background-color: rgba(234, 67, 53, 0.3);
        color: #ffffff;
    }
    
    .sidebar.collapsed .logout-btn span {
        display: none;
    }
</style>

<div class="sidebar" id="mainSidebar">
    <div class="nav-container">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <h2>eDOIS Portal</h2>
            </div>
            <button class="toggle-menu-btn" onclick="toggleSidebar()" title="Toggle Sidebar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <ul class="nav-list">
            <?php if ($userRole === 'Supplier'): ?>
                <div class="nav-section-title">Vendor Workspace</div>
                <li>
                    <a href="/KTMEDOIS/dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">General Dashboard</span>
                    </a>
                </li>
                <div class="nav-section-title">Delivery Orders (M02)</div>
                <li>
                    <a href="/KTMEDOIS/m2/create_do.php" class="nav-item <?php echo ($current_page == 'create_do.php' || $current_page == 'do_confirmation.php') ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">Submit Delivery Order</span>
                    </a>
                </li>
                <li>
                    <a href="/KTMEDOIS/m2/do_dashboard.php" class="nav-item <?php echo ($current_page == 'do_dashboard.php' || $current_page == 'do_rejected.php') ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">DO Tracking Matrix</span>
                    </a>
                </li>
                <div class="nav-section-title">Invoices & Claims (M03)</div>
                <li>
                    <a href="/KTMEDOIS/m3/create_inv.php" class="nav-item <?php echo ($current_page == 'create_inv.php') ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">Generate Claim Invoice</span>
                    </a>
                </li>
                <li>
                    <a href="/KTMEDOIS/m3/inv_list.php" class="nav-item <?php echo in_array($current_page, ['inv_list.php', 'inv_details.php']) ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">Invoice History Tracking</span>
                    </a>
                </li>

            <?php elseif ($userRole === 'Procurement Officer' || $userRole === 'Staff'): ?>
                <div class="nav-section-title">KTM Officer Workspace</div>
                <li>
                    <a href="/KTMEDOIS/m1/admin_vendor_list.php" class="nav-item <?php echo ($current_page == 'admin_vendor_list.php') ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">Supplier Directory Master</span>
                    </a>
                </li>
                <div class="nav-section-title">DO Verification (M02)</div>
                <li>
                    <a href="/KTMEDOIS/m4/do_list.php" class="nav-item <?php echo in_array($current_page, ['do_list.php', 'do_details.php']) ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">DO Inspections List — Review DO</span>
                    </a>
                </li>
                <div class="nav-section-title">Invoice Review & Approval (M04)</div>
                <li>
                    <a href="/KTMEDOIS/m4/review_dashboard.php" class="nav-item <?php echo in_array($current_page, ['review_dashboard.php', 'review_workspace.php']) ? 'active' : ''; ?>">
                       <span class="nav-icon">🏠</span>
                       <span class="nav-text">Dashboard &amp; Review Invoices</span>
                    </a>
                </li>
                <li>
                    <a href="/KTMEDOIS/m4/assign_reviewer.php" class="nav-item <?php echo ($current_page == 'assign_reviewer.php') ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">Assign Reviewer</span>
                    </a>
                </li>
                <li>
                    <a href="/KTMEDOIS/m4/review_history.php" class="nav-item <?php echo ($current_page == 'review_history.php') ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">Review History</span>
                    </a>
                </li>
                <li>
                    <a href="/KTMEDOIS/m4/notifications.php" class="nav-item <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                       <span class="nav-icon">🔔</span>
                       <span class="nav-text">Notifications</span>
                    </a>
                </li>

            <?php elseif (strpos(strtolower($userRole), 'finance') !== false): ?>
                <div class="nav-section-title">Finance Core Division</div>
                <li>
                    <a href="/KTMEDOIS/m4/review_dashboard.php" class="nav-item <?php echo ($current_page == 'review_dashboard.php') ? 'active' : ''; ?>">
                       <span class="nav-icon">🏠</span>
                       <span class="nav-text">Finance Dashboard</span>
                    </a>
                </li>
                <div class="nav-section-title">Claims Management (M04)</div>
                <li>
                    <a href="/KTMEDOIS/m4/review_workspace.php" class="nav-item <?php echo ($current_page == 'review_workspace.php') ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">Invoice Clearing Hub — Review Invoice</span>
                    </a>
                </li>
                <li>
                    <a href="/KTMEDOIS/m4/review_dashboard.php?view=payments" class="nav-item <?php echo ($current_page == 'review_dashboard.php' && ($_GET['view'] ?? '') === 'payments') ? 'active' : ''; ?>">
                       <span class="nav-icon">💲</span>
                       <span class="nav-text">Process Payments</span>
                    </a>
                </li>
                <li>
                    <a href="/KTMEDOIS/m4/review_history.php" class="nav-item <?php echo ($current_page == 'review_history.php') ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">Disbursement Records</span>
                    </a>
                </li>
                <li>
                    <a href="/KTMEDOIS/m4/notifications.php" class="nav-item <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                       <span class="nav-icon">🔔</span>
                       <span class="nav-text">Notifications</span>
                    </a>
                </li>

            <?php elseif ($userRole === 'Administrator' || $userRole === 'Manager'): ?>
                <div class="nav-section-title">System Controller</div>
                <li>
                    <a href="/KTMEDOIS/m4/review_dashboard.php" class="nav-item <?php echo ($current_page == 'review_dashboard.php') ? 'active' : ''; ?>">
                       <span class="nav-icon">🏠</span>
                       <span class="nav-text">Operations Center Overview</span>
                    </a>
                </li>
                <div class="nav-section-title">Review Workflow Control (M04)</div>
                <li>
                    <a href="/KTMEDOIS/m4/assign_reviewer.php" class="nav-item <?php echo ($current_page == 'assign_reviewer.php') ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">Assign Reviewer</span>
                    </a>
                </li>
                <li>
                    <a href="/KTMEDOIS/m1/admin_vendor_list.php" class="nav-item <?php echo ($current_page == 'admin_vendor_list.php') ? 'active' : ''; ?>">
                       <span class="nav-icon">👤</span>
                       <span class="nav-text">User Account Management</span>
                    </a>
                </li>
                <div class="nav-section-title">Audits & Insights</div>
                <li>
                    <a href="/KTMEDOIS/m4/audit_log.php" class="nav-item <?php echo ($current_page == 'audit_log.php') ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">Core System Audit Logs</span>
                    </a>
                </li>
                <li>
                    <a href="/KTMEDOIS/m4/generate_report.php" class="nav-item <?php echo ($current_page == 'generate_report.php') ? 'active' : ''; ?>">
                       <span class="nav-icon"></span>
                       <span class="nav-text">Management Report</span>
                    </a>
                </li>
                <li>
                    <a href="/KTMEDOIS/m4/notifications.php" class="nav-item <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                       <span class="nav-icon">🔔</span>
                       <span class="nav-text">System Alerts Broadcast</span>
                    </a>
                </li>
            
            <?php else: ?>
                <li>
                    <a href="/KTMEDOIS/m1/login.php" class="nav-item active">
                       <span class="nav-icon"></span>
                       <span class="nav-text">Sign In Required</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="logout-container">
        <a href="<?php echo app_url('m1/logout.php'); ?>" class="logout-btn">
            <span class="nav-icon">🚪</span>
            <span>Logout</span>
        </a>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('mainSidebar');
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarStatus', sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded');
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (localStorage.getItem('sidebarStatus') === 'collapsed') {
            document.getElementById('mainSidebar').classList.add('collapsed');
        }
    });
</script>
