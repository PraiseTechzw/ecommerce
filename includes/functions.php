<?php
// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect with message
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: $url");
    exit();
}

// Get cart count
function getCartCount() {
    if (!isset($_SESSION['cart'])) {
        return 0;
    }
    return count($_SESSION['cart']);
}

// Format price
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Get product by ID
function getProduct($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Get all products
function getAllProducts($conn) {
    $stmt = $conn->prepare("SELECT * FROM products ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get products by category
function getProductsByCategory($conn, $category) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? ORDER BY created_at DESC");
    $stmt->execute([$category]);
    return $stmt->fetchAll();
}

// Search products
function searchProducts($conn, $query) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE title LIKE ? OR description LIKE ?");
    $search = "%$query%";
    $stmt->execute([$search, $search]);
    return $stmt->fetchAll();
}

// Get user orders
function getUserOrders($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Get order items
function getOrderItems($conn, $order_id) {
    $stmt = $conn->prepare("
        SELECT oi.*, p.title, p.image_url 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll();
}

// Calculate cart total
function calculateCartTotal($conn, $cart) {
    $total = 0;
    foreach ($cart as $item) {
        $product = getProduct($conn, $item['product_id']);
        $total += $product['price'] * $item['quantity'];
    }
    return $total;
}

// Display message
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'success';
        $message = $_SESSION['message'];
        unset($_SESSION['message'], $_SESSION['message_type']);
        return "<div class='alert alert-$type'>$message</div>";
    }
    return '';
} 