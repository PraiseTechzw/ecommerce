<?php
class CartService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getCart($userId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, p.title, p.price, p.image 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addToCart($userId, $productId, $quantity = 1) {
        // Check if item already exists in cart
        $stmt = $this->pdo->prepare("
            SELECT * FROM cart 
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->execute([$userId, $productId]);
        $existingItem = $stmt->fetch();

        if ($existingItem) {
            // Update quantity
            $stmt = $this->pdo->prepare("
                UPDATE cart 
                SET quantity = quantity + ? 
                WHERE user_id = ? AND product_id = ?
            ");
            return $stmt->execute([$quantity, $userId, $productId]);
        } else {
            // Add new item
            $stmt = $this->pdo->prepare("
                INSERT INTO cart (user_id, product_id, quantity) 
                VALUES (?, ?, ?)
            ");
            return $stmt->execute([$userId, $productId, $quantity]);
        }
    }

    public function updateCartItem($userId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($userId, $productId);
        }

        $stmt = $this->pdo->prepare("
            UPDATE cart 
            SET quantity = ? 
            WHERE user_id = ? AND product_id = ?
        ");
        return $stmt->execute([$quantity, $userId, $productId]);
    }

    public function removeFromCart($userId, $productId) {
        $stmt = $this->pdo->prepare("
            DELETE FROM cart 
            WHERE user_id = ? AND product_id = ?
        ");
        return $stmt->execute([$userId, $productId]);
    }

    public function clearCart($userId) {
        $stmt = $this->pdo->prepare("
            DELETE FROM cart 
            WHERE user_id = ?
        ");
        return $stmt->execute([$userId]);
    }

    public function calculateTotal($cart) {
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }
} 