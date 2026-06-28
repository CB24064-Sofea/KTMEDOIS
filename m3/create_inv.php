<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "127.0.0.1:3307";
$username = "root";
$password = "";
$dbname = "ktm_edois";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$next_id = 1; 
$status_result = $conn->query("SHOW TABLE STATUS LIKE 'invoice'");
if ($status_result && $row = $status_result->fetch_assoc()) {
    $next_id = $row['Auto_increment'] ? $row['Auto_increment'] : 1;
}

$current_year = date('Y');
$auto_invoice_id = "INV-KTM-" . $current_year . "-" . str_pad($next_id, 4, '0', STR_PAD_LEFT);

$show_success_popup = false; 
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_claim'])) {
    $do_id           = $_POST['do_id'];
    $billing_address = $_POST['billing_address'];
    $invoice_num     = $_POST['invoice_num'];
    $description     = !empty($_POST['description']) ? $_POST['description'] : null;
    $invoice_date    = $_POST['invoice_date'];
    
    $subtotal        = is_numeric($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0.00; 
    $tax             = $subtotal * 0.06;
    $credit_note     = is_numeric($_POST['credit_note']) ? floatval($_POST['credit_note']) : 0.00;
    $penalty         = is_numeric($_POST['penalty']) ? floatval($_POST['penalty']) : 0.00; 
    
    $invoice_status  = $_POST['invoice_status'];
    $payment_status  = $_POST['payment_status'];
    $reason          = !empty($_POST['reason']) ? $_POST['reason'] : null;

    $total = ($subtotal + $tax) - $credit_note - $penalty;

    $stmt = $conn->prepare("INSERT INTO invoice (DO_ID, billing_address, invoice_num, description, invoice_date, subtotal, tax, credit_note, penalty, total, invoice_status, payment_status, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sssssddddssss", $do_id, $billing_address, $invoice_num, $description, $invoice_date, $subtotal, $tax, $credit_note, $penalty, $total, $invoice_status, $payment_status, $reason);
    
    if ($stmt->execute()) {
        $show_success_popup = true; 
        
        $inserted_id = $conn->insert_id;
        $next_id = $inserted_id + 1;
        $auto_invoice_id = "INV-KTM-" . date('Y') . "-" . str_pad($next_id, 4, '0', STR_PAD_LEFT);
    } else {
        $message = "Error submitting invoice data: " . $stmt->error;
        $messageType = "error";
    }
    $stmt->close();
}

$delivery_orders = [];
$do_result = $conn->query("SELECT DO_ID FROM delivery_order");
if ($do_result && $do_result->num_rows > 0) {
    while($row = $do_result->fetch_assoc()) {
        $delivery_orders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Portal - Invoice Submission</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f3f5f9;
            --card-bg: #ffffff;
            --primary-navy: #002D62;
            --dark-gray: #1a1a1a;
            --text-muted: #757575;
            --border-color: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        /* FIXED LAYOUT STRUCTURE */
        body {
            background-color: var(--bg-color);
            display: flex;
            flex-direction: column; /* Stack topbar over the inner content container */
            height: 100vh;
            overflow: hidden;
            color: #333;
        }

        /* Container under the topbar to house sidebar and main view */
        .app-container {
            display: flex;
            flex-direction: row;
            flex-grow: 1;
            width: 100%;
            overflow: hidden; /* Prevent entire page double scrolling loops */
        }

        .workspace {
            flex-grow: 1;
            padding: 40px;
            overflow-y: auto; /* Allow independent scrolling for the workspace form only */
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .header-area { 
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px; 
        }

        .header-title { 
            font-size: 28px; 
            font-weight: 700; 
            color: var(--primary-navy); 
            line-height: 1.2; 
        }

        .logo-container {
            height: 50px;
            display: flex;
            align-items: center;
            margin-left: auto;
        }

        .logo-container img {
            height: 100%;
            width: auto;
            object-fit: contain;
        }

        .alert { 
            padding: 12px 16px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            font-size: 14px; 
            font-weight: 500; 
        }

        .alert-error { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }

        .submission-layout { 
            display: grid; 
            grid-template-columns: 1.6fr 1fr; 
            gap: 30px; 
            align-items: start; 
        }

        .form-left-stack { 
            display: flex; 
            flex-direction: column; 
            gap: 30px; 
        }

        .card { 
            background: var(--card-bg); 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.02); 
            border: 1px solid var(--border-color); 
            padding: 24px; 
        }

        .card-title-section { 
            font-size: 13px; 
            font-weight: 700; 
            color: #4a5568; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            margin-bottom: 20px; 
            display: block; 
        }

        .blue-header-card { 
            padding: 0; 
            overflow: hidden; 
        }

        .blue-header { 
            background-color: var(--primary-navy); 
            color: #ffffff; 
            padding: 16px 24px; 
            font-size: 13px; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
        }

        .blue-card-body { 
            padding: 24px; 
            display: flex; 
            flex-direction: column; 
            gap: 16px; 
        }

        .form-group { 
            display: grid; 
            grid-template-columns: 160px 1fr; 
            align-items: center; 
            gap: 16px; 
        }

        .form-group.align-start { 
            align-items: start; 
        }

        label { 
            font-size: 13px; 
            font-weight: 600; 
            color: #718096; 
        }

        input[type="text"], input[type="date"], select, textarea {
            width: 100%; padding: 10px 14px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; background-color: #fafafa; color: var(--dark-gray); transition: all 0.2s ease;
        }

        input[type="text"]:focus, input[type="date"]:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary-navy); background-color: #fff; }
        input:disabled { background-color: #edf2f7; color: #4a5568; cursor: not-allowed; font-weight: 600; }
        textarea { resize: vertical; min-height: 70px; }

        .right-panel { 
            display: flex; 
            flex-direction: column; 
            gap: 20px; 
        }

        .upload-dashed-box { 
            border: 2px dashed #cbd5e0; 
            border-radius: 12px; 
            padding: 40px 20px; 
            text-align: center; 
            background-color: #f8fafc; 
            cursor: pointer; 
            transition: border-color 0.2s ease; 
        }

        .upload-dashed-box:hover { 
            border-color: var(--primary-navy); 
        }

        .upload-text-label { 
            font-size: 12px; 
            font-weight: 700; 
            color: var(--primary-navy); 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            margin-bottom: 8px; 
        }

        .upload-title { 
            font-size: 14px; 
            font-weight: 600; 
            color: var(--dark-gray); 
            margin-bottom: 4px; 
        }

        .upload-meta { 
            font-size: 11px; 
            color: var(--text-muted); 
        }

        .select-file-pill { 
            display: inline-block; 
            margin-top: 16px; 
            border: 1px solid #cbd5e0; 
            padding: 8px 16px; 
            border-radius: 6px; 
            background: white; 
            font-size: 12px; 
            font-weight: 700; 
        }

        .info-disclaimer { 
            background-color: #ebf8ff; 
            border: 1px solid #bee3f8; 
            color: #2b6cb0; 
            border-radius: 8px; 
            padding: 14px; 
            font-size: 12px; 
            line-height: 1.5; 
        }

        .action-container { 
            display: flex; 
            flex-direction: column; 
            gap: 12px; 
            margin-top: 10px; 
        }

        .btn-submit { 
            background-color: #031B33; 
            color: #ffffff; 
            border: none; 
            padding: 16px; 
            border-radius: 8px; 
            font-weight: 700; 
            font-size: 14px; 
            cursor: pointer; 
            letter-spacing: 0.5px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px; 
            transition: opacity 0.2s ease; 
        }

        .btn-submit:hover { 
            opacity: 0.9; 
        }

        .btn-cancel { 
            background-color: #ffffff; 
            color: #4a5568; 
            border: 1px solid #cbd5e0; 
            padding: 14px; 
            border-radius: 8px; 
            font-weight: 600; 
            font-size: 14px; 
            cursor: pointer; 
            text-align: center; 
            text-decoration: none; 
            transition: background-color 0.2s ease; 
        }

        .btn-cancel:hover { 
            background-color: #f7fafc; 
        }

        .total-row { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 24px; 
            border-top: 1px solid var(--border-color); 
            background-color: #fafafa; 
            border-bottom-left-radius: 12px; 
            border-bottom-right-radius: 12px; 
        }

        .total-label { 
            font-size: 14px; 
            font-weight: 700; 
            color: #2d3748; 
        }

        .total-value { 
            font-size: 22px; 
            font-weight: 700; 
            color: var(--primary-navy); 
        }

        .system-footer { 
            text-align: center; 
            font-size: 11px; 
            color: #a0aec0; 
            margin-top: 40px; 
            letter-spacing: 1px; 
        }

       .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        
        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .success-modal-card {
            background-color: #EAEAEA;
            width: 440px;
            padding: 40px 30px;
            border-radius: 28px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            text-align: center;
            position: relative;
            transform: scale(0.8);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }

        .modal-overlay.active .success-modal-card {
            transform: scale(1);
        }

        .success-icon-container {
            background-color: #76BA00;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .success-icon-container svg {
            width: 32px;
            height: 32px;
            fill: #ffffff;
        }

        .modal-content-stack {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        .modal-title-text {
            font-size: 26px;
            font-weight: 700;
            color: #000000;
            margin: 0;
        }

        .btn-modal-ok {
            background-color: #D1CDCD;
            color: #000000;
            font-size: 18px;
            font-weight: 700;
            border: 1px solid #A6A2A2;
            padding: 8px 36px;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-modal-ok:hover {
            background-color: #C2BEBE;
        }
    </style>
</head>
<body>
    <?php include('topbar.php'); ?>
    
    <div class="app-container">
        
        <?php include('../sidebar.php'); ?>

        <div id="successModal" class="modal-overlay <?php echo $show_success_popup ? 'active' : ''; ?>">
            <div class="success-modal-card">
                <div class="success-icon-container">
                    <svg viewBox="0 0 24 24">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                </div>
                <div class="modal-content-stack">
                    <h2 class="modal-title-text">Submitted!</h2>
                    <a href="inv_list.php" class="btn-modal-ok" id="closeModalBtn" style="text-decoration: none; display: inline-block;">Ok</a>
                </div>
            </div>
        </div>

        <main class="workspace">
            
            <div class="header-area">
                <h1 class="header-title">Invoice Submission</h1>
                
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-error">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="submission-layout">
                    
                    <div class="form-left-stack">
                        
                        <div class="card">
                            <div class="card-title-section">Partner Information</div>
                            
                            <div style="display: flex; flex-direction: column; gap: 16px;">
                                <div class="form-group">
                                    <label for="invoice_id_display">Invoice ID</label>
                                    <input type="text" id="invoice_id_display" value="<?php echo $auto_invoice_id; ?>" disabled>
                                </div>

                                <div class="form-group">
                                    <label style="color: #718096;">DO ID / Number</label>
                                    <select name="do_id" required>
                                        <option value="" disabled selected>Select Delivery Order</option>
                                        <?php foreach ($delivery_orders as $do): ?>
                                            <option value="<?php echo htmlspecialchars($do['DO_ID']); ?>">
                                                <?php echo htmlspecialchars($do['DO_ID']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group align-start">
                                    <label for="billing_address">Billing Address</label>
                                    <textarea id="billing_address" name="billing_address" required></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="card blue-header-card">
                            <div class="blue-header">Financial Breakdown</div>
                            
                            <div class="blue-card-body">
                                <div class="form-group">
                                    <label style="color: #718096;">INVOICE NUMBER</label>
                                    <input type="text" name="invoice_num" value="" required>
                                </div>

                                <div class="form-group">
                                    <label style="color: #718096;">INVOICE DATE</label>
                                    <input type="date" name="invoice_date" value="" required>
                                </div>

                                <div class="form-group align-start">
                                    <label style="color: #718096;">DESCRIPTION</label>
                                    <textarea name="description"></textarea>
                                </div>

                                <div class="form-group">
                                    <label style="color: #718096;">SUBTOTAL (MYR)</label>
                                    <input type="text" id="subtotal" name="subtotal" value="" placeholder="0.00" required>
                                </div>

                                <div class="form-group">
                                    <label style="color: #718096;">TAX (SST 6%)</label>
                                    <input type="text" id="tax" value="0.00" disabled>
                                </div>

                                <div class="form-group">
                                    <label style="color: #718096;">DISCOUNT / CREDIT NOTE</label>
                                    <input type="text" id="credit_note" name="credit_note" value="" placeholder="0.00">
                                </div>

                                <div class="form-group">
                                    <label style="color: #718096;">PENALTY (MYR)</label>
                                    <input type="text" id="penalty" name="penalty" value="" placeholder="0.00">
                                </div>

                                <div class="form-group">
                                    <label style="color: #718096;">INVOICE STATUS</label>
                                    <select name="invoice_status">
                                        <option value="Submitted" selected>Submitted</option>
                                        <option value="Under Review">Under Review</option>
                                        <option value="Approved">Approved</option>
                                        <option value="Rejected">Rejected</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label style="color: #718096;">PAYMENT STATUS</label>
                                    <select name="payment_status">
                                        <option value="Pending" selected>Pending</option>
                                        <option value="Processing">Processing</option>
                                        <option value="Paid">Paid</option>
                                    </select>
                                </div>

                                <div class="form-group align-start">
                                    <label style="color: #718096;">REASON</label>
                                    <textarea name="reason"></textarea>
                                </div>
                            </div>

                            <div class="total-row">
                                <span class="total-label">TOTAL CLAIM AMOUNT</span>
                                <span class="total-value">MYR <span id="total_display">0.00</span></span>
                            </div>
                        </div>

                    </div>

                    <div class="right-panel">
                        <div class="card">
                            <div class="card-title-section">Supporting Documents</div>
                            <div class="upload-dashed-box" onclick="document.getElementById('file_upload_input').click();">
                                <div class="upload-text-label">Supporting Document</div>
                                <div class="upload-title" id="file_status_title">Upload Document Order</div>
                                <div class="upload-meta">SUPPORTED: PDF, PNG, JPG (MAX 10MB)</div>
                                <div class="select-file-pill" id="file_action_pill">SELECT FILE</div>
                                <input type="file" id="file_upload_input" name="supporting_doc" style="display: none;">
                            </div>
                            <div class="info-disclaimer" style="margin-top: 24px;">
                                Ensure all timestamps and signatures are clearly visible to prevent rejection by the ktm_edois system.
                            </div>
                        </div>

                        <div class="action-container">
                            <button type="submit" name="submit_claim" class="btn-submit">
                                SUBMIT 
                            </button>
                            <a href="inv_list.php" class="btn-cancel">CANCEL</a>
                        </div>
                    </div>

                </div>
            </form>

            <footer class="system-footer">
                © 2026 KTMEDOIS INTEGRATED PORTAL
            </footer>
        </main>
    </div> <script>
        const subtotalInput = document.getElementById('subtotal');
        const taxInput = document.getElementById('tax');
        const creditNoteInput = document.getElementById('credit_note');
        const penaltyInput = document.getElementById('penalty'); 
        const totalDisplay = document.getElementById('total_display');
        const fileInput = document.getElementById('file_upload_input');
        const fileStatusTitle = document.getElementById('file_status_title');
        const fileActionPill = document.getElementById('file_action_pill');
        
        const successModal = document.getElementById('successModal');
        const closeModalBtn = document.getElementById('closeModalBtn');

        function calculateTotalAmount() {
            const subtotal = parseFloat(subtotalInput.value) || 0;
            const tax = subtotal * 0.06;
            taxInput.value = tax.toFixed(2);
            
            const creditNote = parseFloat(creditNoteInput.value) || 0;
            const penalty = parseFloat(penaltyInput.value) || 0;
            
            const dynamicTotal = (subtotal + tax) - creditNote - penalty;
            totalDisplay.textContent = dynamicTotal.toFixed(2);
        }

        fileInput.addEventListener('change', function() {
            if(this.files && this.files.length > 0) {
                fileStatusTitle.textContent = this.files[0].name;
                fileStatusTitle.style.color = "#002D62";
                fileActionPill.textContent = "CHANGE FILE";
            }
        });

        closeModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            successModal.classList.remove('active');
            window.location.href = 'inv_list.php'; 
        });

        subtotalInput.addEventListener('input', calculateTotalAmount);
        creditNoteInput.addEventListener('input', calculateTotalAmount);
        penaltyInput.addEventListener('input', calculateTotalAmount);

        window.addEventListener('DOMContentLoaded', calculateTotalAmount);
    </script>
</body>
</html>
<?php
$conn->close();
?>