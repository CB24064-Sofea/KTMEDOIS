<?php
require_once __DIR__ . "/db.php";

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === "") {
    exit();
}

// 1. HARDCODED SYSTEM NAVIGATION SYSTEM PATHS
// This lets users search for features/actions directly from the topbar
$navigation_pages = [
    [
        'item_id' => 'Submit Delivery Order',
        'record_type' => 'System Action (M2)',
        'target_url' => '/KTMEDOIS/m2/do_submission.php'
    ],
    [
        'item_id' => 'Delivery Order Dashboard',
        'record_type' => 'Workspace Navigation (M2)',
        'target_url' => '/KTMEDOIS/m2/do_dashboard.php'
    ],
    [
        'item_id' => 'Delivery Order Summary Reports',
        'record_type' => 'System Action (M2)',
        'target_url' => '/KTMEDOIS/m2/do_report.php'
    ],
    [
        'item_id' => 'Create Invoice Workspace',
        'record_type' => 'System Action (M3)',
        'target_url' => '/KTMEDOIS/m3/invoiceCreationUI.php' // Adjust file names if different
    ],
    [
        'item_id' => 'Invoice Registry List',
        'record_type' => 'Workspace Navigation (M3)',
        'target_url' => '/KTMEDOIS/m3/inv_list.php'
    ],
    [
        'item_id' => 'Delivery Order Master List',
        'record_type' => 'Workspace Navigation (M4)',
        'target_url' => '/KTMEDOIS/m4/do_list.php'
    ]
];

$results_found = 0;

// 2. CHECK MATCHES AGAINST NAVIGATION PAGES FIRST
foreach ($navigation_pages as $page) {
    // Perform case-insensitive search matching string
    if (stripos($page['item_id'], $q) !== false || stripos($page['record_type'], $q) !== false) {
        $safeId = htmlspecialchars($page['item_id']);
        $safeType = htmlspecialchars($page['record_type']);
        $safeUrl = htmlspecialchars($page['target_url']);
        
        echo "
        <div class='live-item' onclick=\"selectItem('$safeUrl')\">
            <strong>⚙️ {$safeId}</strong><br>
            <small style='color: #3182ce;'>{$safeType}</small>
        </div>
        ";
        $results_found++;
    }
}

// 3. SECURE DATABASE LOOKUP (DOs and Invoices)
$stmt = $conn->prepare("
    SELECT DO_ID AS item_id, 'Delivery Order Record' AS record_type, CONCAT('/KTMEDOIS/m4/do_list.php?search=', DO_ID) AS target_url
    FROM delivery_order
    WHERE DO_ID LIKE CONCAT('%', ?, '%')
    UNION
    SELECT invoice_num AS item_id, 'Invoice Record' AS record_type, CONCAT('/KTMEDOIS/m3/inv_list.php?search=', invoice_num) AS target_url
    FROM invoice
    WHERE invoice_num LIKE CONCAT('%', ?, '%')
    LIMIT 5
");

$stmt->bind_param("ss", $q, $q);
$stmt->execute();
$db_result = $stmt->get_result();

while ($row = $db_result->fetch_assoc()) {
    $safeId = htmlspecialchars($row['item_id']);
    $safeType = htmlspecialchars($row['record_type']);
    $safeUrl = htmlspecialchars($row['target_url']);
    
    echo "
    <div class='live-item' onclick=\"selectItem('$safeUrl')\">
        <strong>📄 {$safeId}</strong><br>
        <small style='color: #718096;'>{$safeType}</small>
    </div>
    ";
    $results_found++;
}

// 4. FALLBACK ERROR MESSAGES
if ($results_found === 0) {
    echo "<div class='live-item' style='color:#a0aec0; cursor:default; font-style:italic;'>No matches or system actions found.</div>";
}
?>