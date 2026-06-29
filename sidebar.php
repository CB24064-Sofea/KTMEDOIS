<?php
// Get the current filename to track active item styling
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    :root {
        --sidebar-width: 270px;
        --sidebar-collapsed-width: 72px;
    }

    .sidebar {
        width: var(--sidebar-width);
        background-color: #fdfdfd;
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 24px 16px;
        height: 100%; /* 🔑 FIX: Changed from 100vh to 100% to stay within the layout frame */
        box-sizing: border-box;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-x: hidden;
        position: relative;
        flex-shrink: 0;
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
        color: #002D62;
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

    /* Menu Toggle Button Style */
    .toggle-menu-btn {
        background: none;
        border: none;
        color: #4a5568;
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s;
    }

    .toggle-menu-btn:hover {
        background-color: #f1f5f9;
    }

    .nav-container {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        overflow-y: auto; /* 🔄 Allows the long list of links to scroll independently */
        overflow-x: hidden;
        padding-right: 4px;
    }

    /* Custom Scrollbar for navigation container */
    .nav-container::-webkit-scrollbar {
        width: 4px;
    }
    .nav-container::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }

    .nav-section-title {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: #a0aec0;
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
        color: #616161;
        font-size: 13.5px;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s ease;
        white-space: nowrap;
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

    .nav-item.disabled {
        color: #cbd5e1;
        cursor: not-allowed;
        font-style: italic;
    }
    .nav-item.disabled:hover {
        background-color: transparent;
        color: #cbd5e1;
    }

    .logout-container {
        flex-shrink: 0;
        padding-top: 15px;
        background-color: #fdfdfd; /* Keeps button background clean */
    }

    .logout-btn {
        background-color: #eaedf2;
        border: none;
        padding: 14px;
        border-radius: 8px;
        font-weight: 600;
        color: #4a5568;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .logout-btn:hover {
        background-color: #e2e8f0;
    }
    
    .sidebar.collapsed .logout-btn span {
        display: none;
    }
</style>

<div class="sidebar" id="mainSidebar">
    <div class="nav-container">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <h2>KTM Portal</h2>
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
            <li>
                <a href="/KTMEDOIS/dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">General Dashboard</span>
                </a>
            </li>

            <div class="nav-section-title">Vendor Master (M01)</div>
            <li>
                <a href="#" class="nav-item disabled" onclick="return false;">
                   <span class="nav-icon"></span>
                   <span class="nav-text">Supplier Registry Sync...</span>
                </a>
            </li>

            <div class="nav-section-title">Delivery Orders</div>
            <li>
                <a href="/KTMEDOIS/m2/create_do.php" class="nav-item <?php echo ($current_page == 'create_do.php' || $current_page == 'do_confirmation.php') ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">Submit Delivery Order</span>
                </a>
            </li>
            <li>
                <a href="/KTMEDOIS/m2/do_dashboard.php"" class="nav-item <?php echo ($current_page == 'do_dashboard.php' || $current_page == 'do_rejected.php') ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">DO Dashboard / Status</span>
                </a>
            </li>
            <li>
                <a href="/KTMEDOIS/m2/do_report.php" class="nav-item <?php echo ($current_page == 'do_report.php') ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">Generate DO Summary</span>
                </a>
            </li>

            <div class="nav-section-title">Invoices & Claims (M03)</div>
            <li>
                <a href="create_inv.php" class="nav-item <?php echo ($current_page == 'create_inv.php') ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">Submit Invoice</span>
                </a>
            </li>
            <li>
                <a href="inv_list.php" class="nav-item <?php echo in_array($current_page, ['inv_list.php', 'inv_details.php']) ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">Invoice Tracking List</span>
                </a>
            </li>
            <li>
                <a href="inv_preview.php" class="nav-item <?php echo ($current_page == 'inv_preview.php') ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">Preview Workspace</span>
                </a>
            </li>

            <div class="nav-section-title">Verification Logs (M04)</div>
            <li>
                <a href="/KTMEDOIS/m4/review_dashboard.php" class="nav-item <?php echo ($current_page == 'review_dashboard.php') ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">Review Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/KTMEDOIS/m4/do_list.php" class="nav-item <?php echo in_array($current_page, ['do_list.php', 'do_details.php']) ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">DO Evaluation</span>
                </a>
            </li>
            <li>
                <a href="/KTMEDOIS/m4/review_workspace.php" class="nav-item <?php echo ($current_page == 'review_workspace.php') ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">Invoice Clearance</span>
                </a>
            </li>
            <li>
                <a href="/KTMEDOIS/m4/audit_log.php" class="nav-item <?php echo ($current_page == 'audit_log.php') ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">System Audit Log</span>
                </a>
            </li>
            <li>
                <a href="/KTMEDOIS/m4/generate_report.php" class="nav-item <?php echo ($current_page == 'generate_report.php') ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">Management Report</span>
                </a>
            </li>
            <li>
                <a href="/KTMEDOIS/m4/assign_reviewer.php" class="nav-item <?php echo ($current_page == 'assign_reviewer.php') ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">Assign Reviewer</span>
                </a>
            </li>
            <li>
                <a href="/KTMEDOIS/m4/notifications.php" class="nav-item <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">Notifications</span>
                </a>
            </li>
            <li>
                <a href="/KTMEDOIS/m4/review_history.php" class="nav-item <?php echo ($current_page == 'review_history.php') ? 'active' : ''; ?>">
                   <span class="nav-icon"></span>
                   <span class="nav-text">Review History</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="logout-container">
        <button class="logout-btn" onclick="window.location.href='/KTMEDOIS/logout.php'">
            <span class="nav-icon">🚪</span>
            <span>Logout</span>
        </button>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('mainSidebar');
        sidebar.classList.toggle('collapsed');
        
        if (sidebar.classList.contains('collapsed')) {
            localStorage.setItem('sidebarStatus', 'collapsed');
        } else {
            localStorage.setItem('sidebarStatus', 'expanded');
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const savedStatus = localStorage.getItem('sidebarStatus');
        const sidebar = document.getElementById('mainSidebar');
        if (savedStatus === 'collapsed') {
            sidebar.classList.add('collapsed');
        }
    });
</script>
