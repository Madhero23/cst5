<?php
session_start();
include("db_connect.php");

// Delete Customer
if (isset($_GET['delete_id'])) {
    $stmt = $conn->prepare("DELETE FROM customer WHERE CustomerID=?");
    $stmt->bind_param("i", $id);
    
    $id = $_GET['delete_id'];

    if ($stmt->execute()) {
        $_SESSION['message'] = "Customer successfully deleted.";
    } else {
        $_SESSION['message'] = "Error deleting customer.";
    }
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Update Customer
if (isset($_POST['update_customer'])) {
    $stmt = $conn->prepare("UPDATE customer SET CustomerName=?, CustomerEmail=?, CustomerBirthday=? WHERE CustomerID=?");
    $stmt->bind_param("sssi", $name, $email, $birthday, $id);
    
    $id = $_POST['customer_id'];
    $name = $_POST['customer_name'];
    $email = $_POST['customer_email'];
    $birthday = $_POST['customer_birthday'];

    if ($stmt->execute()) {
        $_SESSION['message'] = "Customer successfully updated.";
    } else {
        $_SESSION['message'] = "Error updating customer.";
    }
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Display success message if any
if (isset($_SESSION['message'])) {
    echo "<div class='alert alert-success'>" . $_SESSION['message'] . "</div>";
    unset($_SESSION['message']);
}

include 'includes/sidebar.php';

// Pagination setup
$limit = 10; // Customers per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch customers without joining orders to avoid duplicates
$result = $conn->query("SELECT * FROM Customer LIMIT $offset, $limit");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Cookie Corner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bakery-brown: #8B4513;
            --bakery-tan: #DEB887;
            --bakery-cream: #FFEFD5;
            --bakery-light: #FFF8DC;
            --bakery-accent: #D2691E;
            --bakery-text: #4A3728;
        }

        body {
            background-color: var(--bakery-light);
            color: var(--bakery-text);
            font-family: 'Quicksand', sans-serif;
            background-image:
                repeating-linear-gradient(45deg,
                transparent 0,
                transparent 10px,
                rgba(139, 69, 19, 0.03) 10px,
                rgba(139, 69, 19, 0.03) 20px);
        }

        .container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(139, 69, 19, 0.1);
            margin-top: 20px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--bakery-brown), var(--bakery-accent));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .filter-card {
            background-color: var(--bakery-cream);
            border: none;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .table th {
            background-color: var(--bakery-tan);
            color: var(--bakery-text);
            border: none;
            padding: 15px;
        }

        .table td {
            vertical-align: middle;
            border-color: rgba(139, 69, 19, 0.1);
            padding: 12px;
        }

        .btn-bakery {
            background-color: var(--bakery-brown);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-bakery:hover {
            background-color: var(--bakery-accent);
            color: white;
            transform: translateY(-2px);
        }

        .pagination .page-link {
            color: var(--bakery-brown);
            border-color: var(--bakery-tan);
        }

        .pagination .active .page-link {
            background-color: var(--bakery-brown);
            border-color: var(--bakery-brown);
        }

        .modal-content {
            background-color: var(--bakery-light);
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--bakery-brown), var(--bakery-accent));
            color: white;
            border: none;
        }

        .form-control:focus {
            border-color: var(--bakery-tan);
            box-shadow: 0 0 0 0.2rem rgba(222, 184, 135, 0.25);
        }

        .customer-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            border: 1px solid var(--bakery-tan);
        }

        .customer-card:hover {
            transform: translateY(-5px);
        }

        .receipt-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .receipt-header {
            border-bottom: 2px dashed var(--bakery-tan);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        /* Decorative elements */
        .page-header::before {
            content: '🍪';
            font-size: 2rem;
            margin-right: 15px;
            vertical-align: middle;
        }

        .receipt-container::after {
            content: '';
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 50px;
            height: 50px;
            background-image: url('path/to/cookie-pattern.png');
            opacity: 0.1;
        }

        /* Status badges */
        .badge-active {
            background-color: #6a994e;
            color: white;
        }

        .badge-inactive {
            background-color: #bc6c25;
            color: white;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Customer Records</h2>
        <!-- Search and Filter -->
        <div class="row mb-3">
            <div class="col-md-6">
                <input type="text" id="search" class="form-control" placeholder="Search by name or email">
            </div>
            
            <div class="col-md-3">
                <button class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
            </div>
        </div>

        <!-- Customer Table -->
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Birthday</th>
                    <th>Registration Date</th>
                    <th>Actions</th>
                    <th>Order Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $result->fetch_assoc()) {
                    $customerData = json_encode([
                        "id" => $row["CustomerID"],
                        "name" => $row["CustomerName"],
                        "email" => $row["CustomerEmail"],
                        "birthday" => $row["CustomerBirthday"],
                        "registration_date" => $row["RegistrationDate"]
                    ]);
                    echo "<tr>
                        <td>{$row['CustomerID']}</td>
                        <td>{$row['CustomerName']}</td>
                        <td>{$row['CustomerEmail']}</td>
                        <td>{$row['CustomerBirthday']}</td>
                        <td>{$row['RegistrationDate']}</td>
                        <td>
                            <button class='btn btn-info' data-bs-toggle='modal' data-bs-target='#editModal' onclick='fillEditForm(`" . htmlentities($customerData, ENT_QUOTES, "UTF-8") . "`)'>Edit</button>
                            <a href='?delete_id={$row['CustomerID']}' class='btn btn-danger' onclick='return confirmDelete()'>Delete</a>
                        </td>
                        <td>
                            <button class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#receiptModal' onclick='loadReceipt({$row['CustomerID']})'>View Receipt</button>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination">
                <?php
                $result = $conn->query("SELECT COUNT(*) AS total FROM customer");
                $totalRows = $result->fetch_assoc()['total'];
                $totalPages = ceil($totalRows / $limit);

                for ($i = 1; $i <= $totalPages; $i++) {
                    echo "<li class='page-item ".($i == $page ? "active" : "")."'>
                            <a class='page-link' href='?page=$i'>$i</a>
                          </li>";
                }
                ?>
            </ul>
        </nav>
    </div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="receiptContent">
                    <!-- Receipt details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="printReceipt()">Print Receipt</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" id="edit_id" name="customer_id">
                        <input type="text" id="edit_name" name="customer_name" class="form-control mb-2" placeholder="Customer Name" required>
                        <input type="email" id="edit_email" name="customer_email" class="form-control mb-2" placeholder="Email" required>
                        <input type="date" id="edit_birthday" name="customer_birthday" class="form-control mb-2">
                        <button type="submit" name="update_customer" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete this customer?");
        }

        function fillEditForm(data) {
            const customer = JSON.parse(data);
            document.getElementById("edit_id").value = customer.id;
            document.getElementById("edit_name").value = customer.name;
            document.getElementById("edit_email").value = customer.email;
            document.getElementById("edit_birthday").value = customer.birthday;
        }

        function applyFilters() {
            const search = document.getElementById("search").value.toLowerCase();
            const status = document.getElementById("status-filter").value;

            const rows = document.querySelectorAll("tbody tr");
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                const rowStatus = row.cells[4].textContent.toLowerCase();

                const matchesSearch = name.includes(search) || email.includes(search);
                const matchesStatus = status === "" || rowStatus === status;

                row.style.display = matchesSearch && matchesStatus ? "" : "none";
            });
        }

        function loadReceipt(customerId) {
            document.getElementById("receiptContent").innerHTML = '<div class="text-center">Loading...</div>';
            
            fetch(`get_receipt.php?customer_id=${customerId}`)
                .then(response => response.json())
                .then(data => {
                    const receiptContent = document.getElementById("receiptContent");
                    let html = `
                        <div class="receipt">
                            <h3 class="text-center mb-4">Order History</h3>
                            <div class="customer-info mb-4">
                                <p><strong>Customer Name:</strong> ${data.customer_name}</p>
                                <p><strong>Customer Email:</strong> ${data.customer_email}</p>
                            </div>`;

                    if (data.orders && data.orders.length > 0) {
                        data.orders.forEach(order => {
                            html += `
                                <div class="order-section mb-4">
                                    <div class="order-header bg-light p-2">
                                        <h5>Order #${order.order_id}</h5>
                                        <p class="mb-1"><strong>Date:</strong> ${order.order_date}</p>
                                        <p class="mb-1"><strong>Status:</strong> 
                                            <span class="badge ${getStatusBadgeClass(order.order_status)}">
                                                ${order.order_status}
                                            </span>
                                        </p>
                                        <p class="mb-1"><strong>Server:</strong> ${order.server_name}</p>
                                    </div>
                                    
                                    <div class="order-items">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Price</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${order.items.map(item => `
                                                    <tr>
                                                        <td>${item.product_name}</td>
                                                        <td>${item.quantity}</td>
                                                        <td>₱${item.unit_price.toFixed(2)}</td>
                                                        <td>₱${item.subtotal.toFixed(2)}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                                                    <td><strong>₱${order.total_amount.toFixed(2)}</strong></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>Cash Paid:</strong></td>
                                                    <td><strong>₱${order.cash_paid.toFixed(2)}</strong></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>Change:</strong></td>
                                                    <td><strong>₱${(order.cash_paid - order.total_amount).toFixed(2)}</strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <hr>
                            `;
                        });
                    } else {
                        html += '<div class="alert alert-info">No orders found for this customer.</div>';
                    }

                    html += `</div>`;
                    receiptContent.innerHTML = html;
                })
                .catch(error => {
                    console.error("Network error:", error);
                    document.getElementById("receiptContent").innerHTML = 
                        '<div class="alert alert-danger">Network error while loading receipt. Please try again.</div>';
                });
        }

        function getStatusBadgeClass(status) {
            switch(status.toLowerCase()) {
                case 'completed': return 'bg-success';
                case 'pending': return 'bg-warning';
                case 'cancelled': return 'bg-danger';
                default: return 'bg-secondary';
            }
        }

        function printReceipt() {
            const receiptContent = document.getElementById("receiptContent").innerHTML;
            const printWindow = window.open("", "_blank");
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Order Receipt</title>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .receipt { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; }
                            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                            th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
                            .text-center { text-align: center; }
                            .text-end { text-align: right; }
                        </style>
                    </head>
                    <body>
                        ${receiptContent}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>