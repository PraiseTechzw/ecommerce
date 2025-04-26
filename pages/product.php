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
                <!-- Product Gallery -->
                <div class="col-md-6">
                    <div class="product-gallery">
                        <img src="<?php echo htmlspecialchars($product['image_url'] ?? $product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['title']); ?>"
                             class="main-image"
                             id="mainImage">
                        <div class="thumbnail-container">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?? $product['image']); ?>" 
                                 alt="Thumbnail"
                                 class="thumbnail active"
                                 onclick="changeImage(this.src)">
                            <?php if (isset($product['images']) && is_array($product['images'])): ?>
                                <?php foreach ($product['images'] as $image): ?>
                                    <img src="<?php echo htmlspecialchars($image); ?>" 
                                         alt="Thumbnail"
                                         class="thumbnail"
                                         onclick="changeImage(this.src)">
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
    </div>
    
                <!-- Product Info -->
                <div class="col-md-6">
    <div class="product-info">
                        <div class="product-category">
                            <?php echo htmlspecialchars(ucfirst($product['category'])); ?>
                        </div>
                        <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                        
                        <?php
                        $stock = $product['stock'] ?? 100;
                        $stockClass = $stock > 20 ? 'in-stock' : ($stock > 0 ? 'low-stock' : 'out-of-stock');
                        $stockText = $stock > 20 ? 'In Stock' : ($stock > 0 ? 'Low Stock' : 'Out of Stock');
                        ?>
                        <div class="stock-status <?php echo $stockClass; ?>">
                            <?php echo $stockText; ?>
                        </div>

                        <div class="product-price">
                            <?php echo formatPrice($product['price']); ?>
                        </div>

                        <div class="product-rating">
                            <div class="rating-stars">
                                <?php
                                $rating = isset($product['rating']['rate']) ? $product['rating']['rate'] : 4.5;
                                $fullStars = floor($rating);
                                $hasHalfStar = $rating - $fullStars >= 0.5;
                                
                                for ($i = 0; $i < $fullStars; $i++) {
                                    echo '<i class="fas fa-star"></i>';
                                }
                                if ($hasHalfStar) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                }
                                $emptyStars = 5 - ceil($rating);
                                for ($i = 0; $i < $emptyStars; $i++) {
                                    echo '<i class="far fa-star"></i>';
                                }
                                ?>
                            </div>
                            <span class="rating-count">
                                (<?php echo isset($product['rating']['count']) ? $product['rating']['count'] : rand(10, 100); ?> reviews)
                            </span>
                        </div>

                        <div class="product-description">
                            <?php echo htmlspecialchars($product['description']); ?>
                        </div>

                        <div class="product-meta">
                            <div class="meta-item">
                                <i class="fas fa-truck meta-icon"></i>
                                <span class="meta-text">Free Shipping</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-undo meta-icon"></i>
                                <span class="meta-text">30-Day Returns</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-shield-alt meta-icon"></i>
                                <span class="meta-text">2-Year Warranty</span>
                            </div>
                        </div>

                        <div class="quantity-selector">
                            <button class="quantity-btn" onclick="updateQuantity(-1)">-</button>
                            <input type="number" class="quantity-input" id="quantity" value="1" min="1">
                            <button class="quantity-btn" onclick="updateQuantity(1)">+</button>
                        </div>

                        <div class="product-actions">
                            <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                            <button class="btn-wishlist" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>

                        <div class="product-features">
                            <div class="features-grid">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="feature-text">Premium Quality</div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-sync"></i>
                                    </div>
                                    <div class="feature-text">Easy Returns</div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-truck"></i>
                                    </div>
                                    <div class="feature-text">Fast Delivery</div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-headset"></i>
                                    </div>
                                    <div class="feature-text">24/7 Support</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2 class="related-title">Customer Reviews</h2>
            <div class="review-card">
                <div class="review-header">
                    <div class="reviewer-name">John Doe</div>
                    <div class="review-date">March 15, 2024</div>
                </div>
                <div class="review-rating">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <div class="review-text">
                    Excellent product! The quality is outstanding and it exceeded my expectations. 
                    The delivery was fast and the packaging was secure. Highly recommended!
                </div>
            </div>
            <!-- Add more review cards as needed -->
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
        <div class="related-products">
            <h2 class="related-title">You May Also Like</h2>
            <div class="related-grid">
                <?php foreach ($related_products as $related): ?>
                    <div class="related-card">
                        <img src="<?php echo htmlspecialchars($related['image_url'] ?? $related['image']); ?>" 
                             alt="<?php echo htmlspecialchars($related['title']); ?>"
                             class="related-image">
                        <div class="related-info">
                            <h3 class="related-name"><?php echo htmlspecialchars($related['title']); ?></h3>
                            <p class="related-price"><?php echo formatPrice($related['price']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function changeImage(src) {
    document.getElementById('mainImage').src = src;
    // Update active thumbnail
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
        if (thumb.src === src) {
            thumb.classList.add('active');
        }
    });
}

function updateQuantity(change) {
    const input = document.getElementById('quantity');
    const newValue = parseInt(input.value) + change;
    if (newValue >= 1) {
        input.value = newValue;
    }
}

function addToCart(productId) {
    const quantity = parseInt(document.getElementById('quantity').value);
    fetch('/api/cart/add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Product added to cart!');
            updateCartCount(data.cartCount);
        } else {
            alert(data.message || 'Error adding product to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding product to cart');
    });
}

function addToWishlist(productId) {
    fetch('/api/wishlist/add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Product added to wishlist!');
        } else {
            alert(data.message || 'Error adding product to wishlist');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding product to wishlist');
    });
}

function updateCartCount(count) {
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        cartCountElement.style.display = count > 0 ? 'inline' : 'none';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?> 