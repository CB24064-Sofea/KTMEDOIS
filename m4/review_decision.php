<?php
// =========================================================================
// FILE        : review_decision.php
// SDD CLASS   : SDD_CLS_405 — ReviewAndApprovalController.updateStatus()
//               SDD_CLS_406 — AuditController.writeLogEntry()
// DESCRIPTION : POST handler only — no HTML. Calls OOP controller then redirects.
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/ReviewAndApprovalController.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: review_dashboard.php"); exit;
}

$invoiceId  = intval($_POST['invoice_id']      ?? 0);
$actionType = trim($_POST['action']            ?? '');
$remarks    = trim($_POST['review_remarks']    ?? '');
$staffId    = isset($_SESSION['staff_id']) ? $_SESSION['staff_id'] : 'STF001';

// SDD_CLS_405: updateStatus() — validates, updates DB, logs audit, sends notification
$controller = new ReviewAndApprovalController($conn, $staffId);
$result     = $controller->updateStatus($invoiceId, $remarks, $actionType);

$conn->close();

if (!$result['success']) {
    header("Location: review_dashboard.php?error=" . urlencode($result['message']));
    exit;
}

header("Location: review_dashboard.php?decision=success&action=" . urlencode($actionType));
exit;
