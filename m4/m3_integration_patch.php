<?php
// =========================================================================
// FILE        : m3_integration_patch.php
// PURPOSE     : This file shows what to ADD to your friend's Module 3
//               inv_list.php to create the integration link to Module 4.
//
// INSTRUCTION : Open your friend's file:
//               m3/inv_list.php
//               Find the table row where invoices are listed, and ADD
//               the Review button shown below in the Action column.
// =========================================================================

// ── WHAT TO ADD in m3/inv_list.php ───────────────────────────────────────────
//
// In the table <tbody> where each invoice row is rendered, find the last <td>
// and add this button AFTER the existing view/edit buttons:
//
//   <a href="../m4/review_workspace.php?id=<?php echo $row['invoice_ID']; ?>"
//      style="display:inline-block;background:#002D62;color:#fff;padding:7px 14px;
//             border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;
//             margin-left:6px;">
//      Review (M4)
//   </a>
//
// This links Module 3 (Invoice Submission) directly to Module 4 (Review Workflow)
// using the shared invoice_ID primary key from the invoice table.
// =========================================================================
?>
