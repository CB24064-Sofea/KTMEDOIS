<?php
// =========================================================================
// SAFE SESSION INITIALIZATION (PREVENTS HEADERS SENT WARNINGS FROM TOPBAR)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
// 2. FETCH INVOICE DATA
// =========================================================================
$sql = "SELECT i.invoice_ID, i.invoice_num, i.DO_ID, i.total, i.invoice_status 
        FROM invoice i 
        ORDER BY i.invoice_ID DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Portal - Invoice List</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f3f5f9;
            --card-bg: #ffffff;
            --primary-navy: #002D62;
            --dark-gray: #1a1a1a;
            --border-color: #e2e8f0;
            
            /* Status Badge Colors */
            --status-submitted: #eef2ff; --text-submitted: #4f46e5;
            --status-review: #fef3c7; --text-review: #d97706;
            --status-approved: #ecfdf5; --text-approved: #059669;
            --status-rejected: #fef2f2; --text-rejected: #dc2626;
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
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            color: #333;
        }

        /* FLEX CONTAINER STRUCTURAL SYNC WITH PORTAL GLOBAL SIDEBAR */
        .app-container {
            display: flex;
            flex-direction: row;
            flex-grow: 1;
            width: 100%;
            overflow: hidden;
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

        .search-container {
            margin-bottom: 25px;
            width: 100%;
            max-width: 400px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-size: 14px;
            color: var(--dark-gray);
            outline: none;
            transition: border-color 0.2s;
        }
        
        .search-input:focus {
            border-color: var(--primary-navy);
            box-shadow: 0 0 0 3px rgba(0, 45, 98, 0.1);
        }

        .card { 
            background: var(--card-bg); 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.02); 
            border: 1px solid var(--border-color); 
            padding: 24px; 
            margin-bottom: 20px;
        }

        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        
        th {
            background-color: #f8fafc;
            color: #4a5568;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            padding: 16px;
            border-bottom: 2px solid var(--border-color);
        }
        td { padding: 16px; border-bottom: 1px solid var(--border-color); color: var(--dark-gray); vertical-align: middle; }
        tr:hover td { background-color: #f8fafc; }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        .status-submitted { background-color: var(--status-submitted); color: var(--text-submitted); }
        .status-under-review { background-color: var(--status-review); color: var(--text-review); }
        .status-finance-review { background-color: var(--status-review); color: var(--text-review); }
        .status-approved { background-color: var(--status-approved); color: var(--text-approved); }
        .status-rejected { background-color: var(--status-rejected); color: var(--text-rejected); }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #f1f5f9;
            color: #334155;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            border: 1px solid #cbd5e1;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-print:hover {
            background-color: var(--primary-navy);
            color: #ffffff;
            border-color: var(--primary-navy);
        }
        .btn-print::before {
            content: "🖨️";
            font-size: 12px;
        }

        .text-bold { font-weight: 600; color: #000; }
        
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

    <?php include('topbar.php'); ?>

    <div class="app-container">
        
        <?php include('../sidebar.php'); ?>

        <main class="workspace">
            
            <div class="header-area">
                <h1 class="header-title">Invoice Preview</h1>
            </div> 
            
            <div class="search-container">
                <input type="text" id="invoiceSearch" class="search-input" placeholder="Search Invoice No. or DO Reference...">
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice No.</th>
                                <th>DO No.</th>
                                <th>Claim Amount</th>
                                <th>Status</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="invoiceTableBody">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <?php 
                                        $status_slug = strtolower(str_replace(' ', '-', $row['invoice_status']));
                                        $inv_status_class = 'status-' . $status_slug;
                                    ?>
                                    <tr>
                                        <td class="searchable-inv"><span class="text-bold"><?php echo htmlspecialchars($row['invoice_num']); ?></span></td>
                                        <td class="searchable-do"><code><?php echo htmlspecialchars($row['DO_ID']); ?></code></td>
                                        <td><span class="text-bold">MYR <?php echo number_format($row['total'], 2); ?></span></td>
                                        <td>
                                            <span class="badge <?php echo $inv_status_class; ?>">
                                                <?php echo htmlspecialchars($row['invoice_status']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="sample inv.jpg" class="btn-print" onclick="printPreviewImage(this.href); return false;">
                                                Print Invoice
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr id="noDataRow">
                                    <td colspan="5" style="text-align: center; padding: 30px; color: #718096;">No invoices found in the system.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <footer class="system-footer">
                © 2026 KTMEDOIS INTEGRATED PORTAL
            </footer>
        </main>

    </div>

    <script>
        document.getElementById('invoiceSearch').addEventListener('keyup', function() {
            const filterValue = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#invoiceTableBody tr:not(#noDataRow)');

            rows.forEach(row => {
                const invCell = row.querySelector('.searchable-inv');
                const doCell = row.querySelector('.searchable-do');
                
                if (invCell && doCell) {
                    const invText = invCell.textContent.toLowerCase();
                    const doText = doCell.textContent.toLowerCase();

                    if (invText.includes(filterValue) || doText.includes(filterValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    </script>

    <script>
        function printPreviewImage(imagePath) {
            var printWindow = window.open('', '_blank', 'width=800,height=600');
            
            printWindow.document.write('<html><head><title>Print Document Preview</title>');
            printWindow.document.write('<style>body{margin:0; display:flex; justify-content:center; align-items:center; height:100vh; background:#fff;} img{max-width:100%; max-height:100%; object-fit:contain;}</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<img src="' + imagePath + '" id="previewTargetImg">');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            
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
$conn->close();
?>