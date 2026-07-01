<?php
// =========================================================================
// FILE        : review_decision.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : SDD_CLS_405 — ReviewAndApprovalController.updateStatus()
//               SDD_CLS_406 — AuditController.writeLogEntry()
// DESCRIPTION : POST handler only — no HTML output.
//               Calls OOP controller to:
//               1. Validate the action type
//               2. Update invoice_status in DB
//               3. Write audit log entry (AuditController.writeLogEntry)
//               4. Send vendor notification (triggerNotifications)
//               Then redirects back to review_dashboard.php
// =========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/ReviewAndApprovalController.php";

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: review_dashboard.php"); exit;
}

// ── Session guard ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['staff_auth'])) {
    header("Location: " . app_url('m1/staff_login.php')); exit;
}

$invoiceId  = intval($_POST['invoice_id']   ?? 0);
$actionType = trim($_POST['action']         ?? '');
$remarks    = trim($_POST['review_remarks'] ?? '');
$staffId    = $_SESSION['staff_auth']['staff_id'] ?? $_SESSION['staff_auth']['staff_ID'] ?? 'STF001';

// ── Determine where to redirect back to ───────────────────────────────────────
// Several M4 boundary pages (review_workspace, forward_to_finance, approve_payment)
// all POST to this single handler. Each supplies a hidden "redirect_to" field so
// the user lands back on the page they started from instead of always bouncing
// to review_dashboard.php. Only a whitelisted set of local M4 filenames is ever
// honoured — this prevents the value from being abused as an open redirect.
$allowedRedirects = ['review_dashboard.php', 'forward_to_finance.php', 'approve_payment.php'];
$redirectTo = basename(trim($_POST['redirect_to'] ?? ''));
if (!in_array($redirectTo, $allowedRedirects, true)) {
    $redirectTo = 'review_dashboard.php';
}

// Basic server-side validation
if ($invoiceId <= 0 || empty($actionType)) {
    header("Location: {$redirectTo}?error=" . urlencode("Invalid submission. Please try again."));
    exit;
}

// SDD_CLS_405: updateStatus() — validates, updates DB, logs audit, sends notification
$controller = new ReviewAndApprovalController($conn, $staffId);

// PaymentProcessed is handled separately from updateStatus() because it acts
// on invoices that are already in the final 'Approved' state (payment stage
// happens after the review workflow itself has concluded).
if ($actionType === 'PaymentProcessed') {
    $result = $controller->processPayment($invoiceId);
} else {
    $result = $controller->updateStatus($invoiceId, $remarks, $actionType);
}

$conn->close();

if (!$result['success']) {
    header("Location: {$redirectTo}?error=" . urlencode($result['message']));
    exit;
}

header("Location: {$redirectTo}?decision=success&action=" . urlencode($actionType));
exit;
