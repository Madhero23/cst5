<?php
include 'db_connect.php'; // Include your database connection file

// Start session and handle form submissions before any output
session_start();

// Fetch products
$productQuery = "SELECT ProductID, ProductName, StockQuantity FROM products";
$productResult = mysqli_query($conn, $productQuery);
if (!$productResult) {
    die("Error fetching products: " . mysqli_error($conn));
}

// Handle stock-in form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id']) && isset($_POST['quantity']) && isset($_POST['stockin_date'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $stockin_date = $_POST['stockin_date'];
    $employee_id = $_SESSION['user_id'] ?? null;

    // Check if employee ID is set and valid
    if ($employee_id === null) {
        $_SESSION['error'] = "Employee ID is not set. Please log in again.";
        header("Location: stockin.php");
        exit();
    }

    // Verify that the employee ID exists in the employee table
    $employee_check_query = $conn->prepare("SELECT EmployeeID FROM employee WHERE EmployeeID = ?");
    $employee_check_query->bind_param("i", $employee_id);
    $employee_check_query->execute();
    $employee_check_result = $employee_check_query->get_result();

    if ($employee_check_result->num_rows === 0) {
        $_SESSION['error'] = "Invalid Employee ID. Please log in again.";
        header("Location: stockin.php");
        exit();
    }

    // Insert stock-in record
    $stockin_stmt = $conn->prepare("
        INSERT INTO stockin_products (ProductID, QuantityAdded, EmployeeID, StockInDate)
        VALUES (?, ?, ?, ?)
    ");
    $stockin_stmt->bind_param("iiis", $product_id, $quantity, $employee_id, $stockin_date);

    if ($stockin_stmt->execute()) {
        // Update product stock quantity
        $update_product_stmt = $conn->prepare("
            UPDATE products
            SET StockQuantity = StockQuantity + ?
            WHERE ProductID = ?
        ");
        $update_product_stmt->bind_param("ii", $quantity, $product_id);
        $update_product_stmt->execute();

        $_SESSION['success'] = "Stock added successfully!";
    } else {
        $_SESSION['error'] = "Error adding stock: " . $stockin_stmt->error;
    }

    header("Location: stockin.php");
    exit();
}

// Fetch stock-in history for a specific product
$history = [];
if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $historyQuery = "
        SELECT si.StockInDate, si.QuantityAdded, e.EmployeeName
        FROM stockin_products si
        JOIN employee e ON si.EmployeeID = e.EmployeeID
        WHERE si.ProductID = ?
        ORDER BY si.StockInDate DESC
    ";
    $historyStmt = $conn->prepare($historyQuery);
    $historyStmt->bind_param("i", $product_id);
    $historyStmt->execute();
    $historyResult = $historyStmt->get_result();
    while ($row = $historyResult->fetch_assoc()) {
        $history[] = $row;
    }
}

include 'includes/sidebar.php'; // Include sidebar after handling form submissions
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock In Products - Bakery Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bakery-brown: #8B4513;
            --bakery-light: #DEB887;
            --bakery-cream: #FFEFD5;
            --bakery-accent: #D2691E;
            --bakery-dark: #654321;
            --card-shadow: 0 4px 15px rgba(139, 69, 19, 0.1);
        }

        body {
            background-color: var(--bakery-cream);
            font-family: 'Quicksand', sans-serif;
            background-image:
                repeating-linear-gradient(45deg,
                transparent 0,
                transparent 10px,
                rgba(139, 69, 19, 0.03) 10px,
                rgba(139, 69, 19, 0.03) 20px);
        }

        .content-wrapper {
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(135deg, var(--bakery-brown), var(--bakery-dark));
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .product-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            border-left: 5px solid var(--bakery-accent);
            transition: transform 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .filters-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .form-label {
            color: var(--bakery-dark);
            font-weight: 600;
        }

        .form-control:focus {
            border-color: var(--bakery-light);
            box-shadow: 0 0 0 0.2rem rgba(222, 184, 135, 0.25);
        }

        .btn-primary {
            background-color: var(--bakery-brown);
            border-color: var(--bakery-brown);
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--bakery-dark);
            border-color: var(--bakery-dark);
            transform: translateY(-2px);
        }

        .modal-content {
            background-color: var(--bakery-cream);
            border: none;
            border-radius: 15px;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--bakery-brown), var(--bakery-dark));
            color: white;
            border-bottom: none;
            border-radius: 15px 15px 0 0;
        }

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .table th {
            background-color: var(--bakery-light);
            color: var(--bakery-dark);
            font-weight: 600;
            border: none;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 4px solid #dc3545;
        }

        /* Decorative elements */
        .page-header::before {
            content: '🥖';
            font-size: 2rem;
            margin-right: 1rem;
            vertical-align: middle;
        }

        .product-card::after {
            content: '🍪';
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            font-size: 2rem;
            opacity: 0.1;
        }

        .datepicker {
            background-color: white !important;
            border-radius: 10px !important;
            border: 1px solid var(--bakery-light) !important;
        }

        /* Animation for success message */
        @keyframes slideIn {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert {
            animation: slideIn 0.3s ease-out;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bakery-cream);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--bakery-light);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--bakery-brown);
        }
    </style>
</head>
<body class="bg-light">
    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Stock In Products</h2>
                    <p class="text-muted">Manage product stock-in operations</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="refreshData()">
                        <i class="fas fa-sync-alt me-2"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Display session messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Stock In Form -->
            <div class="filters-section mb-4">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="product_id" class="form-label">Product</label>
                            <select class="form-select" id="product_id" name="product_id" required>
                                <option value="">Select Product</option>
                                <?php while ($row = mysqli_fetch_assoc($productResult)): ?>
                                    <option value="<?= $row['ProductID']; ?>">
                                        <?= htmlspecialchars($row['ProductName']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="quantity" class="form-label">Quantity to Add</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required min="1">
                        </div>
                        <div class="col-md-4">
                            <label for="stockin_date" class="form-label">Stock In Date</label>
                            <input type="text" class="form-control datepicker" id="stockin_date" name="stockin_date" required>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary w-100">Add Stock</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Products List -->
            <div class="row g-4">
                <?php
                mysqli_data_seek($productResult, 0); // Reset pointer
                while ($product = mysqli_fetch_assoc($productResult)): ?>
                    <div class="col-md-4">
                        <div class="product-card p-3">
                            <h5 class="card-title"><?= htmlspecialchars($product['ProductName']); ?></h5>
                            <p class="card-text">Current Stock: <?= $product['StockQuantity']; ?></p>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#historyModal" data-product-id="<?= $product['ProductID']; ?>">
                                View Stock In History
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Modal for Stock In History -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="historyModalLabel">Stock In History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Quantity</th>
                                <th>Employee</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <!-- History rows will be populated here -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script>
        // Initialize datepicker
        $(document).ready(function() {
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });
        });

        // Handle modal opening and fetch history
        $('#historyModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var productId = button.data('product-id'); // Extract product ID from data-* attributes
            var modal = $(this);

            // Fetch history via AJAX
            $.ajax({
                url: 'fetch_stock_history.php',
                type: 'GET',
                data: { product_id: productId },
                success: function(response) {
                    $('#historyTableBody').html(response); // Populate the table body
                },
                error: function() {
                    $('#historyTableBody').html('<tr><td colspan="3">Error loading history.</td></tr>');
                }
            });
        });

        function exportHistory() {
            alert('Export functionality will be implemented here');
        }

        function refreshData() {
            location.reload();
        }
    </script>
</body>
</html>