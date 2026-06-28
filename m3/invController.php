<?php
class InvoiceController {
    private $conn;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    public function getAllInvoices() {
        $sql = "SELECT i.invoice_ID, i.invoice_num, i.DO_ID, i.total, i.invoice_status 
                FROM invoice i 
                ORDER BY i.invoice_ID DESC";
        
        return $this->conn->query($sql);
    }

    public function getInvoiceDetails($invoice_id) {
        $invoice_id = intval($invoice_id);
        
        $sql = "SELECT i.*, c.customer_name 
                FROM invoice i
                INNER JOIN delivery_order d ON i.DO_ID = d.DO_ID
                INNER JOIN customer c ON d.customer_ID = c.customer_ID
                WHERE i.invoice_ID = ? 
                LIMIT 1";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $invoice_data = null;
        if ($result && $result->num_rows > 0) {
            $invoice_data = $result->fetch_assoc();
        }
        
        $stmt->close();
        return $invoice_data;
    }
}
?>