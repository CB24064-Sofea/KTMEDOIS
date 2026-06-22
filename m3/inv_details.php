<?php
// =========================================================================
// 1. DATABASE CONNECTION
// =========================================================================
$servername = "127.0.0.1:3307";
$username = "root";
$password = "";
$dbname = "ktm_edois";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// =========================================================================
// 2. FETCH INVOICE DETAILS VIA PARAMETERIZED ID
// =========================================================================
$invoice_data = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $invoice_id = intval($_GET['id']);
    
    // Comprehensive query joining delivery_order and customer structures
    $sql = "SELECT i.*, c.customer_name 
            FROM invoice i
            INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
            INNER JOIN customer c ON d.customer_ID = c.customer_ID
            WHERE i.invoice_ID = ? 
            LIMIT 1";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $invoice_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// Fallback mock data setup if database record isn't matching yet for immediate UI testing
if (!$invoice_data) {
    $invoice_data = [
        'invoice_num' => 'INV023',
        'vendor_id' => 'VDR_023',
        'customer_name' => 'Wolo.Enterprise',
        'invoice_date' => '2026-01-06',
        'total' => 2819.00,
        'invoice_status' => 'Finance Review',
        'DO_ID' => 'DO263_2026'
    ];
}

$status_current = $invoice_data['invoice_status'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Claim Details - <?php echo htmlspecialchars($invoice_data['invoice_num']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f3f5f9;
            --card-bg: #ffffff;
            --primary-navy: #002D62;
            --dark-gray: #1a1a1a;
            --border-color: #e2e8f0;
            --text-muted: #757575;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            display: flex;
            height: 100vh;
            overflow: hidden;
            color: #333;
        }

        .workspace {
            flex-grow: 1;
            padding: 40px;
            overflow-y: auto;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Branding header block layout wrapper */
        .brand-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .logo-container {
            height: 50px;
            display: flex;
            align-items: center;
            margin-left: auto; /* Pushes the corporate logo container to the far right edge */
        }

        .logo-container img {
            height: 100%;
            width: auto;
            object-fit: contain;
        }

        .header-title { 
            font-size: 28px; 
            font-weight: 700; 
            color: #000000; 
        }

        /* Two Column Dynamic Layout Grid matching the uploaded UI blueprint */
        .details-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 30px;
            align-items: start;
        }

        .card { 
            background: var(--card-bg); 
            border-radius: 16px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.01); 
            border: 1px solid var(--border-color); 
            padding: 30px; 
            margin-bottom: 24px;
        }

        .section-subtitle {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 20px;
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        /* Metadata Text Layout rows */
        .meta-row {
            display: flex;
            margin-bottom: 14px;
            font-size: 15px;
        }
        .meta-label {
            width: 160px;
            font-weight: 700;
            color: #333333;
        }
        .meta-value {
            color: #4a4a4a;
            font-weight: 500;
        }

        /* Visual Progress Pipeline Stepper Component */
        .status-container {
            margin: 25px 0;
            padding: 12px 16px;
            background-color: #eaedf2;
            border-radius: 6px;
        }
        .status-title-label {
            display: block;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        .status-pipeline {
            display: flex;
            justify-content: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #b0b0b0;
        }
        .step.active-step {
            color: #4a4a4a;
        }
        .step.highlight-step {
            color: #000000;
            font-weight: 700;
        }

        /* Custom Items Breakdown Table layout block */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 13px;
        }
        .items-table th {
            color: #333;
            font-weight: 700;
            text-align: left;
            padding: 10px 8px;
        }
        .items-table td {
            padding: 10px 8px;
            color: #4a4a4a;
        }
        .items-table tbody tr {
            background-color: #eaedf2;
        }
        .items-table tbody tr td:first-child {
            border-top-left-radius: 6px;
            border-bottom-left-radius: 6px;
        }
        .items-table tbody tr td:last-child {
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
        }

        /* Document Attachments File Layout Block List */
        .doc-list {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            border-radius: 8px;
        }
        
        .doc-icon {
            font-size: 18px;
        }

        .doc-list a {
            text-decoration: none;
            color: #4a5568;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .doc-list a:hover {
            color: var(--primary-navy);
        }

        /* Dynamic Yellow Highlight Info Alert Status Strip Component */
        .alert-status-box {
            background-color: #f7f7f7;
            border: 1px solid var(--border-color);
            padding: 14px;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 40px;
        }
        .alert-highlight {
            color: #ffcc00;
            font-weight: 700;
            text-shadow: 0px 0px 1px rgba(0,0,0,0.1);
        }

        /* Back Action Navigation Interface button rules */
        .btn-back-container {
            display: flex;
            justify-content: flex-end;
            margin-top: auto;
        }
        .btn-back {
            display: block;
            width: 100%;
            max-width: 240px;
            background-color: #ffffff;
            color: #000000;
            text-decoration: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            border: 2px solid #000000;
            text-align: center;
            transition: background-color 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .btn-back:hover { 
            background-color: #f1f5f9;
        }

        .system-footer { 
            text-align: center; 
            font-size: 11px; 
            color: #a0aec0; 
            margin-top: auto;
            padding-top: 40px; 
            letter-spacing: 1px; 
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="workspace">
        
        <div class="brand-header">
            <h1 class="header-title">Invoice Claim Details</h1>
            
            <div class="logo-container">
                <img src="ktmb_logo.jpg" alt="KTMB Corporate Logo">
            </div>
        </div>

        <div class="details-grid">
            
            <div class="card">
                <h2 class="section-subtitle">Invoice Details</h2>
                
                <div class="meta-row">
                    <span class="meta-label">Invoice Number</span>
                    <span class="meta-value"><?php echo htmlspecialchars($invoice_data['invoice_num']); ?></span>
                </div>
                
                <div class="meta-row">
                    <span class="meta-label">Vendor ID</span>
                    <span class="meta-value"><?php echo htmlspecialchars($invoice_data['vendor_id'] ?? 'VDR_023'); ?></span>
                </div>

                <div class="meta-row">
                    <span class="meta-label">Company Name</span>
                    <span class="meta-value"><?php echo htmlspecialchars($invoice_data['customer_name']); ?></span>
                </div>

                <div class="meta-row">
                    <span class="meta-label">Invoice Date</span>
                    <span class="meta-value"><?php echo htmlspecialchars($invoice_data['invoice_date']); ?></span>
                </div>

                <div class="meta-row">
                    <span class="meta-label">Claim Amount</span>
                    <span class="meta-value" style="font-weight: 700;">RM <?php echo number_format($invoice_data['total'], 2); ?></span>
                </div>

                <div class="status-container">
                    <span class="status-title-label">Status</span>
                    <div class="status-pipeline">
                        <span class="step active-step">Submitted</span> &gt; 
                        <span class="step highlight-step">Finance Review</span> &gt; 
                        <span class="step">Payment Processing</span> &gt; 
                        <span class="step">Paid</span>
                    </div>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Items Claim</th>
                            <th>Quantity</th>
                            <th>Amount</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 8px solid #ffffff;">
                            <td>Comm Component</td>
                            <td>20</td>
                            <td>RM 30.00</td>
                            <td style="font-weight: 600;">RM 600.00</td>
                        </tr>
                        <tr>
                            <td>Fiber Optic Cable</td>
                            <td>317 m</td>
                            <td>RM 7/m</td>
                            <td style="font-weight: 600;">RM 2219.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div>
                <div class="card" style="background-color: #f7f7f7;">
                    <h2 class="section-subtitle">Supported Documents</h2>
                    
                    <div class="doc-list">
                        <span class="doc-icon">📄</span>
                        <a href="../m3/Delivery Order .png" onclick="printPreviewImage(this.href); return false;">
                            Delivery Order.png
                        </a>
                    </div>
                </div>

                <div class="alert-status-box">
                    Your Invoice is Under <span class="alert-highlight"><?php echo htmlspecialchars($status_current); ?></span>
                </div>

                <div class="btn-back-container">
                    <a href="inv_list.php" class="btn-back">Back</a>
                </div>
            </div>

        </div>

        <footer class="system-footer">
            © 2026 KTMEDOIS INTEGRATED PORTAL
        </footer>
    </main>

    <script>
    function printPreviewImage(imagePath) {
        // 1. Open an isolated window interface context
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        
        // 2. Inject structural markup and layout styles for printing
        printWindow.document.write('<html><head><title>Print Document Preview</title>');
        printWindow.document.write('<style>body{margin:0; display:flex; justify-content:center; align-items:center; height:100vh; background:#fff;} img{max-width:100%; max-height:100%; object-fit:contain;}</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<img src="' + imagePath + '" id="previewTargetImg">');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        
        // 3. Prompt printer driver stream once asset download completes safely
        var img = printWindow.document.getElementById('previewTargetImg');
        img.onload = function() {
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        };
    }
    </script>
</body>
</html>

<?php
// Close data streams right at termination
$conn->close();
?>