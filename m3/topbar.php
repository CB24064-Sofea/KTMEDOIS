<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
?>
<style>
    .topbar {
        height: 70px;
        background-color: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between; 
        padding: 0 32px;
        box-sizing: border-box;
        width: 100%;
        gap: 20px;
    }
    .topbar-left { display: flex; align-items: center; flex-shrink: 0; }
    
    .auth-guest-links { display: flex; gap: 12px; }
    .btn-auth { text-decoration: none; font-size: 14px; font-weight: 600; padding: 8px 16px; border-radius: 6px; }
    .btn-login { color: #002D62; border: 1px solid #002D62; }
    .btn-register { background-color: #002D62; color: #ffffff; }
    
    .user-profile-badge { display: flex; align-items: center; gap: 12px; padding: 6px 12px; border-radius: 30px; cursor: pointer; }
    .user-avatar-frame { width: 38px; height: 38px; border-radius: 50%; overflow: hidden; border: 2px solid #e2e8f0; background-color: #edf2f7; }
    .user-avatar-img { width: 100%; height: 100%; object-fit: cover; }
    .user-meta-info { display: flex; flex-direction: column; text-align: left; }
    .user-display-name { font-size: 14px; font-weight: 600; color: #1a202c; }
    .user-assigned-role { font-size: 11px; color: #718096; }
    
    .topbar-right { display: flex; align-items: center; height: 100%; flex-shrink: 0; }
    .logo-container { display: flex; align-items: center; height: 100%; max-height: 46px; }
</style>

<div class="topbar">
    <div class="topbar-left">
        <?php if ($is_logged_in): ?>
            <div class="user-profile-badge" onclick="window.location.href='/KTMEDOIS/profile.php'">
                <div class="user-avatar-frame">
                    <img src="<?php echo !empty($_SESSION['user_avatar']) ? htmlspecialchars($_SESSION['user_avatar']) : '/KTMEDOIS/default_avatar.png'; ?>" alt="User" class="user-avatar-img">
                </div>
                <div class="user-meta-info">
                    <span class="user-display-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <span class="user-assigned-role"><?php echo htmlspecialchars($_SESSION['user_role']); ?></span>
                </div>
            </div>
        <?php else: ?>
            <div class="auth-guest-links">
                <a href="/KTMEDOIS/login.php" class="btn-auth btn-login">Login</a>
                <a href="/KTMEDOIS/register.php" class="btn-auth btn-register">Register</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="topbar-right">
        <div class="logo-container">
            <img src="ktmb_logo.jpg" alt="KTMB Logo" style="height: 45px; width: auto;">
        </div>
    </div>
</div>