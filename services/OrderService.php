<?php
require_once __DIR__ . '/../config/database.php';

class OrderService {
    private $pdo;

    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    }

    public function createOrder($userId, $cartItems, $total, $paypalOrderId) {
        try {
            $this->pdo->beginTransaction();

            // Create order record
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (user_id, total_amount, paypal_order_id, status, created_at) 
                VALUES (?, ?, ?, 'completed', NOW())
            ");
            $stmt->execute([$userId, $total, $paypalOrderId]);
            $orderId = $this->pdo->lastInsertId();

            // Create order items
            $stmt = $this->pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price, is_api_product) 
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($cartItems as $item) {
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                    $item['is_api_product'] ?? 0
                ]);
            }

            // Clear the cart
            $stmt = $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);

            $this->pdo->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error creating order: " . $e->getMessage());
            throw $e;
        }
    }

    public function getOrderDetails($orderId) {
        $stmt = $this->pdo->prepare("
            SELECT o.*, oi.*, p.title as product_title, p.image_url as product_image
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 