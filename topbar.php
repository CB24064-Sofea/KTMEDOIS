<?php
// Start session only if it hasn't been started yet by a parent script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

// 1. DYNAMIC SEARCH ENGINE OPTIONS LOADER
$search_suggestions = [];

// Calculate project root dynamically to connect to the database securely
$project_root = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..')), '/');
if (file_exists($project_root . '/db.php')) {
    include_once $project_root . '/db.php';
    
    // Check if the database connection variable ($conn) is alive and healthy
    if (isset($conn) && $conn) {
        // Gather DO numbers for suggestions
        $res_do = $conn->query("SELECT DO_ID FROM delivery_order LIMIT 5");
        if ($res_do) {
            while ($row = $res_do->fetch_assoc()) {
                $search_suggestions[] = $row['DO_ID'];
            }
        }
        
        // Gather Invoice numbers for suggestions
        $res_inv = $conn->query("SELECT Invoice_ID FROM invoice LIMIT 5"); // Change column name if your schema differs
        if ($res_inv) {
            while ($row = $res_inv->fetch_assoc()) {
                $search_suggestions[] = $row['Invoice_ID'];
            }
        }
    }
}

// Fallback default suggestions if your database tables are completely empty right now
if (empty($search_suggestions)) {
    $search_suggestions = ['DO001', 'DO002', 'INV1001', 'INV1002', 'PO8899'];
}

// 2. SEARCH REDIRECTION ROUTER
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['global_search'])) {
    $search_query = trim($_GET['global_search']);
    if (!empty($search_query)) {
        if (stripos($search_query, 'INV') !== false) {
            header("Location: /KTMEDOIS/m3/inv_list.php?search=" . urlencode($search_query));
            exit();
        } else {
            header("Location: /KTMEDOIS/m4/do_list.php?search=" . urlencode($search_query));
            exit();
        }
    }
}
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

    .topbar-left {
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }

    /* 🔍 CENTER SEARCH BAR NAVIGATION BAR STYLING */
    .topbar-center {
        flex-grow: 1;
        max-width: 500px;
        display: flex;
        justify-content: center;
    }

    .search-navbar-form {
        width: 100%;
        display: flex;
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

    .search-input-field::placeholder {
        color: #94a3b8;
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
        transition: background-color 0.2s;
    }

    .search-submit-btn:hover {
        background-color: #001937;
    }

    /* GUEST LINKS */
    .auth-guest-links {
        display: flex;
        gap: 12px;
    }

    .btn-auth {
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .btn-login { color: #002D62; border: 1px solid #002D62; }
    .btn-login:hover { background-color: #f0f4f8; }
    .btn-register { background-color: #002D62; color: #ffffff; }
    .btn-register:hover { background-color: #001f44; }

    /* LOGGED IN USER PROFILE */
    .user-profile-badge {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 6px 12px;
        border-radius: 30px;
        transition: background-color 0.2s;
        cursor: pointer;
    }
    .user-profile-badge:hover { background-color: #f8fafc; }

    .user-avatar-frame {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        overflow: hidden;
        border: 2px solid #e2e8f0;
        background-color: #edf2f7;
    }

    .user-avatar-img { width: 100%; height: 100%; object-fit: cover; }
    .user-meta-info { display: flex; flex-direction: column; text-align: left; }
    .user-display-name { font-size: 14px; font-weight: 600; color: #1a202c; line-height: 1.2; }
    .user-assigned-role { font-size: 11px; font-weight: 500; color: #718096; }

    .topbar-right { display: flex; align-items: center; height: 100%; flex-shrink: 0; }
    .logo-container { display: flex; align-items: center; height: 100%; max-height: 46px; }

    @media (max-width: 650px) {
        .topbar { padding: 0 16px; }
        .search-navbar-form { padding: 4px 4px 4px 10px; }
        .search-submit-btn { padding: 6px 10px; font-size: 11px; }
        .user-meta-info { display: none; }
    }
</style>

<div class="topbar">
    <div class="topbar-left">
        <?php if ($is_logged_in): ?>
            <div class="user-profile-badge" onclick="window.location.href='/KTMEDOIS/profile.php'">
                <div class="user-avatar-frame">
                    <img src="<?php echo !empty($_SESSION['user_avatar']) ? htmlspecialchars($_SESSION['user_avatar']) : '/KTMEDOIS/default_avatar.png'; ?>" 
                         alt="User Profile" 
                         class="user-avatar-img">
                </div>
                <div class="user-meta-info">
                    <span class="user-display-name">
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <span class="user-assigned-role">
                        <?php echo htmlspecialchars($_SESSION['user_role']); ?>
                    </span>
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
       <form class="search-navbar-form" onsubmit="return false;">
           <div class="search-wrapper">

<input
    type="text"
    id="global_search"
    class="search-input-field"
    placeholder="Search DO, Invoice or PO..."
    autocomplete="off">

<div id="live-search-result" class="live-search-result"></div>

</div>

<button
    class="search-submit-btn"
    onclick="goSearch()">
Search
</button>
        </form>
    </div>

    <div class="topbar-right">
        <div class="logo-container">
            <img src="/KTMEDOIS/ktmb_logo.jpg" alt="KTMB Official Logo" style="height: 45px; width: auto;">
        </div>
    </div>
</div>
<script>

const searchBox=document.getElementById("global_search");

searchBox.addEventListener("keyup",function(){

    let keyword=this.value;

    if(keyword.length==0){

        document.getElementById("live-search-result").style.display="none";
        return;
    }

    fetch("/KTMEDOIS/ajax_search.php?q="+encodeURIComponent(keyword))

    .then(response=>response.text())

    .then(data=>{

        let box=document.getElementById("live-search-result");

        box.innerHTML=data;

        box.style.display="block";

    });

});


function fillSearch(value){

    document.getElementById("global_search").value=value;

    document.getElementById("live-search-result").style.display="none";

}


function goSearch(){

    let value=document.getElementById("global_search").value;

    if(value=="") return;

    if(value.toUpperCase().startsWith("INV")){

        window.location="/KTMEDOIS/m3/inv_list.php?search="+encodeURIComponent(value);

    }else{

        window.location="/KTMEDOIS/m4/do_list.php?search="+encodeURIComponent(value);

    }

}

document.addEventListener("click",function(e){

    if(!e.target.closest(".search-wrapper")){

        document.getElementById("live-search-result").style.display="none";

    }

});

</script>