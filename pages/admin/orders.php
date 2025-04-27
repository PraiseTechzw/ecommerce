<?php
require_once '../../includes/header_logic.php';

if (!isAdmin()) {
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit;
}

require_once '../../includes/header.php';
require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Handle order status update
if (isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);
    header('Location: orders.php?message=Order status updated successfully');
    exit;
}

// Get all orders with user information
$stmt = $conn->query("
    SELECT o.*, u.name as customer_name, u.email as customer_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Manage Orders</h1>
    </div>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['message']); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($order['customer_name']); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                        </td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="failed" <?php echo $order['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" 
                                    onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                View Details
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
function viewOrderDetails(orderId) {
    // Fetch order details via AJAX
    fetch(`get_order_details.php?id=${orderId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetailsContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
        });
}
</script>

<?php require_once '../../includes/footer.php'; ?> 