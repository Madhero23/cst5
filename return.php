<?php
session_start();
include 'includes/sidebar.php';
require_once 'includes/db_connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund & Return Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .refund-container {
            max-width: 800px;
            margin: 120px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .product-details {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            display: none;
        }
        .product-price {
            font-weight: bold;
            color: #1b3c3d;
            font-size: 1.2em;
            margin: 10px 0;
        }
        .alert {
            margin-top: 15px;
        }
        
    </style>
</head>
<body>

      <!-- Display Success/Error Messages -->
      <div id="messageContainer" class="mt-3"></div>

<!-- Refund Requests and History Tables -->
<div class="container mt-4">
    <h2>Refund & Return Management</h2>
    
    <!-- Button to Trigger Modal -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manualRefundModal">
        Process Manual Refund
    </button>

         <!-- Manual Refund Modal -->
         <div class="modal fade" id="manualRefundModal" tabindex="-1" aria-labelledby="manualRefundModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="manualRefundModalLabel">Manual Refund Processing</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="process_manual_refund.php" method="POST" id="manualRefundForm">
                            <div class="form-group">
                                <label for="order_id">Order ID</label>
                                <input type="number" name="order_id" id="order_id" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="customer_id">Customer ID</label>
                                <input type="number" name="customer_id" id="customer_id" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="refund_reason">Refund Reason</label>
                                <textarea name="refund_reason" id="refund_reason" rows="4" class="form-control" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="refund_amount">Refund Amount</label>
                                <input type="number" step="0.01" name="refund_amount" id="refund_amount" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="refund_status">Refund Status</label>
                                <select name="refund_status" id="refund_status" class="form-control" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" form="manualRefundForm" class="btn btn-primary">Submit Refund</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Refund Requests Table -->
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Refund ID</th>
            <th>Order ID</th>
            <th>Customer Name</th>
            <th>Reason</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody id="refund-requests-table">
        <?php
        // Fetch pending refund requests
        $stmt = $conn->query("
            SELECT r.RefundID, r.OrderID, c.CustomerName, r.RefundReason, r.RefundAmount, r.RefundDate, r.Status 
            FROM refund r
            JOIN orders o ON r.OrderID = o.OrderID
            JOIN customer c ON o.CustomerID = c.CustomerID
            WHERE r.Status = 'Pending'
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>
                    <td>{$row['RefundID']}</td>
                    <td>{$row['OrderID']}</td>
                    <td>{$row['CustomerName']}</td>
                    <td>{$row['RefundReason']}</td>
                    <td>{$row['RefundAmount']}</td>
                    <td>{$row['RefundDate']}</td>
                    <td>{$row['Status']}</td>
                    <td>
                        <button class='btn btn-success' onclick='updateRefundStatus({$row['RefundID']}, \"Approved\")'>Approve</button>
                        <button class='btn btn-danger' onclick='updateRefundStatus({$row['RefundID']}, \"Rejected\")'>Reject</button>
                    </td>
                  </tr>";
        }
        ?>
    </tbody>
</table>
        </section>

        <!-- Refund History Table -->
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Refund ID</th>
            <th>Order ID</th>
            <th>Customer Name</th>
            <th>Reason</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody id="refund-table">
        <?php
        // Fetch refund history (approved or rejected refunds)
        $stmt = $conn->query("
            SELECT r.RefundID, r.OrderID, c.CustomerName, r.RefundReason, r.RefundAmount, r.RefundDate, r.Status 
            FROM refund r
            JOIN orders o ON r.OrderID = o.OrderID
            JOIN customer c ON o.CustomerID = c.CustomerID
            WHERE r.Status IN ('Approved', 'Rejected')
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>
                    <td>{$row['RefundID']}</td>
                    <td>{$row['OrderID']}</td>
                    <td>{$row['CustomerName']}</td>
                    <td>{$row['RefundReason']}</td>
                    <td>{$row['RefundAmount']}</td>
                    <td>{$row['RefundDate']}</td>
                    <td>{$row['Status']}</td>
                  </tr>";
        }
        ?>
    </tbody>
</table>
        </section>
    </div>

    <script>
        // JavaScript for dynamic functionality
        $(document).ready(function() {
            // Handle form submission
            $('#manualRefundForm').on('submit', function(e) {
                e.preventDefault(); // Prevent the default form submission

                // Submit the form via AJAX
                $.ajax({
                    url: 'process_manual_refund.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json', // Specify that the response is JSON
                    success: function(response) {
                        console.log(response); // Log the response to the console
                        if (response.status === 'success') {
                            $('#messageContainer').html('<div class="alert alert-success">Manual refund processed successfully!</div>');
                            $('#manualRefundModal').modal('hide');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $('#messageContainer').html('<div class="alert alert-danger">Error: ' + response.message + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText); // Log the error to the console
                        $('#messageContainer').html('<div class="alert alert-danger">An error occurred while processing the refund. Please try again.</div>');
                    }
                });
            });
        });
    function updateRefundStatus(refundId, status) {
        $.ajax({
            url: 'update_refund.php',
            type: 'POST',
            data: { refundId: refundId, status: status },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('Refund status updated successfully!');
                    location.reload(); // Refresh the page to reflect the changes
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while updating the refund status. Please try again.');
            }
        });
    }
    </script>
</body>
</html>