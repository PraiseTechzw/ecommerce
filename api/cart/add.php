<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../api/fakestore.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401); // Unauthorized
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required. Please log in.']);
    exit();
}

header('Content-Type: application/json');

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!$data || !isset($data['product_id']) || !isset($data['quantity'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$product_id = intval($data['product_id']);
$quantity = intval($data['quantity']);

// Validate quantity
if ($quantity <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit();
}

try {
    // First try to get the product from the database
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id, title, price, stock, image_url FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // If not in database, try to get it from the API
    if (!$product) {
        $api = new FakeStoreAPI();
        $apiProduct = $api->getProduct($product_id);
        
        if ($apiProduct) {
            $product = [
                'id' => $apiProduct['id'],
                'title' => $apiProduct['title'],
                'price' => $apiProduct['price'],
                'stock' => 100, // Default stock for API products
                'image_url' => $apiProduct['image'] // Get image from API product
            ];
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit();
        }
    }

    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if product already exists in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] === $product_id) {
            // Check if new total quantity exceeds stock
            $newQuantity = $item['quantity'] + $quantity;
            if ($newQuantity > $product['stock']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot add more items than available in stock']);
                exit();
            }
            $item['quantity'] = $newQuantity;
            $found = true;
            break;
        }
    }
    unset($item); // Unset reference to prevent accidental modifications

    // If product not in cart, add it
    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'price' => $product['price'],
            'title' => $product['title'],
            'image_url' => $product['image_url'] ?? '' // Store image_url
        ];
    }

    // Calculate total quantity in cart
    $totalQuantity = 0;
    foreach ($_SESSION['cart'] as $item) {
        $totalQuantity += $item['quantity'];
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart successfully',
        'totalQuantity' => $totalQuantity
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    exit();
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    exit();
}
?> 