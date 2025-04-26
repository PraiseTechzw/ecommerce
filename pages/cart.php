<?php
require_once '../includes/header.php';
require_once '../api/fakestore.php'; // Keep for potential future use, but not needed for basic display now
require_once '../config/database.php'; // Needed for potential updates/removals

// Initialize cart if not exists
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// --- Helper function to find item index by ID ---
function findCartItemIndex($product_id) {
    foreach ($_SESSION['cart'] as $index => $item) {
        if ($item['product_id'] === $product_id) {
            return $index;
        }
    }
    return false;
}

// --- Handle cart actions (Update Quantity) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $index = findCartItemIndex($product_id);

        if ($index !== false && $quantity > 0) {
            // TODO: Add stock check here if needed before updating quantity
            $_SESSION['cart'][$index]['quantity'] = $quantity;
        } elseif ($index !== false && $quantity <= 0) {
            // Remove item if quantity is 0 or less
            array_splice($_SESSION['cart'], $index, 1);
        }
        // Redirect to avoid form resubmission on refresh
        header("Location: cart.php");
        exit();
    }
}

// --- Handle remove action (using GET for simplicity here, POST is better practice) ---
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    $index = findCartItemIndex($product_id);
    if ($index !== false) {
        array_splice($_SESSION['cart'], $index, 1);
    }
    // Redirect to avoid accidental removal on refresh
    header("Location: cart.php");
    exit();
}

// --- Calculate Cart Totals --- 
$cart_items = $_SESSION['cart']; // Use the session data directly
$total = 0;

foreach ($cart_items as &$item) { // Use reference to potentially add subtotal if needed
    $item['subtotal'] = ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
    $total += $item['subtotal'];
}
unset($item); // Unset reference

?>

<style>
/* Add some basic styling for the cart */
.cart-section {
    max-width: 900px;
    margin: 2rem auto;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 10px;
}

.empty-cart {
    padding: 3rem 0;
    color: #6c757d;
}

.cart-items {
    border-top: 1px solid #dee2e6;
    margin-top: 1.5rem;
}

.cart-item {
    display: flex;
    padding: 1.5rem 0;
    border-bottom: 1px solid #dee2e6;
    gap: 1.5rem;
    align-items: flex-start;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 5px;
    border: 1px solid #dee2e6;
}

.cart-item-details {
    flex-grow: 1;
}

.cart-item-details .product-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.cart-item-details .product-price {
    font-size: 1rem;
    color: #495057;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.quantity-input {
    width: 50px;
    text-align: center;
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 0.375rem 0.75rem;
}

.quantity-btn {
    padding: 0.375rem 0.75rem;
    line-height: 1.5;
}

.subtotal {
    font-weight: 500;
    color: var(--primary-color);
}

.cart-summary {
    margin-top: 2rem;
    display: flex;
    justify-content: flex-end;
}

.summary-card {
    width: 100%;
    max-width: 350px;
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    font-size: 1rem;
}

.summary-row.total {
    font-weight: bold;
    font-size: 1.2rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}

.btn-block {
    display: block;
    width: 100%;
}

</style>

<div class="container">
    <section class="cart-section">
        <h1 class="text-center mb-4">Shopping Cart</h1>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart text-center">
                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                <p class="mb-3">Your cart is empty.</p>
                <a href="products.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $index => $item): ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image_url'] ?? '/public/images/placeholder.png'); ?>" 
                             alt="<?php echo htmlspecialchars($item['title'] ?? 'Product Image'); ?>"
                             class="cart-item-image">
                        <div class="cart-item-details">
                            <h3 class="product-title"><?php echo htmlspecialchars($item['title'] ?? 'Product Title'); ?></h3>
                            <p class="product-price mb-2">Price: $<?php echo number_format($item['price'] ?? 0, 2); ?></p>
                            
                            <form method="POST" action="" class="update-quantity-form d-inline-block">
                                <input type="hidden" name="update_cart" value="1">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <div class="quantity-control">
                                    <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn" onclick="updateQuantity(this, -1)">-</button>
                                    <input type="number" 
                                           name="quantity" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="0" 
                                           max="99" 
                                           class="quantity-input form-control form-control-sm d-inline-block" 
                                           onchange="this.form.submit()" /* Optional: auto-update on change */>
                                    <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn" onclick="updateQuantity(this, 1)">+</button>
                                </div>
                                <!-- Remove submit button if using onchange, or keep for manual update -->
                                <!-- <button type="submit" class="btn btn-sm btn-primary mt-2">Update</button> -->
                            </form>
                            
                            <p class="subtotal mt-2">Subtotal: $<?php echo number_format($item['subtotal'], 2); ?></p>
                            <a href="?remove=<?php echo $item['product_id']; ?>" class="btn btn-sm btn-outline-danger mt-2" onclick="return confirm('Are you sure you want to remove this item?');">
                                <i class="fas fa-trash"></i> Remove
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cart-summary mt-4">
                <div class="summary-card">
                    <h3 class="mb-3">Order Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>Free</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <a href="checkout.php" class="btn btn-primary btn-block mt-3">Proceed to Checkout</a>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
function updateQuantity(button, change) {
    const form = button.closest('form');
    const input = form.querySelector('.quantity-input');
    const currentValue = parseInt(input.value);
    const newValue = currentValue + change;
    
    if (newValue >= 0 && newValue <= 99) { // Allow 0 to remove via update
        input.value = newValue;
        // Automatically submit the form when quantity changes via buttons
        form.submit(); 
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>

 