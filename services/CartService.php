<?php
class CartService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getCart($userId) {
        // Get local database products from cart
        $stmt = $this->pdo->prepare("
            SELECT c.*, p.title, p.price 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get API products from session
        $apiCart = isset($_SESSION['api_cart']) ? $_SESSION['api_cart'] : [];
        
        // Merge both carts
        return array_merge($cartItems, $apiCart);
    }

    public function addToCart($userId, $productId, $isApiProduct = false, $quantity = 1) {
        if ($isApiProduct) {
            // Handle API products in session
            if (!isset($_SESSION['api_cart'])) {
                $_SESSION['api_cart'] = [];
            }

            // Check if product already exists in API cart
            $found = false;
            foreach ($_SESSION['api_cart'] as &$item) {
                if ($item['product_id'] == $productId) {
                    $item['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                // Get product details from API
                $product = $this->getApiProduct($productId);
                if ($product) {
                    $_SESSION['api_cart'][] = [
                        'product_id' => $productId,
                        'title' => $product['title'],
                        'price' => $product['price'],
                        'quantity' => $quantity
                    ];
                }
            }
            return true;
        } else {
            // Handle local database products
            $stmt = $this->pdo->prepare("
                SELECT * FROM cart 
                WHERE user_id = ? AND product_id = ?
            ");
            $stmt->execute([$userId, $productId]);
            $existingItem = $stmt->fetch();

            if ($existingItem) {
                $stmt = $this->pdo->prepare("
                    UPDATE cart 
                    SET quantity = quantity + ? 
                    WHERE user_id = ? AND product_id = ?
                ");
                return $stmt->execute([$quantity, $userId, $productId]);
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO cart (user_id, product_id, quantity) 
                    VALUES (?, ?, ?)
                ");
                return $stmt->execute([$userId, $productId, $quantity]);
            }
        }
    }

    private function getApiProduct($productId) {
        $url = "https://fakestoreapi.com/products/" . $productId;
        $response = file_get_contents($url);
        return json_decode($response, true);
    }

    public function updateCartItem($userId, $productId, $isApiProduct, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($userId, $productId, $isApiProduct);
        }

        if ($isApiProduct) {
            if (isset($_SESSION['api_cart'])) {
                foreach ($_SESSION['api_cart'] as &$item) {
                    if ($item['product_id'] == $productId) {
                        $item['quantity'] = $quantity;
                        break;
                    }
                }
            }
            return true;
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE cart 
                SET quantity = ? 
                WHERE user_id = ? AND product_id = ?
            ");
            return $stmt->execute([$quantity, $userId, $productId]);
        }
    }

    public function removeFromCart($userId, $productId, $isApiProduct = false) {
        if ($isApiProduct) {
            if (isset($_SESSION['api_cart'])) {
                $_SESSION['api_cart'] = array_filter($_SESSION['api_cart'], function($item) use ($productId) {
                    return $item['product_id'] != $productId;
                });
            }
            return true;
        } else {
            $stmt = $this->pdo->prepare("
                DELETE FROM cart 
                WHERE user_id = ? AND product_id = ?
            ");
            return $stmt->execute([$userId, $productId]);
        }
    }

    public function clearCart($userId) {
        // Clear local database cart
        $stmt = $this->pdo->prepare("
            DELETE FROM cart 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);

        // Clear API cart from session
        unset($_SESSION['api_cart']);
        
        return true;
    }

    public function calculateTotal($cart) {
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    public function getCartCount($userId) {
        // Get count from local database
        $stmt = $this->pdo->prepare("
            SELECT SUM(quantity) as total 
            FROM cart 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        $dbCount = $result['total'] ?? 0;

        // Get count from API cart in session
        $apiCount = 0;
        if (isset($_SESSION['api_cart'])) {
            foreach ($_SESSION['api_cart'] as $item) {
                $apiCount += $item['quantity'];
            }
        }

        return $dbCount + $apiCount;
    }
} 