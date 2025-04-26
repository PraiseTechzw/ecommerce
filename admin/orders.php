<?php
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../api/fakestore.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$api = new FakeStoreAPI();

// Get all orders
$stmt = $conn->query("SELECT o.*, u.name as user_name, u.email as user_email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      ORDER BY o.created_at DESC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Admin - Orders</h1>

<div class="orders-list">
    <?php foreach ($orders as $order): ?>
        <div class="order-card">
            <div class="order-header">
                <h3>Order #<?php echo $order['id']; ?></h3>
                <p>Date: <?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></p>
            </div>
            
            <div class="order-details">
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['user_name']); ?> (<?php echo htmlspecialchars($order['user_email']); ?>)</p>
                <p><strong>Total:</strong> $<?php echo number_format($order['total_price'], 2); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
                <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($order['transaction_id']); ?></p>
            </div>
            
            <div class="order-items">
                <h4>Items:</h4>
                <?php 
                $product_ids = json_decode($order['product_ids'], true);
                foreach ($product_ids as $product_id): 
                    $product = $api->getProduct($product_id);
                    if ($product):
                ?>
                    <div class="order-item">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                        <div class="item-details">
                            <h5><?php echo htmlspecialchars($product['title']); ?></h5>
                            <p class="price">$<?php echo htmlspecialchars($product['price']); ?></p>
                        </div>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 