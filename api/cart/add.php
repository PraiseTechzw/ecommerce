<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Cart.php';
require_once __DIR__ . '/../../includes/functions.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Please login to add items to cart',
        'redirect' => BASE_URL . '/pages/login.php'
    ]);
    exit;
}

// Initialize cart
$cart = new Cart();

// Get POST data
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$is_api_product = isset($_POST['is_api_product']) ? (bool)$_POST['is_api_product'] : false;

// Validate input
if ($product_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product ID'
    ]);
    exit;
}

try {
    // Add to cart
    $result = $cart->addToCart($_SESSION['user_id'], $product_id, $quantity, $is_api_product);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'cart_count' => $cart->getCartCount($_SESSION['user_id']),
            'message' => 'Product added to cart successfully'
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add product to cart'
        ]);
    }
} catch (Exception $e) {
    error_log("Error in cart API: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while adding to cart'
    ]);
} 