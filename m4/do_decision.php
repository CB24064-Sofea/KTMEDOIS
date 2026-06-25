<?php
// =========================================================================
// MODULE 4 — DO Approve/Reject processor
// SDD_CLS_404 + SDD_CLS_405 (for DO workflow)
// =========================================================================
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: do_list.php"); exit; }

$do_id          = $conn->real_escape_string(trim($_POST['do_id']));
$action         = $conn->real_escape_string(trim($_POST['action']));
$review_remarks = $conn->real_escape_string(trim($_POST['review_remarks']));
$staff_id       = 'STF001'; // demo — replace with $_SESSION['staff_id'] in full system

if (!in_array($action, ['Approved','Rejected'])) { header("Location: do_list.php"); exit; }

// 1. Update PO_status in delivery_order
$stmt = $conn->prepare("UPDATE delivery_order SET PO_status=? WHERE DO_ID=?");
$stmt->bind_param("ss", $action, $do_id);
$stmt->execute();
$stmt->close();

// 2. writeLogEntry() — SDD_CLS_405
// Get an invoice linked to this DO to satisfy audit_log FK
$inv = $conn->query("SELECT invoice_ID FROM invoice WHERE DO_ID='$do_id' LIMIT 1")->fetch_assoc();
if ($inv) {
    $record_id  = 'TRX-' . date('YmdHis');
    $log_action = 'DO ' . $action;
    $s2 = $conn->prepare("INSERT INTO audit_log (staff_ID,invoice_ID,action,record_ID) VALUES (?,?,?,?)");
    $s2->bind_param("siss", $staff_id, $inv['invoice_ID'], $log_action, $record_id);
    $s2->execute();
    $s2->close();
}

// 3. triggerNotifications()
$do_res = $conn->query("SELECT customer_ID FROM delivery_order WHERE DO_ID='$do_id' LIMIT 1")->fetch_assoc();
if ($do_res) {
    $type    = ($action==='Approved') ? 'DO Approved' : 'DO Rejected';
    $content = ($action==='Approved')
        ? "Delivery Order $do_id has been approved and forwarded for invoice processing."
        : "Delivery Order $do_id has been rejected. Reason: $review_remarks";
    $s3 = $conn->prepare("INSERT INTO notification (customer_ID,type,content) VALUES (?,?,?)");
    $s3->bind_param("sss", $do_res['customer_ID'], $type, $content);
    $s3->execute();
    $s3->close();
}

$conn->close();
header("Location: review_dashboard.php?do_decision=success&action=" . urlencode($action));
exit;
