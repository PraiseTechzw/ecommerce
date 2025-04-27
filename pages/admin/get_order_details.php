<?php
require_once '../../includes/header_logic.php';

if (!isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Order ID is required');
}

$orderId = $_GET['id'];

// Get order details with user information
$stmt = $conn->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('HTTP/1.1 404 Not Found');
    exit('Order not found');
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, p.title, p.image_url, p.price
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="order-details">
    <div class="row mb-4">
        <div class="col-md-6">
            <h6>Customer Information</h6>
            <p>
                <strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                <strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?>
            </p>
        </div>
        <div class="col-md-6">
            <h6>Order Information</h6>
            <p>
                <strong>Order ID:</strong> #<?php echo $order['id']; ?><br>
                <strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?><br>
                <strong>Status:</strong> 
                <span class="badge bg-<?php 
                    echo $order['status'] === 'completed' ? 'success' : 
                        ($order['status'] === 'pending' ? 'warning' : 'danger'); 
                ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </p>
        </div>
    </div>

    <h6>Order Items</h6>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?php echo BASE_URL . '/' . htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>"
                                     style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </div>
                        </td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                    <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ($order['paypal_order_id']): ?>
        <div class="mt-3">
            <h6>Payment Information</h6>
            <p><strong>PayPal Order ID:</strong> <?php echo htmlspecialchars($order['paypal_order_id']); ?></p>
        </div>
    <?php endif; ?>
</div> 