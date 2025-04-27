<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/CartService.php';

// Get database connection
$database = new Database();
$pdo = $database->getConnection();
$cartService = new CartService($pdo);

// Get cart count if user is logged in
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $cartCount = $cartService->getCartCount($_SESSION['user_id']);
}

// Check if user is already logged in and redirect if needed
if (isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) === 'login.php') {
    header('Location: home.php');
    exit();
} 