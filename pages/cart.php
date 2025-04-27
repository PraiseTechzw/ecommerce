<?php
session_start();
require_once '../includes/Cart.php';

// Check login status
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$cart = new Cart();
$userId = $_SESSION['user_id'];

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
    if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
                    $cart->updateQuantity($userId, $_POST['product_id'], $_POST['quantity']);
                }
                break;
            case 'remove':
                if (isset($_POST['product_id'])) {
                    $cart->removeFromCart($userId, $_POST['product_id']);
                }
                break;
            case 'clear':
                $cart->clearCart($userId);
                break;
        }
        // Redirect to prevent form resubmission
        header('Location: cart.php');
        exit();
    }
}

// Get cart data
$cartItems = $cart->getCartItems($userId);
$cartTotal = $cart->getCartTotal($userId);

// Now include header and start output
require_once '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Shopping Cart</h1>

    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info">
            Your cart is empty. <a href="products.php">Continue shopping</a>
            </div>
        <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars($item['image'] ?? 'https://via.placeholder.com/50'); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                         class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <span class="ms-2"><?php echo htmlspecialchars($item['title']); ?></span>
                                                </div>
                                            </td>
                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                           min="1" class="form-control form-control-sm" style="width: 70px;">
                                                </form>
                                            </td>
                                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                            </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                            <span>$<?php echo number_format($cartTotal, 2); ?></span>
                    </div>
                        <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span>Free</span>
                    </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong>$<?php echo number_format($cartTotal, 2); ?></strong>
                        </div>
                        <a href="checkout.php" class="btn btn-primary w-100 mb-2">Proceed to Checkout</a>
                        <form method="POST">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-outline-danger w-100">Clear Cart</button>
                        </form>
                    </div>
                </div>
                </div>
            </div>
        <?php endif; ?>
</div>

<script>
// Auto-submit form when quantity changes
document.querySelectorAll('input[name="quantity"]').forEach(input => {
    input.addEventListener('change', function() {
        this.form.submit();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>

 