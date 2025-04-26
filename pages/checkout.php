<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/PayPalService.php';
require_once __DIR__ . '/../services/CartService.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$cartService = new CartService($pdo);
$paypalService = new PayPalService();

$cart = $cartService->getCart($_SESSION['user_id']);
$total = $cartService->calculateTotal($cart);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paypal'])) {
    $order = $paypalService->createOrder($cart, $total);
    if ($order) {
        foreach ($order->links as $link) {
            if ($link->rel === 'approve') {
                header('Location: ' . $link->href);
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - E-Commerce</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <h1>Checkout</h1>
        
        <div class="checkout-summary">
            <h2>Order Summary</h2>
            <?php foreach ($cart as $item): ?>
                <div class="cart-item">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <div class="item-details">
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p>Quantity: <?php echo $item['quantity']; ?></p>
                        <p>Price: $<?php echo number_format($item['price'], 2); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="total">
                <h3>Total: $<?php echo number_format($total, 2); ?></h3>
            </div>
        </div>

        <div class="payment-options">
            <h2>Payment Method</h2>
            <form method="POST">
                <button type="submit" name="paypal" class="btn btn-primary">
                    <img src="../assets/images/paypal.png" alt="PayPal" style="height: 24px; vertical-align: middle;">
                    Pay with PayPal
                </button>
            </form>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html> 