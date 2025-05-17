<?php
require_once 'includes/db_connection.php';

// Fetch categories
$stmt = $conn->query("SELECT * FROM productscategory");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize cart if not exists
session_start();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - The Pastry Kitchen</title>
    <link rel="stylesheet" href="css/products.css">
</head>
<body>
<nav>
    <div class="navigation-container">
        <img src="images/logo.png" alt="LOGO" class="logo-nav"> 
        
        <div class="nav-wrapper">
            <ul class="nav-links">
                <li><a href="homepage.php">Home</a></li>
                <li><a href="about.php">About us</a></li>
                <li><a href="products.php">Menu</a></li>
                <li><a href="location.php">Store Location</a></li>
                <li><a href="account.php">Account</a></li>
            </ul>
        </div>
    </div>

</nav>
<style>
.navigation-container {
    display: flex;
    align-items: center;
    justify-content: space-between; 
    
    background-color: #1b3c3d;
}

.logo-nav {
    height: 120px;
}

.nav-wrapper {
    flex: 1;
    display: flex;
    justify-content: center;
}

.nav-links {
    display: flex;
    gap: 25px;
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-links li {
    list-style: none;
    font-size: 18px;
    font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif;
    font-weight: 400;
    transition: color 0.3s ease;
}

.nav-links a {
    text-decoration: none;
    color: rgb(190, 196, 106);
    font-size: 18px;
    transition: color 0.3s ease;
}

.nav-links a:hover {
    color: #fff;
}

            
        </style>
           </div>

    <div class="container">
        <div class="products-section">
            <?php foreach ($categories as $category): ?>
                <div class="category-section">
                    <h2><?= htmlspecialchars($category['CategoryName']) ?></h2>
                    <div class="products-grid">
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM products WHERE CategoryID = ?");
                        $stmt->execute([$category['CategoryID']]);
                        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($products as $product):
                        ?>
                            <div class="product-card">
                                <h3><?= htmlspecialchars($product['ProductName']) ?></h3>
                                <p class="price">₱<?= number_format($product['Price'], 2) ?></p>
                                <!-- <p class="stock">Stock: <?= $product['StockQuantity'] ?></p> -->
                                <button class="add-to-cart" 
                                        data-id="<?= $product['ProductID'] ?>"
                                        data-name="<?= htmlspecialchars($product['ProductName']) ?>"
                                        data-price="<?= $product['Price'] ?>">
                                    Add to Cart
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pos-section">
            <h2>Your Order</h2>
            <div id="cart-items">
                <div class="empty-cart-message">Your cart is empty</div>
            </div>
            <div class="total-section">
                <p>Total Amount: ₱<span id="total">0.00</span></p>
                <button id="process-order" class="confirm-btn">Place Order</button>
            </div>
        </div>
    </div>

    <!-- Simplified Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Order Confirmation</h2>
            
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div id="modalCartItems"></div>
                <div class="modal-total">
                    Total: ₱<span id="modalTotal">0.00</span>
                </div>
            </div>

            <div class="payment-section">
                <h3>Payment Method</h3>
                <select id="paymentMethod" class="payment-select">
                    <option value="Cash">Cash Payment</option>
                    <option value="Card">Card Payment</option>
                    <option value="Online">Online Payment</option>
                </select>

                <div id="cashPayment">
                    <input type="number" id="cashAmount" class="payment-input" placeholder="Enter cash amount">
                    <div id="changeAmount"></div>
                </div>

                <div id="onlinePayment" style="display:none;">
                    <p>Reference Number: <span id="refNumber"></span></p>
                </div>

                <button id="confirmOrder" class="confirm-btn">Confirm Payment</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('paymentModal');
        const span = document.getElementsByClassName('close')[0];
        let cart = {};

        // Close modal when clicking X
        span.onclick = function() {
            modal.style.display = "none";
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Handle add to cart
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const price = parseFloat(this.dataset.price);
                
                if (cart[id]) {
                    cart[id].quantity++;
                } else {
                    cart[id] = {
                        name: name,
                        price: price,
                        quantity: 1
                    };
                }
                updateCart();
            });
        });

        // Update cart display
        function updateCart() {
            const cartDiv = document.getElementById('cart-items');
            cartDiv.innerHTML = '';
            let total = 0;

            if (Object.keys(cart).length === 0) {
                cartDiv.innerHTML = '<div class="empty-cart-message">Your cart is empty</div>';
                document.getElementById('process-order').disabled = true;
                return;
            }

            document.getElementById('process-order').disabled = false;

            for (const [id, item] of Object.entries(cart)) {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;

                cartDiv.innerHTML += `
                    <div class="cart-item">
                        <div class="item-details">
                            <span class="item-name">${item.name}</span>
                            <div class="item-quantity">
                                <button onclick="updateQuantity('${id}', -1)">-</button>
                                <span>${item.quantity}</span>
                                <button onclick="updateQuantity('${id}', 1)">+</button>
                            </div>
                        </div>
                        <span class="item-price">₱${itemTotal.toFixed(2)}</span>
                    </div>
                `;
            }

            document.getElementById('total').textContent = total.toFixed(2);
        }

        // Place Order button click handler
        document.getElementById('process-order').addEventListener('click', function() {
            if (Object.keys(cart).length === 0) {
                alert('Your cart is empty');
                return;
            }

            // Update modal content
            const modalCartItems = document.getElementById('modalCartItems');
            modalCartItems.innerHTML = '';
            let total = 0;

            for (const [id, item] of Object.entries(cart)) {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                modalCartItems.innerHTML += `
                    <div class="modal-item">
                        <span>${item.name} × ${item.quantity}</span>
                        <span>₱${itemTotal.toFixed(2)}</span>
                    </div>
                `;
            }

            document.getElementById('modalTotal').textContent = total.toFixed(2);
            modal.style.display = "block";
        });

        // Confirm Order button click handler
        document.getElementById('confirmOrder').addEventListener('click', function() {
    const paymentMethod = document.getElementById('paymentMethod').value;
    const total = parseFloat(document.getElementById('modalTotal').textContent);

    let paymentDetails = {};
    if (paymentMethod === 'Cash') {
        const cashAmount = parseFloat(document.getElementById('cashAmount').value || 0);
        if (cashAmount < total) {
            alert('Insufficient cash amount');
            return;
        }
        paymentDetails = {
            cash: cashAmount
        };
    } else if (paymentMethod === 'Online') {
        paymentDetails = {
            refNumber: document.getElementById('refNumber').textContent
        };
    }

    // Process payment
    fetch('process_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            cart: cart,
            total: total,
            paymentMethod: paymentMethod,
            paymentDetails: paymentDetails
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order placed successfully!');
            cart = {};
            updateCart();
            modal.style.display = "none";
        } else {
            alert('Error processing order: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the order.');
    });
});

        // Handle payment method change
        document.getElementById('paymentMethod').addEventListener('change', function() {
            const cashPayment = document.getElementById('cashPayment');
            const onlinePayment = document.getElementById('onlinePayment');
            
            if (this.value === 'Cash') {
                cashPayment.style.display = 'block';
                onlinePayment.style.display = 'none';
            } else {
                cashPayment.style.display = 'none';
                onlinePayment.style.display = 'block';
                document.getElementById('refNumber').textContent = 'REF' + Date.now();
            }
        });

    // Make updateQuantity function globally available
        window.updateQuantity = function(id, change) {
            if (cart[id]) {
                cart[id].quantity = Math.max(0, cart[id].quantity + change);
                if (cart[id].quantity === 0) {
                    delete cart[id];
                }
                updateCart();
            }
        };
    });
    </script>
</body>
</html>