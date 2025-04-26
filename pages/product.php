<?php
require_once '../includes/header.php';
require_once '../api/fakestore.php';
require_once '../config/database.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize API and Database
$api = new FakeStoreAPI();
$db = new Database();
$conn = $db->getConnection();

// Try to get product from API first
$product = $api->getProduct($product_id);

// If not found in API, try database
if (!$product) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

// If product not found, redirect to products page
if (!$product) {
    redirect('products.php', 'Product not found', 'error');
    exit();
}

// Get related products
$related_products = [];
if ($product['category']) {
    $api_related = $api->getProductsByCategory($product['category']);
    $db_related = getProductsByCategory($conn, $product['category']);
    $related_products = array_merge($api_related, $db_related);
    // Remove current product from related products
    $related_products = array_filter($related_products, function($p) use ($product_id) {
        return $p['id'] != $product_id;
    });
    // Limit to 4 related products
    $related_products = array_slice($related_products, 0, 4);
}
?>

<style>
.product-detail-section {
    padding: 3rem 0;
    background: #f8f9fa;
}

.product-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.product-gallery {
    position: relative;
    padding: 2rem;
    background: white;
}

.main-image {
    width: 100%;
    height: 400px;
    object-fit: contain;
    border-radius: 10px;
    margin-bottom: 1rem;
}

.thumbnail-container {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding: 1rem 0;
}

.thumbnail {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.thumbnail:hover, .thumbnail.active {
    border-color: var(--secondary-color);
    transform: scale(1.05);
}

.product-info {
    padding: 2rem;
    background: white;
}

.product-category {
    color: var(--secondary-color);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.product-title {
    font-size: 2rem;
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.product-price {
    font-size: 1.8rem;
    color: var(--accent-color);
    font-weight: bold;
    margin-bottom: 1rem;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.rating-stars {
    color: #f1c40f;
    display: flex;
    gap: 2px;
}

.rating-count {
    color: #666;
    font-size: 0.9rem;
}

.product-description {
    color: #666;
    line-height: 1.8;
    margin-bottom: 2rem;
}

.product-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 10px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.meta-icon {
    color: var(--secondary-color);
    font-size: 1.2rem;
}

.meta-text {
    color: #666;
    font-size: 0.9rem;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.quantity-btn {
    width: 40px;
    height: 40px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1.2rem;
}

.quantity-btn:hover {
    background: var(--secondary-color);
    color: white;
    border-color: var(--secondary-color);
}

.quantity-input {
    width: 60px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 0.5rem;
    font-size: 1rem;
}

.product-actions {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
}

.btn-add-cart {
    flex: 1;
    padding: 1rem;
    border: none;
    border-radius: 5px;
    background: var(--primary-color);
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-add-cart:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
}

.btn-wishlist {
    width: 50px;
    height: 50px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #666;
}

.btn-wishlist:hover {
    color: var(--accent-color);
    border-color: var(--accent-color);
}

.product-features {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #eee;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.feature-icon {
    width: 40px;
    height: 40px;
    background: var(--secondary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.feature-text {
    color: #666;
    font-size: 0.9rem;
}

.related-products {
    padding: 3rem 0;
}

.related-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 2rem;
    color: var(--primary-color);
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 2rem;
}

.related-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.related-card:hover {
    transform: translateY(-5px);
}

.related-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.related-info {
    padding: 1rem;
}

.related-name {
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--primary-color);
}

.related-price {
    color: var(--accent-color);
    font-weight: bold;
}

/* Stock Status Styles */
.stock-status {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 1rem;
}

.in-stock {
    background: #2ecc71;
    color: white;
}

.low-stock {
    background: #f1c40f;
    color: white;
}

.out-of-stock {
    background: #e74c3c;
    color: white;
}

/* Review Section */
.reviews-section {
    margin-top: 3rem;
    padding-top: 3rem;
    border-top: 1px solid #eee;
}

.review-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.reviewer-name {
    font-weight: 500;
    color: var(--primary-color);
}

.review-date {
    color: #666;
    font-size: 0.9rem;
}

.review-rating {
    color: #f1c40f;
    margin-bottom: 0.5rem;
}

.review-text {
    color: #666;
    line-height: 1.6;
}
</style>

<div class="product-detail-section">
    <div class="container">
        <div class="product-container">
            <div class="row">
                <div class="col-md-6">
                    <div class="product-gallery">
                        <img src="<?php echo htmlspecialchars($product['image'] ?? $product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['title']); ?>" 
                             class="main-image">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="product-info">
                        <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                        <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                        <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                        
                        <div class="product-rating">
                            <div class="rating-stars">
                                <?php
                                $rating = isset($product['rating']['rate']) ? $product['rating']['rate'] : 4.5;
                                $full_stars = floor($rating);
                                $half_star = $rating - $full_stars >= 0.5;
                                
                                for ($i = 0; $i < 5; $i++) {
                                    if ($i < $full_stars) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i == $full_stars && $half_star) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <span class="rating-count">(<?php echo isset($product['rating']['count']) ? $product['rating']['count'] : '0'; ?>)</span>
                        </div>

                        <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>

                        <form id="addToCartForm">
                            <div class="quantity-selector">
                                <label for="quantity">Quantity:</label>
                                <input type="number" id="quantity" name="quantity" class="quantity-input" value="1" min="1" max="10">
                            </div>
                            <div class="product-actions">
                                <button type="submit" class="btn-add-cart">
                                    <i class="fas fa-shopping-cart"></i>
                                    Add to Cart
                                </button>
                                <button type="button" class="btn-wishlist">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($related_products)): ?>
        <div class="related-products mt-5">
            <h2 class="text-center mb-4">Related Products</h2>
            <div class="row">
                <?php foreach ($related_products as $related): ?>
                <div class="col-md-3">
                    <div class="product-card">
                        <a href="<?php echo BASE_URL; ?>/pages/product.php?id=<?php echo $related['id']; ?>" class="product-image-container">
                            <img src="<?php echo htmlspecialchars($related['image'] ?? $related['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($related['title']); ?>" 
                                 class="product-image">
                        </a>
                        <div class="product-info">
                            <span class="product-category"><?php echo htmlspecialchars($related['category']); ?></span>
                            <h3 class="product-title">
                                <a href="<?php echo BASE_URL; ?>/pages/product.php?id=<?php echo $related['id']; ?>" class="product-title-link">
                                    <?php echo htmlspecialchars($related['title']); ?>
                                </a>
                            </h3>
                            <div class="product-price"><?php echo formatPrice($related['price']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
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
    .then(response => {
        if (response.status === 401) {
            // Handle unauthorized (not logged in) case
            return response.json().then(data => {
                // Show login required message
                Toastify({
                    text: data.message || 'Please login to add items to cart',
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "linear-gradient(to right, #ff416c, #ff4b2b)",
                    stopOnFocus: true
                }).showToast();
                
                // Store the current URL to redirect back after login
                localStorage.setItem('redirectAfterLogin', window.location.href);
                
                // Wait for the toast to show before redirecting
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
                
                throw new Error('Login required');
            });
        }
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update cart count in header
            const cartCountElement = document.getElementById('headerCartCount');
            if (cartCountElement) {
                cartCountElement.textContent = data.cart_count;
            }
            
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
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Only show error toast if not redirecting to login
        if (error.message !== 'Login required') {
            Toastify({
                text: "An error occurred while adding to cart. Please try again.",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "linear-gradient(to right, #ff416c, #ff4b2b)",
                stopOnFocus: true
            }).showToast();
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 