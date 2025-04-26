<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user information from database
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="profile-section">
        <h1 class="text-center mb-4">My Profile</h1>
        
        <div class="profile-card">
            <div class="profile-header">
                <i class="fas fa-user-circle fa-4x"></i>
                <h2><?php echo htmlspecialchars($user['username']); ?></h2>
            </div>
            
            <div class="profile-info">
                <div class="info-group">
                    <label>Email:</label>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                
                <div class="info-group">
                    <label>Member Since:</label>
                    <p><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="profile-actions">
                <a href="edit-profile.php" class="btn btn-primary">Edit Profile</a>
                <a href="change-password.php" class="btn btn-secondary">Change Password</a>
            </div>
        </div>
        
        <div class="order-history mt-4">
            <h2>Order History</h2>
            <?php
            // Get user's orders
            $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($orders)): ?>
                <p class="text-center">No orders found.</p>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <span class="order-id">Order #<?php echo $order['id']; ?></span>
                                <span class="order-date"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-details">
                                <p>Total: $<?php echo number_format($order['total_amount'], 2); ?></p>
                                <p>Status: <span class="status-badge <?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span></p>
                            </div>
                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">View Details</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .profile-section {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem 0;
    }
    
    .profile-card {
        background: white;
        border-radius: 10px;
        padding: 2rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .profile-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .profile-header i {
        color: var(--secondary-color);
        margin-bottom: 1rem;
    }
    
    .profile-info {
        margin-bottom: 2rem;
    }
    
    .info-group {
        margin-bottom: 1rem;
    }
    
    .info-group label {
        font-weight: bold;
        color: var(--primary-color);
    }
    
    .profile-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }
    
    .order-history {
        background: white;
        border-radius: 10px;
        padding: 2rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .order-card {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .order-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
    }
    
    .order-id {
        font-weight: bold;
    }
    
    .order-date {
        color: #666;
    }
    
    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    
    .status-badge.completed {
        background: var(--success-color);
        color: white;
    }
    
    .status-badge.pending {
        background: var(--warning-color);
        color: white;
    }
    
    .status-badge.cancelled {
        background: var(--error-color);
        color: white;
    }
</style>

<?php require_once '../includes/footer.php'; ?> 