<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login to add items to cart',
        'redirect' => BASE_URL . '/pages/login.php'
    ]);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get and validate product ID
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product ID'
    ]);
    exit;
}

if ($quantity <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid quantity'
    ]);
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if product already in cart
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['product_id'] == $product_id) {
        $item['quantity'] += $quantity;
        $found = true;
        break;
    }
}

// If product not in cart, add it
if (!$found) {
    $_SESSION['cart'][] = [
        'product_id' => $product_id,
        'quantity' => $quantity
    ];
}

// Return success response
echo json_encode([
    'success' => true,
    'cart_count' => getCartCount(),
    'message' => 'Product added to cart successfully'
]); 