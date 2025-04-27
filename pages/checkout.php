<?php
// Start session and check login
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize services
require_once '../includes/Cart.php';
require_once '../services/PayPalService.php';

$cart = new Cart();
$userId = $_SESSION['user_id'];
$paypalService = new PayPalService();

// Get cart data
$cartItems = $cart->getCartItems($userId);
$total = $cart->getCartTotal($userId);

$error = null;
$success = null;

// Handle PayPal order creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paypal'])) {
    try {
        $order = $paypalService->createOrder($cartItems, $total);
        if ($order) {
            foreach ($order->links as $link) {
                if ($link->rel === 'approve') {
                    header('Location: ' . $link->href);
                    exit();
                }
            }
            $error = "No approval link found in PayPal response";
        } else {
            $error = "Failed to create PayPal order";
        }
    } catch (Exception $e) {
        $error = "Error creating PayPal order: " . $e->getMessage();
        error_log($e->getMessage());
    }
}

// Now include the header and start output
require_once '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - E-Commerce</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .checkout-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .checkout-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .step {
            background: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
            border: 2px solid #e9ecef;
        }
        
        .step.active {
            border-color: #0d6efd;
            background: #0d6efd;
            color: white;
        }
        
        .step.completed {
            border-color: #198754;
            background: #198754;
            color: white;
        }
        
        .order-summary-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1rem;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .item-price {
            color: #6c757d;
        }
        
        .payment-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .paypal-button {
            background: #0070ba;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .paypal-button:hover {
            background: #005ea6;
            transform: translateY(-2px);
        }
        
        .paypal-button img {
            height: 24px;
        }
        
        .total-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .total-row:last-child {
            margin-bottom: 0;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .alert {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-steps">
            <div class="step completed">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="step active">
                <i class="fas fa-credit-card"></i>
            </div>
            <div class="step">
                <i class="fas fa-check"></i>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="order-summary-card">
                    <h2 class="mb-4">Order Summary</h2>
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo htmlspecialchars($item['image'] ?? 'https://via.placeholder.com/80'); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="item-image">
                            <div class="item-details">
                                <h3 class="item-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="item-price">
                                        Quantity: <?php echo $item['quantity']; ?>
                                    </div>
                                    <div class="item-price">
                                        $<?php echo number_format($item['price'], 2); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="payment-card">
                    <h2 class="mb-4">Payment Method</h2>
                    <form method="POST" id="paypal-form">
                        <button type="submit" name="paypal" class="paypal-button" id="paypal-button">
                            <img src="../assets/images/paypal.png" alt="PayPal">
                            Pay with PayPal
                        </button>
                    </form>
                    
                    <div class="total-section">
                        <div class="total-row">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="total-row">
                            <span>Shipping</span>
                            <span>Free</span>
                        </div>
                        <div class="total-row">
                            <span>Total</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('paypal-form').addEventListener('submit', function(e) {
            console.log('PayPal form submitted');
        });
        
        document.getElementById('paypal-button').addEventListener('click', function(e) {
            console.log('PayPal button clicked');
        });
    </script>
</body>
</html> 