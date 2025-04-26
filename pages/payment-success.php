<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/PayPalService.php';
require_once __DIR__ . '/../services/CartService.php';
require_once __DIR__ . '/../services/OrderService.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$paypalService = new PayPalService();
$cartService = new CartService($pdo);
$orderService = new OrderService($pdo);

if (isset($_GET['token'])) {
    $order = $paypalService->captureOrder($_GET['token']);
    
    if ($order && $order->status === 'COMPLETED') {
        // Get cart items
        $cart = $cartService->getCart($_SESSION['user_id']);
        $total = $cartService->calculateTotal($cart);
        
        // Create order in database
        $orderService->createOrder($_SESSION['user_id'], $cart, $total, $order->id);
        
        // Clear cart
        $cartService->clearCart($_SESSION['user_id']);
        
        $success = true;
    } else {
        $error = "Payment failed. Please try again.";
    }
} else {
    header('Location: checkout.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - E-Commerce</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <?php if (isset($success)): ?>
            <div class="success-message">
                <h2>Payment Successful!</h2>
                <p>Thank you for your purchase. Your order has been placed successfully.</p>
                <p>Order ID: <?php echo htmlspecialchars($order->id); ?></p>
                <a href="home.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="error-message">
                <h2>Payment Failed</h2>
                <p><?php echo htmlspecialchars($error); ?></p>
                <a href="checkout.php" class="btn btn-primary">Try Again</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html> 