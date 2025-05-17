<?php
session_start();
include 'includes/sidebar.php';
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "thebakerydb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination Variables
$limit = 10;
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$offset = ($page - 1) * $limit;

// Search Filter
$searchQuery = trim($_GET['search'] ?? '');
$searchParam = "%$searchQuery%";

// Count Total Suppliers (with or without search)
$countSQL = "SELECT COUNT(*) AS total FROM supplier" . 
            (!empty($searchQuery) ? " WHERE SupplierName LIKE ? OR SupplierContactInfo LIKE ? OR SupplierAddress LIKE ?" : "");

$stmt = $conn->prepare($countSQL);
if (!empty($searchQuery)) {
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
}
$stmt->execute();
$totalRecords = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch Suppliers (with or without search)
$query = "SELECT * FROM supplier" . 
         (!empty($searchQuery) ? " WHERE SupplierName LIKE ? OR SupplierContactInfo LIKE ? OR SupplierAddress LIKE ?" : "") . 
         " LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
if (!empty($searchQuery)) {
    $stmt->bind_param("sssii", $searchParam, $searchParam, $searchParam, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$suppliers = $stmt->get_result();

// Add Supplier
if (isset($_POST['add_supplier'])) {
    $supplier_name = htmlspecialchars($_POST['supplier_name']);
    $supplier_contact = htmlspecialchars($_POST['supplier_contact']);
    $supplier_address = htmlspecialchars($_POST['supplier_address']);

    // Validate inputs
    if (empty($supplier_name)) {
        $_SESSION['error'] = "Supplier name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO supplier (SupplierName, SupplierContactInfo, SupplierAddress) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $supplier_name, $supplier_contact, $supplier_address);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Supplier added successfully!";
        } else {
            $_SESSION['error'] = "Error adding supplier: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: supplier.php");
    exit();
}

// Add Supplier Ingredients
if (isset($_POST['add_ingredients'])) {
    $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
    
    if ($supplier_id > 0) {
        $conn->query("DELETE FROM supplieringredient WHERE SupplierID = $supplier_id");

        $stmt = $conn->prepare("INSERT INTO supplieringredient (SupplierID, IngredientID) VALUES (?, ?)");
        foreach ($_POST['ingredients'] as $ingredient_id) {
            $ingredient_id = intval($ingredient_id);
            $stmt->bind_param("ii", $supplier_id, $ingredient_id);
            $stmt->execute();
        }
        $stmt->close();
        $_SESSION['message'] = "Ingredients updated successfully!";
    } else {
        $_SESSION['error'] = "Error: Supplier ID is missing or invalid.";
    }
    header("Location: supplier.php");
    exit();
}


// Fetch suppliers
if (!empty($searchQuery)) {
    $stmt = $conn->prepare("SELECT * FROM supplier WHERE SupplierName LIKE ? OR SupplierContactInfo LIKE ? OR SupplierAddress LIKE ? LIMIT ? OFFSET ?");
    $stmt->bind_param("sssii", $searchParam, $searchParam, $searchParam, $limit, $offset);
} else {
    $stmt = $conn->prepare("SELECT * FROM supplier LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$suppliers = $stmt->get_result();

// Fetch ingredients (only once)
$ingredients = $conn->query("SELECT * FROM ingredients");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2>Supplier Records</h2>
    <!-- Success/Error Messages -->
    <?php foreach (['message' => 'success', 'error' => 'danger'] as $key => $type): ?>
        <?php if (isset($_SESSION[$key])): ?>
            <div class="alert alert-<?= $type ?>"><?= $_SESSION[$key]; unset($_SESSION[$key]); ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Search Form -->
    <form method="GET" action="supplier.php" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search suppliers..." value="<?= htmlspecialchars($searchQuery) ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>

    <!-- Export Button -->
    <a href="export_supplier.php" class="btn btn-success mb-3">
        <i class="fas fa-download"></i> Export to CSV
    </a>

    <!-- Add Supplier Button -->
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
        <i class="fas fa-plus"></i> Add Supplier
    </button>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Supplier Table -->
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Address</th>
                    <th>Ingredients Supplied</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $suppliers->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['SupplierID'] ?></td>
                        <td><?= $row['SupplierName'] ?></td>
                        <td><?= $row['SupplierContactInfo'] ?></td>
                        <td><?= $row['SupplierAddress'] ?></td>
                        <td>
                            <select class="form-select">
                                <?php
                                $suppliedIngredients = $conn->query("SELECT i.IngredientName FROM supplieringredient si JOIN ingredients i ON si.IngredientID = i.IngredientID WHERE si.SupplierID = " . $row['SupplierID']);
                                while ($ing = $suppliedIngredients->fetch_assoc()) {
                                    echo "<option>" . $ing['IngredientName'] . "</option>";
                                }
                                ?>
                            </select>
                        </td>
                        <td>
                            <button class='btn btn-success' data-bs-toggle='modal' data-bs-target='#editSupplierModal'
                                    onclick='setEditSupplier(<?= $row["SupplierID"] ?>, "<?= addslashes($row["SupplierName"]) ?>", 
                                                             "<?= addslashes($row["SupplierContactInfo"]) ?>", 
                                                             "<?= addslashes($row["SupplierAddress"]) ?>")'>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class='btn btn-warning' data-bs-toggle='modal' data-bs-target='#editIngredientsModal'
                                    onclick='setEditIngredient(<?= $row["SupplierID"] ?>)'>
                                <i class="fas fa-carrot"></i> Edit Ingredients
                            </button>
                            <button class='btn btn-danger' data-bs-toggle='modal' data-bs-target='#deleteSupplierModal'
                                    onclick='setDeleteSupplier(<?= $row["SupplierID"] ?>)'>
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchQuery) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

    <!-- Add Supplier Modal -->
    <div class="modal fade" id="addSupplierModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="supplierForm">
                        <input type="text" name="supplier_name" class="form-control mb-2" placeholder="Supplier Name" required>
                        <input type="text" name="supplier_contact" class="form-control mb-2" placeholder="Contact Info">
                        <textarea name="supplier_address" class="form-control mb-2" placeholder="Address"></textarea>
                        <button type="submit" name="add_supplier" class="btn btn-primary">Add</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Ingredients Modal -->
    <div class="modal fade" id="editIngredientsModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Supplied Ingredients</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" id="supplier_id" name="supplier_id">
                        <label>Select Ingredients:</label>
                        <select name="ingredients[]" class="form-select mb-2" multiple>
                            <?php while ($row = $ingredients->fetch_assoc()): ?>
                                <option value="<?= $row['IngredientID'] ?>"><?= $row['IngredientName'] ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" name="add_ingredients" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Supplier Modal -->
    <div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="edit_supplier.php" method="POST">
                        <input type="hidden" id="editSupplierId" name="supplier_id">
                        <div class="mb-3">
                            <label for="supplierName" class="form-label">Supplier Name</label>
                            <input type="text" class="form-control" id="supplierName" name="supplier_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="supplierContact" class="form-label">Contact Info</label>
                            <input type="text" class="form-control" id="supplierContact" name="supplier_contact">
                        </div>
                        <div class="mb-3">
                            <label for="supplierAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="supplierAddress" name="supplier_address"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Supplier Modal -->
    <div class="modal fade" id="deleteSupplierModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this supplier?</p>
                    <form action="delete_supplier.php" method="POST">
                        <input type="hidden" id="deleteSupplierId" name="supplier_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        // Set Edit Supplier Data
        function setEditSupplier(id, name, contact, address) {
            document.getElementById('editSupplierId').value = id;
            document.getElementById('supplierName').value = name;
            document.getElementById('supplierContact').value = contact;
            document.getElementById('supplierAddress').value = address;
        }

       // Set Edit Ingredients Data
       function setEditIngredient(id) {
            document.getElementById('supplier_id').value = id;
            fetch('get_supplier_ingredients.php?supplier_id=' + id)
                .then(response => response.json())
                .then(data => {
                    const select = document.querySelector('select[name="ingredients[]"]');
                    Array.from(select.options).forEach(option => {
                        option.selected = data.includes(parseInt(option.value));
                    });
                });
        }

        // Set Delete Supplier Data
        function setDeleteSupplier(id) {
            document.getElementById('deleteSupplierId').value = id;
        }

        // Show Toast Notifications
        function showToast(message, type = 'success') {
            toastr[type](message);
        }

        // Display session messages as toasts
        <?php if (isset($_SESSION['message'])): ?>
            showToast("<?= $_SESSION['message']; ?>", 'success');
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            showToast("<?= $_SESSION['error']; ?>", 'error');
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.search.split('=')[1] || 'dashboard';
    
    document.querySelectorAll('.nav-link, .dropdown-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
            
            // If it's a dropdown item, also activate parent
            const dropdownParent = link.closest('.dropdown');
            if (dropdownParent) {
                dropdownParent.querySelector('.nav-link').classList.add('active');
            }
        }
    });
});
    </script>
</body>
</html>