<?php
require_once __DIR__ . '/../config/database.php';

class Cart {
    private $db;

    public function __construct() {
        $this->db = new Database();
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
        $stmt = $conn->prepare("
            SELECT c.*, p.title, p.price, p.image 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCartTotal($userId) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            SELECT SUM(p.price * c.quantity) as total 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getCartCount($userId) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
} 