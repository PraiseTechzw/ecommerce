<?php
require_once '../includes/header.php';
require_once '../config/database.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    redirect(BASE_URL . '/pages/products.php', 'Product not found', 'error');
}

$db = new Database();
$conn = $db->getConnection();

// Get product details
$product_id = $_GET['id'];
$product = getProduct($conn, $product_id);

if (!$product) {
    redirect(BASE_URL . '/pages/products.php', 'Product not found', 'error');
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        $_SESSION['message'] = 'Please login to add items to cart';
        $_SESSION['message_type'] = 'warning';
        redirect(BASE_URL . '/pages/login.php');
    }

    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if product already in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }

    // If product not in cart, add it
    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity
        ];
    }

    // Return JSON response for AJAX
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'cart_count' => getCartCount(),
            'message' => 'Product added to cart successfully'
        ]);
        exit;
    }

    redirect(BASE_URL . '/pages/cart.php', 'Product added to cart successfully');
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
            
            <form method="POST" id="addToCartForm">
                <div class="quantity-selector">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" class="quantity-input" value="1" min="1" max="10">
                </div>
                <button type="submit" name="add_to_cart" class="add-to-cart-btn">Add to Cart</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addToCartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('product_id', '<?php echo $product_id; ?>');
    
    fetch('<?php echo BASE_URL; ?>/api/cart/add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in header
            document.getElementById('headerCartCount').textContent = data.cart_count;
            
            // Show success message
            Toastify({
                text: data.message,
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                stopOnFocus: true
            }).showToast();
        } else {
            // Handle error
            Toastify({
                text: data.message || "An error occurred",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "linear-gradient(to right, #ff416c, #ff4b2b)",
                stopOnFocus: true
            }).showToast();
            
            // Redirect to login if not authenticated
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
            backgroundColor: "linear-gradient(to right, #ff416c, #ff4b2b)",
            stopOnFocus: true
        }).showToast();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 