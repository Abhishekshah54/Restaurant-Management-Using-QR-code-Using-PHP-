document.addEventListener('DOMContentLoaded', function() {
    // Simple cart functionality
    const cart = [];
    const cartItemsEl = document.querySelector('.cart-items');
    const cartTotalEl = document.getElementById('cart-total');
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    // Add to cart
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            const itemName = this.parentElement.querySelector('h3').textContent;
            const itemPrice = parseFloat(this.parentElement.querySelector('.price').textContent.replace('$', ''));
            
            // Check if item already in cart
            const existingItem = cart.find(item => item.id === itemId);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: itemId,
                    name: itemName,
                    price: itemPrice,
                    quantity: 1
                });
            }
            
            updateCart();
        });
    });
    
    // Update cart display
    function updateCart() {
        cartItemsEl.innerHTML = '';
        let total = 0;
        
        cart.forEach(item => {
            const itemEl = document.createElement('div');
            itemEl.className = 'cart-item';
            itemEl.innerHTML = `
                <p>${item.name} x${item.quantity}</p>
                <p>$${(item.price * item.quantity).toFixed(2)}</p>
            `;
            cartItemsEl.appendChild(itemEl);
            total += item.price * item.quantity;
        });
        
        cartTotalEl.textContent = total.toFixed(2);
    }
    
    // Checkout form
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const tableNo = this.querySelector('input[name="table_no"]').value;
            
            if (cart.length === 0) {
                alert('Your cart is empty!');
                return;
            }
            
            // In a real app, you would send this to the server
            fetch('place_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    restaurant_id: <?php echo $restaurant_id; ?>,
                    table_no: tableNo,
                    items: cart
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order placed successfully!');
                    cart.length = 0;
                    updateCart();
                } else {
                    alert('Error placing order: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error placing order');
            });
        });
    }
});