<?php
session_start();
require_once 'db_connect.php';
include 'includes/sidebar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch refund statistics
try {
    $stats_query = $conn->query("
        SELECT 
            COUNT(*) as total_refunds,
            SUM(RefundAmount) as total_refunded,
            COUNT(DISTINCT OrderID) as orders_refunded
        FROM refund
        WHERE RefundDate >= CURDATE() - INTERVAL 30 DAY
    ");
    $stats = $stats_query->fetch_assoc();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while fetching refund statistics.");
}

// Get recent refunds with order and customer details
try {
    $refunds_query = $conn->query("
        SELECT 
            r.*,
            o.OrderDate,
            o.TotalPrice as OrderTotal,
            c.CustomerName,
            e.EmployeeName,
            s.TransactionStatus
        FROM refund r
        JOIN orders o ON r.OrderID = o.OrderID
        JOIN customer c ON o.CustomerID = c.CustomerID
        JOIN employee e ON r.EmployeeID = e.EmployeeID
        LEFT JOIN sales s ON o.OrderID = s.OrderID
        ORDER BY r.RefundDate DESC
        LIMIT 20
    ");
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while fetching refund records.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Refund Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Total Refunds (30 days)</h5>
                        <h3><?php echo $stats['total_refunds']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5>Total Amount Refunded</h5>
                        <h3>€<?php echo number_format($stats['total_refunded'], 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5>Orders Refunded</h5>
                        <h3><?php echo $stats['orders_refunded']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Refund Management Section -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Refund Records</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRefundModal">
                    Process New Refund
                </button>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="date" class="form-control" id="dateFilter" placeholder="Filter by date">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="searchRefund" placeholder="Search refunds...">
                    </div>
                </div>

                <!-- Refunds Table -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Refund ID</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Date</th>
                                <th>Processed By</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($refund = $refunds_query->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $refund['RefundID']; ?></td>
                                <td>#<?php echo $refund['OrderID']; ?></td>
                                <td><?php echo htmlspecialchars($refund['CustomerName']); ?></td>
                                <td>€<?php echo number_format($refund['RefundAmount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($refund['RefundReason']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($refund['RefundDate'])); ?></td>
                                <td><?php echo htmlspecialchars($refund['EmployeeName']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $refund['TransactionStatus'] === 'Refunded' ? 'success' : 
                                            ($refund['TransactionStatus'] === 'Failed' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo $refund['TransactionStatus']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewRefundDetails(<?php echo $refund['RefundID']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-secondary" onclick="printRefundReceipt(<?php echo $refund['RefundID']; ?>)">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- New Refund Modal -->
    <div class="modal fade" id="newRefundModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Process New Refund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="refundForm">
                        <div class="mb-3">
                            <label>Order ID</label>
                            <select class="form-control" name="order_id" required onchange="loadOrderDetails(this.value)">
                                <option value="">Select Order</option>
                                <?php
                                $orders = $conn->query("
                                    SELECT o.OrderID, o.OrderDate, c.CustomerName 
                                    FROM orders o 
                                    JOIN customer c ON o.CustomerID = c.CustomerID 
                                    WHERE o.OrderStatus = 'Completed'
                                    AND o.OrderID NOT IN (SELECT OrderID FROM refund)
                                ");
                                while($order = $orders->fetch_assoc()) {
                                    echo "<option value='{$order['OrderID']}'>
                                        #{$order['OrderID']} - {$order['CustomerName']} ({$order['OrderDate']})
                                    </option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div id="orderDetails" class="mb-3">
                            <!-- Order details will be loaded here -->
                        </div>
                        <div class="mb-3">
                            <label>Refund Amount</label>
                            <input type="number" step="0.01" class="form-control" name="refund_amount" required>
                        </div>
                        <div class="mb-3">
                            <label>Reason for Refund</label>
                            <textarea class="form-control" name="refund_reason" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="processRefund()">Process Refund</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function loadOrderDetails(orderId) {
        if (!orderId) return;
        
        fetch(`get_order_details.php?id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('orderDetails').innerHTML = `
                    <div class="card">
                        <div class="card-body">
                            <p><strong>Customer:</strong> ${data.order.CustomerName}</p>
                            <p><strong>Order Date:</strong> ${new Date(data.order.OrderDate).toLocaleString()}</p>
                            <p><strong>Total Amount:</strong> €${data.order.TotalPrice}</p>
                            <p><strong>Items:</strong></p>
                            <ul>
                                ${data.products.map(product => 
                                    `<li>${product.ProductName} (${product.Quantity}) - €${product.Price}</li>`
                                ).join('')}
                            </ul>
                        </div>
                    </div>
                `;
            });
    }

    function processRefund() {
        const formData = new FormData(document.getElementById('refundForm'));
        
        fetch('process_refund.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Refund processed successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }

    // Add filtering functionality
    document.getElementById('dateFilter').addEventListener('change', function() {
        const date = this.value;
        // Filter table rows based on date
    });

    document.getElementById('searchRefund').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        // Filter table rows based on search term
    });
    </script>
</body>
</html>