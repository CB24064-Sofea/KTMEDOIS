/* ==========================================================================
   KTM eDOIS Global Layout Frame Engine
   ========================================================================== */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Master full-screen container */
.app-layout-wrapper {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100vh;
    overflow: hidden;
}

/* 🔒 Flex split layout container */
.main-content-wrapper {
    display: flex;
    width: 100%;
    height: calc(100vh - 60px); /* Subtracts topbar height correctly */
    overflow: hidden;
}

/* 🔄 INDEPENDENT SIDEBAR COMPONENT */
.sidebar {
    background: #ffffff;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    flex-shrink: 0;
}

/* 🔄 WORKSPACE PANEL */
.page-content {
    flex-grow: 1;
    background-color: #f8fafc;
    padding: 32px;
    overflow-y: auto; /* Allows content view matrix to scroll independently */
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

/* Custom layout responsive breakdown rules */
@media (max-width: 768px) {
    .page-content {
        padding: 16px;
    }
}
.search-wrapper{
    position:relative;
    width:100%;
}

.live-search-result{

    position:absolute;
    top:45px;
    left:0;
    right:0;

    background:white;

    border:1px solid #ddd;

    border-radius:12px;

    box-shadow:0 8px 18px rgba(0,0,0,.12);

    display:none;

    z-index:9999;

    max-height:260px;

    overflow-y:auto;
}

.live-item{

    padding:12px 18px;

    cursor:pointer;

    transition:.2s;
}

.live-item:hover{

    background:#f1f5f9;
}
