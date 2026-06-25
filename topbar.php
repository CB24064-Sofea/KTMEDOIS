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
    .topbar-center { flex-grow: 1; max-width: 500px; display: flex; justify-content: center; }
    
    /* 🔍 CONTAINER ENGINE WRAPPER FOR DROPDOWN OVERLAYS */
    .search-wrapper {
        position: relative;
        width: 100%;
    }
    .search-navbar-form {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        background-color: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 30px;
        padding: 4px 6px 4px 16px;
        transition: all 0.2s ease;
    }
    .search-navbar-form:focus-within {
        background-color: #ffffff;
        border-color: #002D62;
        box-shadow: 0 0 0 3px rgba(0, 45, 98, 0.15);
    }
    .search-input-field {
        width: 100%;
        border: none;
        background: transparent;
        font-size: 14px;
        color: #1a202c;
        outline: none;
        padding: 6px 0;
    }
    .search-submit-btn {
        background-color: #002D62;
        color: #ffffff;
        border: none;
        padding: 7px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }
    
    /* 🎯 DYNAMIC DROPDOWN POSITIONING MATRIX */
    .live-search-result {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        margin-top: 8px;
        max-height: 280px;
        overflow-y: auto;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        z-index: 9999;
        display: none;
    }
    .live-item {
        padding: 10px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        text-align: left;
    }
    .live-item:last-child { border-bottom: none; }
    .live-item:hover { background-color: #f8fafc; }
    .live-item strong { font-size: 14px; color: #002D62; }
    .live-item small { font-size: 11px; color: #718096; text-transform: uppercase; font-weight: 600; }

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

    <div class="topbar-center">
        <form class="search-navbar-form" onsubmit="goSearch(); return false;">
            <div class="search-wrapper">
                <input type="text" id="global_search" class="search-input-field" placeholder="Search Delivery Orders or Invoices..." autocomplete="off">
                <div id="live-search-result" class="live-search-result"></div>
            </div>
            <button type="submit" class="search-submit-btn">Search</button>
        </form>
    </div>

    <div class="topbar-right">
        <div class="logo-container">
            <img src="/KTMEDOIS/ktmb_logo.jpg" alt="KTMB Logo" style="height: 45px; width: auto;">
        </div>
    </div>
</div>

<script>
const searchBox = document.getElementById("global_search");
const resultBox = document.getElementById("live-search-result");

searchBox.addEventListener("keyup", function() {
    let keyword = this.value.trim();
    if (keyword.length === 0) {
        resultBox.style.display = "none";
        return;
    }

    fetch("/KTMEDOIS/ajax_search.php?q=" + encodeURIComponent(keyword))
        .then(response => response.text())
        .then(data => {
            resultBox.innerHTML = data;
            resultBox.style.display = data.trim() !== "" ? "block" : "none";
        });
});

// Redirects the page immediately when an option is selected
function selectItem(destinationUrl) {
    window.location.href = destinationUrl;
}

function goSearch() {
    let value = searchBox.value.trim();
    if (value === "") return;
    
    if (value.toUpperCase().startsWith("INV")) {
        window.location.href = "/KTMEDOIS/m3/inv_list.php?search=" + encodeURIComponent(value);
    } else {
        window.location.href = "/KTMEDOIS/m4/do_list.php?search=" + encodeURIComponent(value);
    }
}

// Close dropdown if user clicks outside the form area
document.addEventListener("click", function(e) {
    if (!e.target.closest(".search-wrapper")) {
        resultBox.style.display = "none";
    }
});
</script>