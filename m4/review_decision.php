<?php
// =========================================================================
// FILE        : review_decision.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// SDD CLASS   : SDD_CLS_404 — ReviewAndApprovalController.processWorkflowUpdate()
//               SDD_CLS_405 — AuditController.writeLogEntry()
// DESCRIPTION : Handles the POST form submission from review_workspace.php.
//               This file performs 4 main operations:
//               1. Validates the incoming action (Verified or Rejected)
//               2. updateStatus()    — updates invoice_status in database
//               3. writeLogEntry()   — inserts a record into audit_log
//               4. triggerNotifications() — inserts notification for vendor
//
// AUTHOR      : Module 4 Developer
// DATE        : June 2026
// =========================================================================

include 'db.php'; // Load shared database connection

// ── Security check — only accept POST requests ────────────────────────────────
// Direct URL access to this file is blocked; must come from review_workspace form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: review_dashboard.php");
    exit;
}

// ── Retrieve and sanitise POST data ──────────────────────────────────────────
// real_escape_string() prevents SQL injection on string inputs
$invoice_id     = intval($_POST['invoice_id']);           // Cast to int for safety
$action         = $conn->real_escape_string(trim($_POST['action']));
$review_remarks = $conn->real_escape_string(trim($_POST['review_remarks']));

// Hardcoded staff ID — in full system this comes from $_SESSION['staff_id']
// after login authentication
$staff_id = 'STF001';

// ── Step 1: Validate action value ─────────────────────────────────────────────
// Only 'Verified' or 'Rejected' are valid actions — anything else is rejected
if (!in_array($action, ['Verified', 'Rejected', 'UnderReview'])) {
    header("Location: review_dashboard.php?error=invalid_action");
    exit;
}

// ── Step 2: Validate invoice exists and is in a reviewable state ─────────────
// Prevent re-reviewing an invoice that is already Finance Review, Paid, etc.
$check = $conn->prepare("SELECT invoice_status FROM invoice WHERE invoice_ID = ? LIMIT 1");
$check->bind_param("i", $invoice_id);
$check->execute();
$check_result = $check->get_result()->fetch_assoc();
$check->close();

if (!$check_result) {
    // Invoice not found in database
    header("Location: review_dashboard.php?error=not_found");
    exit;
}

if (!in_array($check_result['invoice_status'], ['Submitted', 'Under Review'])) {
    // Invoice already processed — redirect back without changes
    header("Location: review_workspace.php?id=$invoice_id&error=already_reviewed");
    exit;
}

// ── Step 3: updateStatus() — SDD_CLS_404 ─────────────────────────────────────
// If Verified   → set invoice_status to 'Finance Review' (routes to finance team)
// If Rejected   → set invoice_status to 'Rejected' and save the rejection reason
if ($action === 'Verified') {
    // Approved: forward invoice to Finance Review queue
    $stmt = $conn->prepare(
        "UPDATE invoice SET invoice_status = 'Finance Review' WHERE invoice_ID = ?"
    );
    $stmt->bind_param("i", $invoice_id);
} elseif ($action === 'UnderReview') {
    $stmt = $conn->prepare(
        "UPDATE invoice SET invoice_status = 'Under Review' WHERE invoice_ID = ?"
    );
    $stmt->bind_param("i", $invoice_id);
} else {
    // Rejected: save status as Rejected + store the officer's reason
    $stmt = $conn->prepare(
        "UPDATE invoice SET invoice_status = 'Rejected', reason = ? WHERE invoice_ID = ?"
    );
    $stmt->bind_param("si", $review_remarks, $invoice_id);
}

// Execute the UPDATE and check for errors
if (!$stmt->execute()) {
    // Database update failed — redirect with error flag
    $stmt->close();
    header("Location: review_dashboard.php?error=update_failed");
    exit;
}
$stmt->close();

// ── Step 4: writeLogEntry() — SDD_CLS_405 AuditController ────────────────────
// Every officer action MUST be recorded in audit_log for compliance trail.
// record_ID format: TRX- + timestamp (e.g. TRX-20260617143022)
$record_id = 'TRX-' . date('YmdHis'); // Unique transaction ID using current datetime

$stmt2 = $conn->prepare(
    "INSERT INTO audit_log (staff_ID, invoice_ID, action, record_ID)
     VALUES (?, ?, ?, ?)"
);
$stmt2->bind_param("siss", $staff_id, $invoice_id, $action, $record_id);

if (!$stmt2->execute()) {
    // Log the error but don't stop execution — audit failure shouldn't block workflow
    error_log("Audit log insert failed for invoice_ID: $invoice_id");
}
$stmt2->close();

// ── Step 5: triggerNotifications() — send notification to vendor ──────────────
// Retrieve invoice number and DO reference needed for the notification message
$inv_stmt = $conn->prepare(
    "SELECT i.invoice_num, i.DO_ID, d.customer_ID
     FROM invoice i
     INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
     WHERE i.invoice_ID = ?
     LIMIT 1"
);
$inv_stmt->bind_param("i", $invoice_id);
$inv_stmt->execute();
$inv_data = $inv_stmt->get_result()->fetch_assoc();
$inv_stmt->close();

// Only insert notification if we successfully retrieved invoice + customer data
if ($inv_data) {
    // Build notification type and message based on officer's decision
    if ($action === 'Verified') {
    $noti_type    = 'Invoice Approved';
    $noti_content = "Invoice {$inv_data['invoice_num']} has been approved by the procurement officer and forwarded to Finance Review.";
    } elseif ($action === 'UnderReview') {
    $noti_type    = 'Additional Info Requested';
    $noti_content = "Invoice {$inv_data['invoice_num']} requires additional information. Remarks: {$review_remarks}. Please resubmit with the requested details.";
    } else {
    $noti_type    = 'Invoice Rejected';
    $noti_content = "Invoice {$inv_data['invoice_num']} has been rejected. Reason: {$review_remarks}. Please resubmit with corrections.";
    }

    // Insert notification into notification table — linked to customer_ID
    $noti_stmt = $conn->prepare(
        "INSERT INTO notification (customer_ID, type, content)
         VALUES (?, ?, ?)"
    );
    $noti_stmt->bind_param("sss", $inv_data['customer_ID'], $noti_type, $noti_content);
    $noti_stmt->execute(); // Non-critical — failure here does not block workflow
    $noti_stmt->close();
}

// ── Step 6: Redirect back to dashboard with success flag ─────────────────────
// The dashboard reads $_GET['decision'] and $_GET['action'] to show feedback alert
$conn->close(); // Always close DB connection
header("Location: review_dashboard.php?decision=success&action=" . urlencode($action));
exit;
