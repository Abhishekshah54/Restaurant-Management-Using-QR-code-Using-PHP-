// Auto-refresh orders every 10 seconds
setInterval(() => {
    fetch('../api/get_orders.php')
        .then(res => res.json())
        .then(orders => {
            // Update UI dynamically
        });
}, 10000);

// Update order status
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
        const orderId = this.getAttribute('data-order-id');
        const newStatus = this.value;

        fetch('../kitchen/update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId, status: newStatus })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Status updated!');
            }
        });
    });
});