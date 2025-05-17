<!-- New Order Modal -->
<div class="modal fade" id="newOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newOrderForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Customer</label>
                            <select class="form-control" name="customer_id" required>
                                <?php
                                $customers = $conn->query("SELECT CustomerID, CustomerName FROM customer");
                                while($customer = $customers->fetch_assoc()) {
                                    echo "<option value='{$customer['CustomerID']}'>{$customer['CustomerName']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Payment Method</label>
                            <select class="form-control" name="payment_method" required>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Online">Online</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="orderItems">
                        <div class="row mb-3 order-item">
                            <div class="col-md-6">
                                <select class="form-control" name="products[]" required>
                                    <?php
                                    $products = $conn->query("SELECT ProductID, ProductName, Price FROM products WHERE Status='Available'");
                                    while($product = $products->fetch_assoc()) {
                                        echo "<option value='{$product['ProductID']}' data-price='{$product['Price']}'>{$product['ProductName']} - ₱{$product['Price']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="number" class="form-control quantity" name="quantities[]" min="1" value="1" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger remove-item">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-info" id="addItem">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h4>Total: ₱<span id="orderTotal">0.00</span></h4>
                        </div>
                        <div class="col-md-6">
                            <label>Cash Received</label>
                            <input type="number" class="form-control" name="cash" step="0.01" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitOrder()">Submit Order</button>
            </div>
        </div>
    </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetails">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>
</div>
