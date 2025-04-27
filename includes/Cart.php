<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/fakestore.php';

class Cart {
    private $db;
    private $api;

    public function __construct() {
        $this->db = new Database();
        $this->api = new FakeStoreAPI();
    }

    public function addToCart($userId, $productId, $quantity = 1, $isApiProduct = false) {
        $conn = $this->db->getConnection();
        
        error_log("Attempting to add to cart - User ID: $userId, Product ID: $productId, Quantity: $quantity, Is API Product: " . ($isApiProduct ? 'Yes' : 'No'));
        
        // Check if product already in cart
        $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND is_api_product = ?");
        $stmt->execute([$userId, $productId, $isApiProduct ? 1 : 0]);
        $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingItem) {
            // Update quantity if product exists
            $newQuantity = $existingItem['quantity'] + $quantity;
            error_log("Product exists in cart. Updating quantity from {$existingItem['quantity']} to $newQuantity");
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $result = $stmt->execute([$newQuantity, $existingItem['id']]);
            error_log("Update result: " . ($result ? "Success" : "Failed"));
            return $result;
        } else {
            // Add new item to cart
            error_log("Adding new product to cart");
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, is_api_product) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$userId, $productId, $quantity, $isApiProduct ? 1 : 0]);
            error_log("Insert result: " . ($result ? "Success" : "Failed"));
            return $result;
        }
    }

    public function removeFromCart($userId, $productId) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$userId, $productId]);
    }

    public function updateQuantity($userId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($userId, $productId);
        }
        
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$quantity, $userId, $productId]);
    }

    public function clearCart($userId) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    public function getCartItems($userId) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT c.*, CASE WHEN c.is_api_product = 1 THEN 'api' ELSE 'local' END as source FROM cart c WHERE c.user_id = ?");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $items = [];
        foreach ($cartItems as $item) {
            if ($item['source'] === 'api') {
                $product = $this->api->getProduct($item['product_id']);
                if ($product) {
                    $items[] = [
                        'id' => $item['id'],
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'title' => $product['title'],
                        'price' => $product['price'],
                        'image' => $product['image'],
                        'is_api_product' => true
                    ];
                }
            } else {
                // Get product from database
                $stmt = $conn->prepare("SELECT title, price, image_url as image FROM products WHERE id = ?");
                $stmt->execute([$item['product_id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    $items[] = [
                        'id' => $item['id'],
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'title' => $product['title'],
                        'price' => $product['price'],
                        'image' => $product['image'],
                        'is_api_product' => false
                    ];
                }
            }
        }
        
        return $items;
    }

    public function getCartTotal($userId) {
        $items = $this->getCartItems($userId);
        $total = 0;
        
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        return $total;
    }

    public function getCartCount($userId) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
} 