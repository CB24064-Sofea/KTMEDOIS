<?php
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
    .nav-item:hover { background-color: #f1f5f9; color: #1a1a1a; }
    .nav-item.active { background-color: #1e1e1e; color: #ffffff; font-weight: 600; }
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
        transition: background-color 0.2s;
    }
    .logout-btn:hover { background-color: #e2e8f0; }
</style>

<div class="sidebar">
    <div>
        <div class="sidebar-brand"><h2>KTM Portal</h2></div>
        <ul class="nav-list">
            <li>
                <a href="review_dashboard.php" class="nav-item <?php echo ($current_page=='review_dashboard.php')?'active':''; ?>">
                    Review Dashboard
                </a>
            </li>
            <li>
                <a href="review_workspace.php" class="nav-item <?php echo ($current_page=='review_workspace.php'||$current_page=='review_decision.php')?'active':''; ?>">
                    Invoice Review
                </a>
            </li>
            <li>
                <a href="audit_log.php" class="nav-item <?php echo ($current_page=='audit_log.php')?'active':''; ?>">
                    Audit Log
                </a>
            </li>
        </ul>
    </div>
    <div>
        <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
    </div>
</div>
