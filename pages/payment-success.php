<?php
session_start();
require_once '../includes/Cart.php';
require_once '../services/PayPalService.php';
require_once '../services/OrderService.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$cart = new Cart();
$paypalService = new PayPalService();
$orderService = new OrderService();

$error = null;
$success = null;
$orderDetails = null;

if (isset($_GET['token'])) {
    try {
        // Capture the PayPal order
        $order = $paypalService->captureOrder($_GET['token']);
        
        if ($order && $order->status === 'COMPLETED') {
            // Get cart items and total
            $cartItems = $cart->getCartItems($userId);
            $total = $cart->getCartTotal($userId);
            
            // Create order in database
            $orderId = $orderService->createOrder($userId, $cartItems, $total, $order->id);
            
            // Get order details for display
            $orderDetails = $orderService->getOrderDetails($orderId);
            $success = "Payment successful! Your order has been placed.";
        } else {
            $error = "Payment was not completed successfully.";
        }
    } catch (Exception $e) {
        $error = "Error processing payment: " . $e->getMessage();
        error_log($e->getMessage());
    }
} else {
    $error = "No payment token provided.";
}

require_once '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($orderDetails): ?>
                        <h2 class="mb-4">Order Details</h2>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderDetails as $item): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo htmlspecialchars($item['product_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_title']); ?>" 
                                                     style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php echo htmlspecialchars($item['product_title']); ?>
                                            </td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td><strong>$<?php echo number_format($orderDetails[0]['total_amount'], 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-primary">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 