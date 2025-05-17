<?php
session_start();
require_once 'db_connect.php';
include 'includes/sidebar.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle stock removal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_stock'])) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Validate inputs
        if (!isset($_POST['product_id']) || !isset($_POST['quantity']) || !isset($_POST['reason'])) {
            throw new Exception("Missing required fields");
        }

        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        $reason = $_POST['reason'];
        $employee_id = $_SESSION['user_id'];

        // Verify employee exists
        $emp_check = $conn->prepare("SELECT EmployeeID FROM employee WHERE EmployeeID = ?");
        $emp_check->bind_param("i", $employee_id);
        $emp_check->execute();
        if ($emp_check->get_result()->num_rows === 0) {
            throw new Exception("Invalid employee ID");
        }

        $conn->begin_transaction();

        // Check current stock
        $check_stock = $conn->prepare("
            SELECT ProductID, ProductName, StockQuantity 
            FROM products 
            WHERE ProductID = ? 
            FOR UPDATE
        ");
        
        if (!$check_stock) {
            throw new Exception("Database error: " . $conn->error);
        }

        $check_stock->bind_param("i", $product_id);
        $check_stock->execute();
        $result = $check_stock->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Product not found");
        }

        $product = $result->fetch_assoc();

        // Validate quantity
        if ($quantity > $product['StockQuantity']) {
            throw new Exception("Cannot remove {$quantity} items. Only {$product['StockQuantity']} available for {$product['ProductName']}");
        }

        // Record stock out with verified employee ID
        $stockout_stmt = $conn->prepare("
            INSERT INTO stockout_products 
            (ProductID, QuantityRemoved, EmployeeID, Reason, StockOutDate) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stockout_stmt->bind_param("iiis", 
            $product_id, 
            $quantity, 
            $employee_id, 
            $reason
        );

        if (!$stockout_stmt->execute()) {
            throw new Exception("Failed to record stock out: " . $conn->error);
        }

        // Update product stock
        $new_quantity = $product['StockQuantity'] - $quantity;
        $status = $new_quantity <= 0 ? 'Out of Stock' : 'Available';
        
        $update_stmt = $conn->prepare("
            UPDATE products 
            SET 
                StockQuantity = ?,
                Status = ?,
                DateStockout = CURRENT_DATE
            WHERE ProductID = ?
        ");
        
        $update_stmt->bind_param("isi", $new_quantity, $status, $product_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update product stock: " . $conn->error);
        }

        $conn->commit();
        $response['success'] = true;
        $response['message'] = "Successfully removed {$quantity} items";
        $response['new_quantity'] = $new_quantity;
        
    } catch (Exception $e) {
        $conn->rollback();
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Fetch products with their current stock
$products_query = "SELECT * FROM products ORDER BY ProductName";
$products = $conn->query($products_query);

// Get statistics for dashboard cards
$stats_query = "
    SELECT 
        COUNT(*) as total_stockouts,
        SUM(CASE WHEN Reason = 'Waste' THEN 1 ELSE 0 END) as waste_count,
        SUM(CASE WHEN Reason = 'Expired Product' THEN 1 ELSE 0 END) as expired_count,
        SUM(CASE WHEN Reason = 'Damaged' THEN 1 ELSE 0 END) as damaged_count
    FROM stockout_products
    WHERE StockOutDate >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
";
$stmt = $conn->prepare($stats_query);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get stockout records with product details
$sort_order = $_GET['sort'] ?? 'newest';
$order_by = $sort_order === 'oldest' ? 'ASC' : 'DESC';

$stockouts_query = "
    SELECT 
        sp.*,
        p.ProductName,
        p.StockQuantity as CurrentStock,
        e.EmployeeName
    FROM stockout_products sp
    JOIN products p ON sp.ProductID = p.ProductID
    LEFT JOIN employee e ON sp.EmployeeID = e.EmployeeID
    ORDER BY sp.StockOutDate $order_by
";
$stmt = $conn->prepare($stockouts_query);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->execute();
$stockouts = $stmt->get_result();

// Get products for the form
$products_query = "SELECT ProductID, ProductName, StockQuantity FROM products WHERE Status = 'Available' LIMIT 100";
$products = $conn->query($products_query);
if (!$products) {
    die("Database error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Out Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet">
    <style>
       :root {
           --bakery-brown: #8B4513;
           --bakery-light-brown: #DEB887;
           --bakery-cream: #FFEFD5;
           --bakery-tan: #D2B48C;
           --bakery-dark: #654321;
           --bakery-accent: #FFB347;
       }


       body {
           background-color: var(--bakery-cream);
           font-family: 'Quicksand', sans-serif;
           color: var(--bakery-dark);
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
           box-shadow: 0 4px 15px rgba(0,0,0,0.1);
       }


       .stats-card {
           background: white;
           border-radius: 15px;
           padding: 1.5rem;
           margin-bottom: 1.5rem;
           box-shadow: 0 4px 15px rgba(0,0,0,0.1);
           border-left: 5px solid var(--bakery-light-brown);
           transition: transform 0.3s ease;
       }


       .stats-card:hover {
           transform: translateY(-5px);
       }


       .stats-card h3 {
           color: var(--bakery-brown);
           font-weight: 600;
       }


       .data-card {
           background: white;
           border-radius: 15px;
           overflow: hidden;
           margin-bottom: 1.5rem;
           box-shadow: 0 4px 15px rgba(0,0,0,0.1);
       }
       
.btn-primary {
    background-color: var(--bakery-brown);
    border-color: var(--bakery-brown);
}

.btn-primary:hover {
    background-color: var(--bakery-dark);
    border-color: var(--bakery-dark);
}

.toast {
    z-index: 1050;
}


       .card-header {
           background: var(--bakery-cream);
           border-bottom: 2px solid var(--bakery-light-brown);
           padding: 1rem 1.5rem;
       }


       .card-header h5 {
           color: var(--bakery-brown);
           font-weight: 600;
           margin: 0;
       }

       


       .btn-bakery {
           background-color: var(--bakery-brown);
           color: white;
           border: none;
           padding: 0.5rem 1rem;
           border-radius: 8px;
           transition: all 0.3s ease;
       }


       .btn-bakery:hover {
           background-color: var(--bakery-dark);
           transform: translateY(-2px);
           box-shadow: 0 4px 10px rgba(0,0,0,0.1);
       }


       .table {
           margin: 0;
       }


       .table th {
           background-color: var(--bakery-cream);
           color: var(--bakery-brown);
           font-weight: 600;
           border: none;
       }


       .table td {
           vertical-align: middle;
           border-color: rgba(139, 69, 19, 0.1);
       }


       .reason-badge {
           padding: 0.5rem 1rem;
           border-radius: 20px;
           font-size: 0.85rem;
           font-weight: 500;
       }


       .reason-Waste { background-color: #ffcdd2; color: #c62828; }
       .reason-Expired { background-color: #fff9c4; color: #f57f17; }
       .reason-Damaged { background-color: #ffccbc; color: #d84315; }
       .reason-Sold { background-color: #c8e6c9; color: #2e7d32; }


       .modal-content {
           background-color: var(--bakery-cream);
           border: none;
           border-radius: 15px;
       }


       .modal-header {
           background: linear-gradient(135deg, var(--bakery-brown), var(--bakery-dark));
           color: white;
           border-radius: 15px 15px 0 0;
       }


       .form-control:focus {
           border-color: var(--bakery-light-brown);
           box-shadow: 0 0 0 0.25rem rgba(222, 184, 135, 0.25);
       }


       /* Decorative elements */
       .page-header::before {
           content: '🥨';
           font-size: 2rem;
           margin-right: 1rem;
           vertical-align: middle;
       }


       .stats-card::after {
           content: '🍪';
           position: absolute;
           bottom: 1rem;
           right: 1rem;
           font-size: 2rem;
           opacity: 0.1;
       }


       /* Empty state styling */
       .empty-state {
           text-align: center;
           padding: 3rem 1rem;
           color: var(--bakery-brown);
       }


       .empty-state i {
           font-size: 3rem;
           margin-bottom: 1rem;
           opacity: 0.5;
       }


       /* Custom scrollbar */
       ::-webkit-scrollbar {
           width: 8px;
       }


       ::-webkit-scrollbar-track {
           background: var(--bakery-cream);
       }


       ::-webkit-scrollbar-thumb {
           background: var(--bakery-light-brown);
           border-radius: 4px;
       }


       ::-webkit-scrollbar-thumb:hover {
           background: var(--bakery-brown);
       }
   </style>

</head>
<body>
    <div class="content-wrapper">
        <div class="container-fluid">
           <!-- Statistics Cards -->
           <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white stats-card">
                        <div class="card-body">
                            <h6>Total Stock Outs (30 days)</h6>
                            <h3><?php echo htmlspecialchars($stats['total_stockouts'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white stats-card">
                        <div class="card-body">
                            <h6>Waste</h6>
                            <h3><?php echo htmlspecialchars($stats['waste_count'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white stats-card">
                        <div class="card-body">
                            <h6>Expired</h6>
                            <h3><?php echo htmlspecialchars($stats['expired_count'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white stats-card">
                        <div class="card-body">
                            <h6>Damaged</h6>
                            <h3><?php echo htmlspecialchars($stats['damaged_count'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Stock Out Records</h5>
                    <div>
                        <button class="btn btn-primary me-2" id="refreshButton" aria-label="Refresh Data">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    <button class="btn btn-danger" 
                            onclick="showRemoveModal(null, null, null)"
                            aria-label="Remove Stock">
                        <i class="fas fa-minus-circle"></i> Remove Stock
                    </button>
                </div>
            </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <input type="text" id="daterange" class="form-control" placeholder="Date Range">
                        </div>
                        <div class="col-md-2">
                            <input type="text" id="searchBar" class="form-control" placeholder="Search Item Name">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="reasonFilter">
                                <option value="">All Reasons</option>
                                <option value="Sold to Customer">Sold to Customer</option>
                                <option value="Expired Product">Expired Product</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Waste">Waste</option>
                                <option value="Promotional Giveaway">Promotional Giveaway</option>
                                <option value="Employee Use">Employee Use</option>
                                <option value="Lost Item">Lost Item</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="sortFilter">
                                <option value="newest">Most Recent</option>
                                <option value="oldest">Oldest First</option>
                            </select>
                        </div>
                    </div>
                    <!-- Stock Out Records Table -->
                    <div class="stockout-table table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Product</th>
                                    <th>Quantity Removed</th>
                                    <th>Reason</th>
                                    <th>Remaining Stock</th>
                                    <th>Employee</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $stockout_query = "
                                    SELECT 
                                        sp.*,
                                        p.ProductName,
                                        p.StockQuantity,
                                        e.EmployeeName
                                    FROM stockout_products sp
                                    JOIN products p ON sp.ProductID = p.ProductID
                                    LEFT JOIN employee e ON sp.EmployeeID = e.EmployeeID
                                    ORDER BY sp.StockOutDate DESC
                                    LIMIT 50
                                ";
                                $stockouts = $conn->query($stockout_query);
                                while ($row = $stockouts->fetch_assoc()):
                                    $reasonClass = match($row['Reason']) {
                                        'Waste' => 'bg-danger text-white',
                                        'Expired Product' => 'bg-warning text-dark',
                                        'Damaged' => 'bg-info text-white',
                                        'Sold to Customer' => 'bg-success text-white',
                                        'Promotional Giveaway' => 'bg-primary text-white',
                                        'Employee Use' => 'bg-secondary text-white',
                                        'Lost Item' => 'bg-dark text-white',
                                        default => 'bg-secondary text-white'
                                    };
                                ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($row['StockOutDate'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['ProductName']); ?></td>
                                        <td><?php echo $row['QuantityRemoved']; ?></td>
                                        <td>
                                            <span class="reason-badge <?php echo $reasonClass; ?>">
                                                <?php echo $row['Reason']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $row['StockQuantity']; ?></td>
                                        <td><?php echo htmlspecialchars($row['EmployeeName'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Stock Removal Modal -->
            <div class="modal fade" id="removeStockModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-minus-circle"></i> Remove Stock
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="removeStockForm" method="POST">
                                <input type="hidden" name="remove_stock" value="1">
                                <input type="hidden" name="product_id" id="modal_product_id">
                                
                                <div class="mb-4">
                                    <label class="form-label">Select Product</label>
                                    <select class="form-select" name="product_id" id="modal_product_select" required>
                                        <option value="">Choose a product</option>
                                        <?php 
                                        $products_query = "SELECT ProductID, ProductName, StockQuantity FROM products WHERE StockQuantity > 0";
                                        $products = $conn->query($products_query);
                                        while ($product = $products->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $product['ProductID']; ?>" 
                                                    data-stock="<?php echo $product['StockQuantity']; ?>">
                                                <?php echo htmlspecialchars($product['ProductName']); ?> 
                                                (Stock: <?php echo $product['StockQuantity']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Current Stock</label>
                                    <div id="current_stock" class="form-control-plaintext" data-stock="0">-</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Quantity to Remove</label>
                                    <input type="number" name="quantity" id="quantity_input" 
                                           class="form-control" required min="1" 
                                           oninput="updateRemaining()">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Reason</label>
                                    <select name="reason" class="form-select" required>
                                        <option value="">Select Reason</option>
                                        <option value="Sold to Customer">Sold to Customer</option>
                                        <option value="Expired Product">Expired Product</option>
                                        <option value="Damaged">Damaged</option>
                                        <option value="Waste">Waste</option>
                                        <option value="Promotional Giveaway">Promotional Giveaway</option>
                                        <option value="Employee Use">Employee Use</option>
                                        <option value="Lost Item">Lost Item</option>
                                    </select>
                                </div>

                                <div class="alert alert-info">
                                    <strong>Stock After Removal:</strong> 
                                    <span id="remaining_stock" class="badge bg-secondary ms-2">-</span>
                                </div>

                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-check-circle"></i> Confirm Stock Removal
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
            
            <script>
    // Initialize date range picker
    $('#daterange').daterangepicker({
        opens: 'left',
        autoUpdateInput: false, // Prevent automatic input update
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear',
        }
    });

    // Handle date range selection
    $('#daterange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
        applyFilters(); // Apply filters immediately after date selection
    });

    // Handle date range clear
    $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val(''); // Clear the date range input
        applyFilters(); // Apply filters immediately after clearing
    });

    // Function to show the remove stock modal
    // Function to show the remove stock modal
    function showRemoveModal() {
        // Reset form
        document.getElementById('removeStockForm').reset();
        document.getElementById('current_stock').textContent = '-';
        document.getElementById('current_stock').dataset.stock = '0';
        document.getElementById('remaining_stock').textContent = '-';
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('removeStockModal'));
        modal.show();
    }

    // Function to update product details when selection changes
    document.getElementById('modal_product_select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const stockQuantity = selectedOption.dataset.stock;
        document.getElementById('current_stock').textContent = stockQuantity;
        document.getElementById('current_stock').dataset.stock = stockQuantity;
        document.getElementById('remaining_stock').textContent = stockQuantity;
        document.getElementById('quantity_input').max = stockQuantity;
        document.getElementById('quantity_input').value = '';
    });
    // Filter functionality
    function applyFilters() {
        const dateRange = $('#daterange').val(); // Get the selected date range
        const searchTerm = $('#searchBar').val().toLowerCase(); // Get the search term
        const reason = $('#reasonFilter').val(); // Get the selected reason
        const sortOrder = $('#sortFilter').val(); // Get the selected sort order

        // Split the date range into start and end dates
        const [startDateStr, endDateStr] = dateRange.split(' to ');
        const startDate = startDateStr ? new Date(startDateStr) : null;
        const endDate = endDateStr ? new Date(endDateStr) : null;

        let rows = Array.from($('.stockout-table tbody tr'));

        // Filter rows based on search term, date range, and reason
        rows.forEach(row => {
            const $row = $(row);
            const rowDateStr = $row.find('td:eq(0)').text(); // Get the row date in "M d, Y H:i" format
            const rowDate = new Date(rowDateStr); // Convert row date to a Date object
            const rowProductName = $row.find('td:eq(1)').text().toLowerCase(); // Get the product name
            const rowEmployeeName = $row.find('td:eq(5)').text().toLowerCase(); // Get the employee name
            const rowReason = $row.find('td:eq(3)').text(); // Get the reason

            // Check if the row date falls within the selected date range
            const dateMatch = startDate && endDate ? rowDate >= startDate && rowDate <= endDate : true;

            // Check if the row matches the search term (product name or employee name)
            const searchMatch = searchTerm ? 
                (rowProductName.includes(searchTerm) || rowEmployeeName.includes(searchTerm)) : true;

            // Check if the row matches the selected reason
            const reasonMatch = reason ? rowReason.trim() === reason.trim() : true;

            // Show or hide the row based on the filters
            if (dateMatch && searchMatch && reasonMatch) {
                $row.show();
            } else {
                $row.hide();
            }
        });

        // Sort rows based on the selected sort order
        const tbody = $('.stockout-table tbody');
        rows.sort((a, b) => {
            const dateA = new Date($(a).find('td:eq(0)').text());
            const dateB = new Date($(b).find('td:eq(0)').text());
            return sortOrder === 'oldest' ? dateA - dateB : dateB - dateA;
        });

        // Re-append sorted rows to the table
        tbody.empty().append(rows);
    }

    // Update remaining stock
    function updateRemaining() {
        const currentStock = parseInt(document.getElementById('current_stock').dataset.stock);
        const quantity = parseInt(document.getElementById('quantity_input').value) || 0;
        const remaining = currentStock - quantity;
        
        document.getElementById('remaining_stock').textContent = remaining >= 0 ? remaining : '-';
        
        // Add visual feedback
        const remainingElement = document.getElementById('remaining_stock');
        if (remaining < 0) {
            remainingElement.className = 'badge bg-danger ms-2';
        } else if (remaining < 10) {
            remainingElement.className = 'badge bg-warning ms-2';
        } else {
            remainingElement.className = 'badge bg-success ms-2';
        }
    }

    // Form submission handler
    document.getElementById('removeStockForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const currentStock = parseInt(document.getElementById('current_stock').dataset.stock);
        const quantity = parseInt(document.getElementById('quantity_input').value);
        
        if (!quantity || quantity <= 0) {
            alert('Please enter a valid quantity');
            return;
        }
        
        if (quantity > currentStock) {
            alert(`Cannot remove ${quantity} items. Only ${currentStock} available.`);
            return;
        }
        
        const formData = new FormData(this);
        
        fetch(window.location.pathname, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success === false) {
                    throw new Error(data.message || 'Operation failed');
                }
                return data;
            } catch (e) {
                console.error('Server response:', text);
                throw new Error('Invalid response from server');
            }
        })
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error occurred'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('I have successfully removed stock');
        });
    });

    function refreshData() {
    // Show loading state
    const refreshButton = document.getElementById('refreshButton');
    refreshButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    refreshButton.disabled = true;

    // Simply reload the page
    window.location.reload();

    // Note: The button state will automatically reset after page reload
}
// Add event listener for refresh button
document.getElementById('refreshButton').addEventListener('click', refreshData);


    // Initialize filters
    $(document).ready(function() {
        // Add event listeners for filters
        $('#searchBar, #reasonFilter, #sortFilter').on('change keyup', applyFilters);
    });
</script>
        </div>
    </div>
</body>
</html>