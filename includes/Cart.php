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

    public function addToCart($userId, $productId, $quantity = 1) {
        $conn = $this->db->getConnection();
        
        // Check if product already in cart
        $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingItem) {
            // Update quantity if product exists
            $newQuantity = $existingItem['quantity'] + $quantity;
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            return $stmt->execute([$newQuantity, $existingItem['id']]);
        } else {
            // Add new item to cart
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            return $stmt->execute([$userId, $productId, $quantity]);
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
        $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $items = [];
        foreach ($cartItems as $item) {
            // Check if product ID is from FakeStoreAPI (they use numeric IDs)
            if (is_numeric($item['product_id'])) {
                $product = $this->api->getProduct($item['product_id']);
                if ($product) {
                    $items[] = [
                        'id' => $item['id'],
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'title' => $product['title'],
                        'price' => $product['price'],
                        'image' => $product['image']
                    ];
                }
            } else {
                // Get product from database
                $stmt = $conn->prepare("SELECT title, price, image_url FROM products WHERE id = ?");
                $stmt->execute([$item['product_id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    $items[] = [
                        'id' => $item['id'],
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'title' => $product['title'],
                        'price' => $product['price'],
                        'image' => $product['image_url']
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