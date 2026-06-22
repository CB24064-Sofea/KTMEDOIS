<?php
// Get the current filename
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    .sidebar {
        width: 260px;
        background-color: #fdfdfd;
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 40px 24px;
        height: 100vh;
    }

    /* FIXED: Matched style to HTML layout selector */
    .sidebar-brand h2 {
        font-size: 20px;
        font-weight: 700;
        color: #002D62;
        letter-spacing: 0.5px;
        margin-bottom: 40px;
    }

    /* FIXED: Changed selector name from .nav-menu to match HTML .nav-list */
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
        margin-top: auto; /* Ensures it stays at the absolute bottom */
        transition: background-color 0.2s;
    }
    .logout-btn:hover {
        background-color: #e2e8f0;
    }
</style>

<div class="sidebar">
    <div>
        <div class="sidebar-brand">
            <h2>KTM Portal</h2>
        </div>
        
        <ul class="nav-list">
            <li>
                <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                   Dashboard
                </a>
            </li>
            
            <li>
                <a href="create_inv.php" class="nav-item <?php echo ($current_page == 'create_inv.php') ? 'active' : ''; ?>">
                   Submit Invoice
                </a>
            </li>

            <li>
                <a href="inv_list.php" class="nav-item <?php echo ($current_page == 'inv_list.php' || $current_page == 'inv_details.php') ? 'active' : ''; ?>">
                   Invoice List
                </a>
            </li>

            <li>
                <a href="inv_preview.php" class="nav-item <?php echo ($current_page == 'inv_preview.php') ? 'active' : ''; ?>">
                   Preview
                </a>
            </li>
        </ul>
    </div>

    <div>
        <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
    </div>
</div>