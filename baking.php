<?php
require_once 'db_connect.php';
include 'includes/sidebar.php';

// Fetch categories with valid dates
$categoryQuery = "SELECT * FROM productscategory ORDER BY CategoryName";
$categoryResult = $conn->query($categoryQuery);

// Update product query to properly show all products
$productQuery = "
    SELECT 
        p.*,
        c.CategoryName,
        GROUP_CONCAT(
            DISTINCT CONCAT(
                i.IngredientID,
                ':',
                i.IngredientName,
                ':',
                pi.QuantityRequired,
                ':',
                i.ItemStockQuantity,
                ':',
                i.UnitOfMeasurement,
                ':',
                i.QuantityPerBatch
            ) SEPARATOR '|'
        ) as ingredients
    FROM products p
    LEFT JOIN productscategory c ON p.CategoryID = c.CategoryID
    LEFT JOIN productingredient pi ON p.ProductID = pi.ProductID
    LEFT JOIN ingredients i ON pi.IngredientID = i.IngredientID
    GROUP BY p.ProductID
    ORDER BY p.ProductName";

$products = $conn->query($productQuery);
if (!$products) {
    die("Error fetching products: " . $conn->error);
}

// Modify verified products query to show all products regardless of expiration date
$verifiedQuery = "
    SELECT 
        p.ProductID, 
        p.ProductName,
        p.StockQuantity,
        COALESCE(SUM(pi.QuantityRequired), 0) as BatchSize,
        p.Status
    FROM products p
    LEFT JOIN productingredient pi ON p.ProductID = pi.ProductID
    GROUP BY p.ProductID
    ORDER BY p.ProductName";

$verifiedResult = $conn->query($verifiedQuery);
if (!$verifiedResult) {
    die("Error fetching verified products: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Baking Process</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="baking.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .ingredient-list {
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .batch-calculator {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .insufficient-stock {
            color: #dc3545;
            font-weight: bold;
        }
        .product-card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .nav-tabs .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        .batch-info {
            background: #e9ecef;
            padding: 0.5rem;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }
        .spring-tab {
            border-bottom: 2px solid #0d6efd;
            margin-bottom: 1.5rem;
        }
        .spring-tab .nav-link {
            border: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }
        .spring-tab .nav-link.active {
            border-bottom-color: #0d6efd;
            background: transparent;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Spring Tab Navigation -->
        <ul class="nav nav-tabs spring-tab mb-4" id="productTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#categories">Categories</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#recent">Recent Batches</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#popular">Popular Items</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="categories">
                <!-- Category Tabs -->
                <ul class="nav nav-tabs mb-4" id="categoryTabs">
                    <?php while ($category = $categoryResult->fetch_assoc()) : ?>
                        <li class="nav-item">
                            <button class="nav-link" onclick="showTab(<?php echo $category['CategoryID']; ?>)">
                                <?php echo htmlspecialchars($category['CategoryName']); ?>
                            </button>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <div class="tab-pane fade" id="recent">
                <!-- Recent batches will be loaded here -->
            </div>
            <div class="tab-pane fade" id="popular">
                <!-- Popular items will be loaded here -->
            </div>
        </div>

        <div class="row">
            <!-- Products Section -->
            <div class="col-md-8">
                <div id="product-list" class="row g-3">
                    <!-- Products will be loaded here -->
                </div>
            </div>

            <!-- Enhanced Baking List Section -->
            <div class="col-md-4">
                <div class="card sticky-top" style="top: 1rem;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">🧁 Baking List</h5>
                    </div>
                    <div class="card-body">
                        <div id="baking-list">
                            <!-- Batches will appear here -->
                        </div>
                        <button class="btn btn-success mt-3 w-100" onclick="verifyBaking()">
                            ✅ Verify Baking
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Verified Baking Products Table -->
        <div class="mt-5">
            <h5>Verified Baking Products</h5>
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Total Pieces Produced</th>
                        <th>Batch Size</th>
                        <th>Expiration Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="verified-products">
                    <?php 
                    if ($verifiedResult->num_rows > 0) {
                        while ($product = $verifiedResult->fetch_assoc()) {
                            echo "<tr data-product-id='{$product['ProductID']}'>
                                    <td>{$product['ProductID']}</td>
                                    <td>{$product['ProductName']}</td>
                                    <td>{$product['StockQuantity']}</td>
                                    <td>{$product['BatchSize']}</td>
                                    <td>" . date('M d, Y', strtotime('+7 days')) . "</td>
                                    <td><span class='badge " . 
                                        ($product['Status'] == 'Available' ? 'bg-success' : 'bg-danger') . 
                                        "'>{$product['Status']}</span></td>
                                    <td>
                                        <button class='btn btn-sm btn-success' 
                                                onclick='addToStock({$product['ProductID']}, {$product['BatchSize']})'>
                                            Add to Stock
                                        </button>
                                    </td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>No products found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Add to Batch Modal -->
        <div class="modal fade" id="batchModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add to Baking Batch</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Number of Batches:</label>
                            <input type="number" class="form-control" id="modalBatchSize" min="1" value="1">
                        </div>
                        <div class="mb-3">
                            <label>Total Product Quantity:</label>
                            <div class="alert alert-info">
                                Will produce: <span id="totalQuantity">0</span> pieces
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Expiration Date:</label>
                            <input type="date" class="form-control" id="modalExpirationDate">
                        </div>
                        <div id="ingredientCalculation" class="batch-calculator">
                            <!-- Ingredient calculations with loading placeholder -->
                            <div class="ingredient-requirements">
                                <h6 class="mb-3">Required Ingredients:</h6>
                                <div class="loading-placeholder">
                                    Loading ingredient calculations...
                                </div>
                            </div>
                            <div class="ingredient-requirements">
                                <div class="placeholder-glow">
                                    <span class="placeholder col-12"></span>
                                    <span class="placeholder col-10"></span>
                                    <span class="placeholder col-8"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="confirmAddToBatch()">Add to Batch</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            let currentProductId = null;
            let productIngredients = {};
            let bakingList = [];
            let verifiedProducts = [];

            function showTab(categoryID) {
                // Add loading indicator
                document.getElementById('product-list').innerHTML = '<div class="col-12 text-center">Loading products...</div>';
                
                fetch(`fetch_products.php?categoryID=${categoryID}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(products => {
                        console.log('Fetched products:', products); // Debug line
                        const productList = document.getElementById('product-list');
                        
                        if (!products.length) {
                            productList.innerHTML = '<div class="col-12">No products found for this category.</div>';
                            return;
                        }
                        
                        productList.innerHTML = products.map(product => {
                            const ingredientsList = product.ingredients ? product.ingredients.split('|').map(ing => {
                                const [id, name, required, stock, unit] = ing.split(':');
                                const isInsufficient = parseInt(stock) < parseInt(required);
                                return `
                                    <tr class="${isInsufficient ? 'insufficient-stock' : ''}">
                                        <td>${name}</td>
                                        <td>${required} ${unit}</td>
                                        <td>${stock} ${unit}</td>
                                    </tr>
                                `;
                            }).join('') : '<tr><td colspan="3">No ingredients found</td></tr>';

                            return `
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100 product-card">
                                        <div class="card-header bg-light">
                                            <h5 class="card-title mb-0">${product.ProductName}</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="batch-info">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <small>Quantity per Batch:</small>
                                                        <h6>${product.QuantityPerBatch || 'Not set'}</h6>
                                                    </div>
                                                    <div class="col-6">
                                                        <small>Last Produced:</small>
                                                        <h6>${product.LastProduced || 'N/A'}</h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="ingredient-list">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Ingredient</th>
                                                            <th>Required</th>
                                                            <th>In Stock</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        ${ingredientsList}
                                                    </tbody>
                                                </table>
                                            </div>
                                            <button class="btn btn-primary w-100" 
                                                    onclick="showBatchModal(${product.ProductID}, '${product.ingredients}', ${product.QuantityPerBatch})">
                                                + Add to Batch
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('product-list').innerHTML = 
                            '<div class="col-12 text-center text-danger">Error loading products. Please try again.</div>';
                    });
            }

            function showBatchModal(productId, ingredients, quantityPerBatch) {
                currentProductId = productId;
                productIngredients = ingredients;
                
                const modal = document.getElementById('batchModal');
                const batchSizeInput = document.getElementById('modalBatchSize');
                const totalQuantitySpan = document.getElementById('totalQuantity');
                
                batchSizeInput.addEventListener('input', () => {
                    const total = batchSizeInput.value * quantityPerBatch;
                    totalQuantitySpan.textContent = total;
                    updateIngredientCalculations();
                });
                
                // Set default expiration date (7 days from now)
                const defaultExpDate = new Date();
                defaultExpDate.setDate(defaultExpDate.getDate() + 7);
                document.getElementById('modalExpirationDate').value = defaultExpDate.toISOString().split('T')[0];
                
                // Set default values
                batchSizeInput.value = 1;
                totalQuantitySpan.textContent = quantityPerBatch;
                
                // Show modal
                const batchModal = new bootstrap.Modal(document.getElementById('batchModal'));
                batchModal.show();
                
                // Update ingredient calculations
                updateIngredientCalculations();
            }

            function updateIngredientCalculations() {
                const batchSize = document.getElementById('modalBatchSize').value;
                const calcDiv = document.getElementById('ingredientCalculation');
                // Split ingredients string into array and calculate requirements
                if (productIngredients) {
                    const ingredientsList = productIngredients.split('|').map(ing => {
                        const [id, name, required, stock, unit] = ing.split(':');
                        const totalRequired = required * batchSize;
                        const isInsufficient = parseInt(stock) < totalRequired;
                        
                        return `
                            <div class="row mb-2 ${isInsufficient ? 'text-danger' : ''}">
                                <div class="col-6">${name}:</div>
                                <div class="col-6">
                                    ${totalRequired} ${unit} 
                                    ${isInsufficient ? '(Insufficient stock!)' : ''}
                                </div>
                            </div>
                        `;
                    }).join('');

                    calcDiv.innerHTML = `
                        <h6>Required Ingredients:</h6>
                        ${ingredientsList}
                    `;
                } else {
                    calcDiv.innerHTML = '<p>No ingredients found for this product.</p>';
                }
            }

            function confirmAddToBatch() {
                const batchSize = document.getElementById('modalBatchSize').value;
                const expirationDate = document.getElementById('modalExpirationDate').value;
                
                addToBakingList({
                    productId: currentProductId,
                    batchSize: batchSize,
                    expirationDate: expirationDate,
                    ingredients: productIngredients
                });
                
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('batchModal')).hide();
            }

            function addToBakingList(item) {
                bakingList.push(item);
                updateBakingList();
            }

            function updateBakingList() {
                const list = document.getElementById('baking-list');
                list.innerHTML = bakingList.map((item, index) => `
                    <div class="card mb-2">
                        <div class="card-body">
                            <h6>Batch #${index + 1}</h6>
                            <p>Quantity: ${item.batchSize}</p>
                            <button class="btn btn-sm btn-danger" 
                                    onclick="removeBatch(${index})">❌ Remove</button>
                        </div>
                    </div>
                `).join('');
            }

            function removeBatch(index) {
                bakingList.splice(index, 1);
                updateBakingList();
            }

            function verifyBaking() {
                fetch('process_baking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(bakingList)
                })
                .then(response => response.json())
                .then(result => {
                    if(result.success) {
                        alert('Baking process verified and inventory updated!');
                        bakingList.forEach(item => {
                            verifiedProducts.push({
                                productID: item.productId,
                                productName: result.products[item.productId].name,
                                quantity: item.batchSize,
                                expirationDate: item.expirationDate
                            });
                        });
                        bakingList = [];
                        updateBakingList();
                        updateVerifiedProducts();
                    } else {
                        alert('Error: ' + result.message);
                    }
                });
            }

            function updateVerifiedProducts() {
                const tableBody = document.getElementById('verified-products');
                tableBody.innerHTML = verifiedProducts.map(product => `
                    <tr>
                        <td>${product.productID}</td>
                        <td>${product.productName}</td>
                        <td>${product.quantity}</td>
                        <td>${product.expirationDate}</td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="addToStock(${product.productID})">
                                Add to Stock
                            </button>
                        </td>
                    </tr>
                `).join('');
            }

            function addToStock(productID, quantity) {
                const product = {
                    productID: productID,
                    quantity: quantity,
                    expirationDate: document.querySelector(`tr[data-product-id="${productID}"] td:nth-child(5)`).textContent
                };

                fetch('add_to_stock.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(product)
                })
                .then(response => response.json())
                .then(result => {
                    if(result.success) {
                        alert('Product added to stock successfully!');
                        // Remove the row from the table
                        document.querySelector(`tr[data-product-id="${productID}"]`).remove();
                    } else {
                        alert('Error: ' + result.message);
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Select the first category tab by default
                const firstTab = document.querySelector('#categoryTabs .nav-link');
                if (firstTab) {
                    firstTab.classList.add('active');
                    const categoryID = firstTab.getAttribute('onclick').match(/\d+/)[0];
                    showTab(categoryID);
                }
            });
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
    </div>
</body>
</html>