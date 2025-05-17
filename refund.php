<?php
require_once 'includes/db_connection.php';
session_start();

// Fetch products from database
$stmt = $conn->query("SELECT p.*, pc.CategoryName 
                     FROM products p 
                     LEFT JOIN productscategory pc ON p.CategoryID = pc.CategoryID 
                     ORDER BY pc.CategoryName, p.ProductName");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group products by category
$productsByCategory = [];
foreach ($products as $product) {
    $categoryName = $product['CategoryName'] ?? 'Other';
    if (!isset($productsByCategory[$categoryName])) {
        $productsByCategory[$categoryName] = [];
    }
    $productsByCategory[$categoryName][] = $product;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Refund - The Pastry Kitchen</title>
    <link rel="stylesheet" href="csss/products.css">
    <style>
        .refund-container {
            max-width: 800px;
            margin: 120px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .refund-form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #1b3c3d;
            font-weight: bold;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .submit-btn {
            background: #1b3c3d;
            color: rgb(190, 196, 106);
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }

        .order-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .product-select {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            background: #f8f9fa;
        }

        optgroup {
            font-family: 'Palatino Linotype', serif;
            font-size: 1.1em;
            color: #1b3c3d;
        }

        option {
            padding: 8px;
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

        .product-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .status-available {
            background-color: #d4edda;
            color: #155724;
        }

        .status-outofstock {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- <?php include 'includes/navigation.php'; ?> -->

    <div class="refund-container">
        <h2>Request a Refund</h2>
        
        <form action="process_refund.php" method="POST" class="refund-form">
            <div class="form-group">
                <label for="product">Select Product</label>
                <select name="product_id" id="product" class="product-select" required>
                    <option value="">Select a product</option>
                    <?php foreach ($productsByCategory as $category => $categoryProducts): ?>
                        <optgroup label="<?= htmlspecialchars($category) ?>">
                            <?php foreach ($categoryProducts as $product): ?>
                                <option value="<?= $product['ProductID'] ?>" 
                                        data-price="<?= $product['Price'] ?>"
                                        data-status="<?= $product['Status'] ?>">
                                    <?= htmlspecialchars($product['ProductName']) ?> - 
                                    ₱<?= number_format($product['Price'], 2) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="productDetails" class="product-details">
                <!-- Product details will be displayed here -->
            </div>

            <div class="form-group">
                <label for="reason">Reason for Refund</label>
                <textarea name="reason" id="reason" rows="4" required 
                          placeholder="Please explain why you are requesting a refund..."></textarea>
            </div>

            <button type="submit" class="submit-btn">Submit Refund Request</button>
        </form>
    </div>

    <script>
        document.getElementById('product').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const details = document.getElementById('productDetails');
            
            if (this.value) {
                const price = selected.dataset.price;
                const status = selected.dataset.status;
                
                details.innerHTML = `
                    <div class="product-price">Price: ₱${parseFloat(price).toFixed(2)}</div>
                    <div class="product-status ${status.toLowerCase() === 'available' ? 'status-available' : 'status-outofstock'}">
                        Status: ${status}
                    </div>
                `;
                details.style.display = 'block';
            } else {
                details.style.display = 'none';
            }
        });
    </script>
</body>
</html>