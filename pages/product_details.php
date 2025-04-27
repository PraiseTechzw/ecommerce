<?php
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/Cart.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    redirect(BASE_URL . '/pages/products.php', 'Product not found', 'error');
}

$db = new Database();
$conn = $db->getConnection();
$cart = new Cart($conn);

// Get product details
$product_id = $_GET['id'];
$product = getProduct($conn, $product_id);

if (!$product) {
    redirect(BASE_URL . '/pages/products.php', 'Product not found', 'error');
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    error_log("Add to cart form submitted");
    
    if (!isLoggedIn()) {
        error_log("User not logged in, redirecting to login");
        $_SESSION['message'] = 'Please login to add items to cart';
        $_SESSION['message_type'] = 'warning';
        redirect(BASE_URL . '/pages/login.php');
    }

    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $is_api_product = isset($_POST['is_api_product']) ? (bool)$_POST['is_api_product'] : false;
    
    error_log("Processing add to cart - User ID: {$_SESSION['user_id']}, Product ID: $product_id, Quantity: $quantity, Is API Product: " . ($is_api_product ? 'Yes' : 'No'));
    
    try {
        $result = $cart->addToCart($_SESSION['user_id'], $product_id, $quantity, $is_api_product);
        error_log("Add to cart result: " . ($result ? "Success" : "Failed"));
        
        if ($result) {
            // Return JSON response for AJAX
            if (isset($_POST['ajax'])) {
                error_log("Sending AJAX success response");
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'cart_count' => $cart->getCartCount($_SESSION['user_id']),
                    'message' => 'Product added to cart successfully'
                ]);
                exit;
            }

            redirect(BASE_URL . '/pages/cart.php', 'Product added to cart successfully');
        } else {
            error_log("Failed to add product to cart");
            throw new Exception("Failed to add product to cart");
        }
    } catch (Exception $e) {
        error_log("Error adding to cart: " . $e->getMessage());
        if (isset($_POST['ajax'])) {
            error_log("Sending AJAX error response");
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to add product to cart: ' . $e->getMessage()
            ]);
            exit;
        }
        redirect(BASE_URL . '/pages/product_details.php?id=' . $product_id, 'Failed to add product to cart', 'error');
    }
}
?>

<style>
    .product-details {
        padding: 2rem 0;
    }

    .product-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
    }

    .product-image {
        width: 100%;
        height: 400px;
        object-fit: cover;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
    }

    .product-info {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .product-title {
        font-size: 2rem;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .product-price {
        font-size: 1.5rem;
        color: var(--accent-color);
        font-weight: bold;
    }

    .product-category {
        color: var(--secondary-color);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.9rem;
    }

    .product-description {
        line-height: 1.6;
        color: #666;
    }

    .quantity-selector {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin: 1rem 0;
    }

    .quantity-input {
        width: 60px;
        padding: 0.5rem;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .add-to-cart-btn {
        background: var(--secondary-color);
        color: white;
        border: none;
        padding: 0.8rem 2rem;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .add-to-cart-btn:hover {
        background: var(--primary-color);
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .product-container {
            grid-template-columns: 1fr;
        }

        .product-image {
            height: 300px;
        }
    }
</style>

<div class="product-details">
    <div class="product-container">
        <div class="product-image-container">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="product-image">
        </div>
        <div class="product-info">
            <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
            <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>
            <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
            
            <form method="POST" id="addToCartForm" action="<?php echo BASE_URL; ?>/api/cart/add.php">
                <div class="quantity-selector">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" class="quantity-input" value="1" min="1" max="10">
                </div>
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                <input type="hidden" name="is_api_product" value="<?php echo isset($product['is_api_product']) ? '1' : '0'; ?>">
                <button type="submit" name="add_to_cart" class="add-to-cart-btn">Add to Cart</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addToCartForm');
    if (form) {
        console.log('Form found, adding event listener');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted');
            
            const formData = new FormData(form);
            console.log('Form data:', Object.fromEntries(formData));
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Update cart count in header if element exists
                    const cartCount = document.getElementById('headerCartCount');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    // Show success message
                    Toastify({
                        text: data.message,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: {
                            background: "linear-gradient(to right, #00b09b, #96c93d)"
                        }
                    }).showToast();
                } else {
                    // Show error message
                    Toastify({
                        text: data.message || "An error occurred",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: {
                            background: "linear-gradient(to right, #ff416c, #ff4b2b)"
                        }
                    }).showToast();
                    
                    // Redirect to login if needed
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toastify({
                    text: "An error occurred while adding to cart",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: {
                        background: "linear-gradient(to right, #ff416c, #ff4b2b)"
                    }
                }).showToast();
            });
        });
    } else {
        console.error('Form not found!');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 