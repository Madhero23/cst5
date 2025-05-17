// Initialize date range picker
$(document).ready(function() {
    $('#dateRange').daterangepicker({
        opens: 'left',
        locale: {
            format: 'MM/DD/YYYY'
        }
    });

    // Initialize order items functionality
    initializeOrderItems();
});

function initializeOrderItems() {
    // Add item button
    $('#addItem').click(function() {
        const template = $('.order-item').first().clone();
        template.find('input').val('1');
        $('#orderItems').append(template);
        updateTotal();
    });

    // Remove item button
    $(document).on('click', '.remove-item', function() {
        if ($('.order-item').length > 1) {
            $(this).closest('.order-item').remove();
            updateTotal();
        }
    });

    // Update total when quantity changes
    $(document).on('change', '.quantity', updateTotal);
}

function updateTotal() {
    let total = 0;
    $('.order-item').each(function() {
        const price = $(this).find('select option:selected').data('price');
        const quantity = $(this).find('.quantity').val();
        total += price * quantity;
    });
    $('#orderTotal').text(total.toFixed(2));
}

function submitOrder() {
    const formData = new FormData($('#newOrderForm')[0]);
    
    fetch('process_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order created successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error processing order');
    });
}

function updateStatus(orderId, status) {
    if (!confirm(`Are you sure you want to mark this order as ${status}?`)) {
        return;
    }

    fetch('update_order_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating order status');
        }
    });
}

function generateReport() {
    const dateRange = $('#dateRange').val();
    const status = $('#statusFilter').val();
    
    window.location.href = `generate_report.php?dates=${dateRange}&status=${status}`;
}

function viewOrder(orderId) {
    fetch(`get_order_details.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            const modal = $('#viewOrderModal');
            // Populate modal with order details
            let html = `
                <div class="order-info">
                    <h4>Order #${data.order_id}</h4>
                    <p>Customer: ${data.customer_name}</p>
                    <p>Date: ${data.order_date}</p>
                    <p>Status: <span class="badge bg-${getStatusClass(data.status)}">${data.status}</span></p>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                    ${data.items.map(item => `
                        <tr>
                            <td>${item.name}</td>
                            <td>${item.quantity}</td>
                            <td>₱${item.price}</td>
                            <td>₱${(item.quantity * item.price).toFixed(2)}</td>
                        </tr>
                    `).join('')}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td><strong>₱${data.total}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            `;
            $('#orderDetails').html(html);
            modal.modal('show');
        });
}

function getStatusClass(status) {
    switch(status.toLowerCase()) {
        case 'completed': return 'success';
        case 'pending': return 'warning';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
