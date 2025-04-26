<?php
require_once '../includes/header.php';
require_once '../api/fakestore.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$api = new FakeStoreAPI();
$cart_items = [];
$total = 0;

foreach ($_SESSION['cart'] as $product_id => $quantity) {
    $product = $api->getProduct($product_id);
    if ($product) {
        $product['quantity'] = $quantity;
        $product['subtotal'] = $product['price'] * $quantity;
        $cart_items[] = $product;
        $total += $product['subtotal'];
    }
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $card_number = trim($_POST['card_number'] ?? '');
    $expiry = trim($_POST['expiry'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');

    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($zip)) $errors[] = "ZIP code is required";
    if (empty($card_number)) $errors[] = "Card number is required";
    if (empty($expiry)) $errors[] = "Expiry date is required";
    if (empty($cvv)) $errors[] = "CVV is required";

    if (empty($errors)) {
        // Simulate payment processing
        $payment_success = rand(0, 1); // 50% chance of success

        if ($payment_success) {
            // Create order in database
            $db = new Database();
            $conn = $db->getConnection();

            $product_ids = json_encode(array_keys($_SESSION['cart']));
            $transaction_id = uniqid('TRANS_');

            $stmt = $conn->prepare("INSERT INTO orders (user_id, product_ids, total_price, payment_status, transaction_id) VALUES (?, ?, ?, 'paid', ?)");
            $stmt->execute([$_SESSION['user_id'], $product_ids, $total, $transaction_id]);

            // Clear cart
            $_SESSION['cart'] = [];
            $success = true;
        } else {
            $errors[] = "Payment failed. Please try again.";
        }
    }
}
?>

<h1>Checkout</h1>

<?php if ($success): ?>
    <div class="success-message">
        <h2>Order Successful!</h2>
        <p>Thank you for your purchase. Your order has been placed successfully.</p>
        <a href="home.php" class="btn">Continue Shopping</a>
    </div>
<?php else: ?>
    <?php if (!empty($errors)): ?>
        <div class="error-message">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="checkout-container">
        <div class="order-summary">
            <h2>Order Summary</h2>
            <?php foreach ($cart_items as $item): ?>
                <div class="checkout-item">
                    <span><?php echo htmlspecialchars($item['title']); ?> x <?php echo $item['quantity']; ?></span>
                    <span>$<?php echo number_format($item['subtotal'], 2); ?></span>
                </div>
            <?php endforeach; ?>
            <div class="total">
                <strong>Total:</strong>
                <span>$<?php echo number_format($total, 2); ?></span>
            </div>
        </div>

        <form method="POST" action="" class="checkout-form">
            <h2>Shipping Information</h2>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" required>
            </div>
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" required>
            </div>
            <div class="form-group">
                <label for="zip">ZIP Code</label>
                <input type="text" id="zip" name="zip" required>
            </div>

            <h2>Payment Information</h2>
            <div class="form-group">
                <label for="card_number">Card Number</label>
                <input type="text" id="card_number" name="card_number" required>
            </div>
            <div class="form-group">
                <label for="expiry">Expiry Date (MM/YY)</label>
                <input type="text" id="expiry" name="expiry" required>
            </div>
            <div class="form-group">
                <label for="cvv">CVV</label>
                <input type="text" id="cvv" name="cvv" required>
            </div>

            <button type="submit" class="btn">Place Order</button>
        </form>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?> 