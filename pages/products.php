<?php
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/Cart.php';

// Check if user is logged in (needed for cart functionality check)
$isUserLoggedIn = isLoggedIn();

$db = new Database();
$conn = $db->getConnection();

// Get categories from database
$stmt = $conn->query("SELECT DISTINCT c.name FROM category c JOIN products p ON c.id = p.category_id");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get filter parameters with default values
$selected_category = $_GET['category'] ?? null;
$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';
$price_range = $_GET['price_range'] ?? 'all';
$rating_filter = $_GET['rating'] ?? 'all';

// Get products from database
$products = $selected_category
    ? getProductsByCategory($conn, $selected_category)
    : getAllProducts($conn);

// Apply filters
if ($search_query) {
    $products = array_filter($products, function($product) use ($search_query) {
        return stripos($product['title'] ?? '', $search_query) !== false ||
               stripos($product['description'] ?? '', $search_query) !== false;
    });
}

// Apply price range filter
if ($price_range !== 'all') {
    list($min, $max) = explode('-', $price_range);
    $products = array_filter($products, function($product) use ($min, $max) {
        return ($product['price'] ?? 0) >= $min && ($product['price'] ?? 0) <= $max;
    });
}

// Sort products
switch ($sort_by) {
    case 'price_low':
        usort($products, function($a, $b) {
            return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
        });
        break;
    case 'price_high':
        usort($products, function($a, $b) {
            return ($b['price'] ?? 0) <=> ($a['price'] ?? 0);
        });
        break;
    case 'newest':
    default:
        usort($products, function($a, $b) {
            return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
        });
        break;
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'] ?? 1;
    
    $cart = new Cart();
    if ($cart->addToCart($_SESSION['user_id'], $productId, $quantity)) {
        $_SESSION['success_message'] = "Product added to cart successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to add product to cart.";
    }
    
    // Redirect to prevent form resubmission
    header('Location: products.php' . ($selected_category ? "?category=$selected_category" : '') . ($search_query ? "&search=$search_query" : ''));
    exit();
}
?>

<style>
    .products-header {
        margin-bottom: 2rem;
        padding: 2rem 0;
        background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        color: white;
    }

    .filters-container {
        max-width: 800px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.1);
        padding: 1.5rem;
        border-radius: 10px;
    }

    .filters-form {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .form-group {
        flex: 1;
        min-width: 200px;
    }

    .search-input, .category-select {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 4px;
        font-size: 1rem;
        background: rgba(255, 255, 255, 0.9);
        transition: all 0.3s ease;
    }

    .search-input:focus, .category-select:focus {
        outline: none;
        border-color: var(--accent-color);
        box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2);
    }

    .alert {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }

    .alert-warning {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 2rem;
        padding: 2rem 0;
    }

    .product-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .product-image-container {
        position: relative;
        padding-top: 75%; /* 4:3 Aspect Ratio */
        overflow: hidden;
        background: #f8f9fa;
    }

    .product-image {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .product-card:hover .product-image {
        transform: scale(1.1);
    }

    .product-info {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .product-category {
        font-size: 0.85rem;
        color: var(--secondary-color);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0.5rem;
    }

    .product-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--primary-color);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 2.8em;
    }

    .product-title-link {
        color: inherit; /* Inherit color from parent h3 */
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .product-title-link:hover {
        color: var(--secondary-color);
    }

    .product-price {
        font-size: 1.3rem;
        font-weight: bold;
        color: var(--accent-color);
        margin: 0.5rem 0;
    }

    .product-rating {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
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

    .product-actions {
        display: flex;
        gap: 1rem;
        margin-top: auto;
    }

    .btn-view-details {
        flex: 1;
        padding: 0.8rem;
        border: none;
        border-radius: 5px;
        background: var(--primary-color);
        color: white;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-view-details:hover {
        background: var(--secondary-color);
        transform: translateY(-2px);
    }

    .btn-add-cart {
        padding: 0.8rem;
        border: none;
        border-radius: 5px;
        background: var(--accent-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        transition: all 0.3s ease;
    }

    .btn-add-cart:hover {
        background: #c0392b;
        transform: translateY(-2px);
    }

    .stock-status {
        position: absolute;
        top: 1rem;
        right: 1rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        z-index: 1;
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

    /* New styles for features */
    .features-section {
        background: #f8f9fa;
        padding: 2rem 0;
        margin-bottom: 2rem;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }

    .feature-card {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-5px);
    }

    .feature-icon {
        font-size: 2.5rem;
        color: var(--secondary-color);
        margin-bottom: 1rem;
    }

    .feature-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--primary-color);
    }

    .feature-description {
        color: #666;
        font-size: 0.9rem;
    }

    /* Quick View Modal Styles */
    .quick-view-modal .modal-content {
        border-radius: 15px;
        overflow: hidden;
    }

    .quick-view-image {
        width: 100%;
        height: 300px;
        object-fit: cover;
    }

    .quick-view-details {
        padding: 1.5rem;
    }

    .quick-view-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .quick-view-price {
        font-size: 1.3rem;
        color: var(--accent-color);
        font-weight: bold;
        margin-bottom: 1rem;
    }

    .quick-view-description {
        color: #666;
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }

    .quantity-selector {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .quantity-btn {
        width: 30px;
        height: 30px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .quantity-btn:hover {
        background: var(--secondary-color);
        color: white;
        border-color: var(--secondary-color);
    }

    .quantity-input {
        width: 50px;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 0.5rem;
    }

    /* Filter Section Styles */
    .filter-section {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .filter-group {
        margin-bottom: 1.5rem;
    }

    .filter-title {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--primary-color);
    }

    .price-range-inputs {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .price-input {
        width: 100px;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .rating-options {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .rating-option {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }

    .rating-option input[type="radio"] {
        margin: 0;
    }

    .rating-stars {
        color: #f1c40f;
    }

    .apply-filters {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 0.8rem 1.5rem;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .apply-filters:hover {
        background: var(--secondary-color);
    }
</style>

<div class="container">
    <!-- Features Section -->
    <section class="features-section">
        <h2 class="text-center mb-4">Why Choose Us</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3 class="feature-title">Free Shipping</h3>
                <p class="feature-description">Free shipping on all orders over $50</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <h3 class="feature-title">Easy Returns</h3>
                <p class="feature-description">30-day money back guarantee</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h3 class="feature-title">Secure Payment</h3>
                <p class="feature-description">100% secure payment processing</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="feature-title">24/7 Support</h3>
                <p class="feature-description">Dedicated support team</p>
            </div>
        </div>
    </section>

    <section class="products-header">
        <h1 class="text-center mb-4">Our Products</h1>
        
        <div class="filters-container">
            <form method="GET" action="" class="filters-form">
                <div class="form-group">
                    <input type="text" 
                           name="search" 
                           placeholder="Search products..." 
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           class="search-input">
                </div>
                
                <div class="form-group">
                    <select name="category" class="category-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" 
                                    <?php echo $selected_category === $category ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($category)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <select name="sort" class="category-select">
                        <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="rating" <?php echo $sort_by === 'rating' ? 'selected' : ''; ?>>Top Rated</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
    </section>

    <!-- Advanced Filters Section -->
    <div class="filter-section">
        <form method="GET" action="" class="advanced-filters">
            <div class="row">
                <div class="col-md-4">
                    <div class="filter-group">
                        <h4 class="filter-title">Price Range</h4>
                        <select name="price_range" class="form-control">
                            <option value="all" <?php echo $price_range === 'all' ? 'selected' : ''; ?>>All Prices</option>
                            <option value="0-50" <?php echo $price_range === '0-50' ? 'selected' : ''; ?>>Under $50</option>
                            <option value="50-100" <?php echo $price_range === '50-100' ? 'selected' : ''; ?>>$50 - $100</option>
                            <option value="100-200" <?php echo $price_range === '100-200' ? 'selected' : ''; ?>>$100 - $200</option>
                            <option value="200-1000" <?php echo $price_range === '200-1000' ? 'selected' : ''; ?>>$200+</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="filter-group">
                        <h4 class="filter-title">Rating</h4>
                        <div class="rating-options">
                            <label class="rating-option">
                                <input type="radio" name="rating" value="all" <?php echo $rating_filter === 'all' ? 'checked' : ''; ?>>
                                All Ratings
                            </label>
                            <label class="rating-option">
                                <input type="radio" name="rating" value="4" <?php echo $rating_filter === '4' ? 'checked' : ''; ?>>
                                <div class="rating-stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                & Up
                            </label>
                            <label class="rating-option">
                                <input type="radio" name="rating" value="3" <?php echo $rating_filter === '3' ? 'checked' : ''; ?>>
                                <div class="rating-stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                & Up
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="filter-group">
                        <h4 class="filter-title">Quick Actions</h4>
                        <button type="submit" class="apply-filters">Apply Filters</button>
                        <a href="products.php" class="btn btn-secondary">Reset Filters</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <section class="products-grid-container">
        <?php if (empty($products)): ?>
            <div class="alert alert-warning text-center">
                No products found matching your criteria.
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <?php
                        $stock = $product['stock'] ?? 0;
                        $stockClass = $stock > 20 ? 'in-stock' : ($stock > 0 ? 'low-stock' : 'out-of-stock');
                        $stockText = $stock > 20 ? 'In Stock' : ($stock > 0 ? 'Low Stock' : 'Out of Stock');
                        ?>
                        <div class="stock-status <?php echo $stockClass; ?>">
                            <?php echo $stockText; ?>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/pages/product.php?id=<?php echo $product['id']; ?>" class="product-image-container">
                            <img src="<?php echo BASE_URL . '/' . htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                 class="product-image">
                        </a>
                        <div class="product-info">
                            <span class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                            <h3 class="product-title">
                                <a href="<?php echo BASE_URL; ?>/pages/product.php?id=<?php echo $product['id']; ?>" class="product-title-link">
                                    <?php echo htmlspecialchars($product['title']); ?>
                                </a>
                            </h3>
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
                            <div class="product-actions">
                                <button class="btn-view-details" onclick="quickView(<?php echo htmlspecialchars(json_encode($product)); ?>)">Quick View</button>
                                <?php if ($stock > 0): ?>
                                    <form method="POST" class="add-to-cart-form">
                                        <input type="hidden" name="add_to_cart" value="1">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <div class="quantity-input">
                                            <input type="number" name="quantity" value="1" min="1" class="form-control">
                                        </div>
                                        <button type="submit" class="btn btn-primary add-to-cart-btn">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <img src="" alt="" class="quick-view-image" id="quickViewImage">
                    </div>
                    <div class="col-md-6">
                        <div class="quick-view-details">
                            <h2 class="quick-view-title" id="quickViewTitle"></h2>
                            <p class="quick-view-price" id="quickViewPrice"></p>
                            <div class="product-rating" id="quickViewRating"></div>
                            <p class="quick-view-description" id="quickViewDescription"></p>
                            <div class="quantity-selector">
                                <button class="quantity-btn" onclick="updateQuantity(-1)">-</button>
                                <input type="number" class="quantity-input" id="quantity" value="1" min="1" max="10">
                                <button class="quantity-btn" onclick="updateQuantity(1)">+</button>
                            </div>
                            <button class="btn btn-primary" onclick="addToCartFromQuickView()">Add to Cart</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
// Function to handle adding items to cart
function addToCart(productId) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1); // Default quantity
    
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
}

// Function to handle quick view
function quickView(product) {
    // Update modal content
    document.getElementById('quickViewImage').src = product.image;
    document.getElementById('quickViewTitle').textContent = product.title;
    document.getElementById('quickViewPrice').textContent = formatPrice(product.price);
    document.getElementById('quickViewDescription').textContent = product.description;
    
    // Update rating stars
    const ratingContainer = document.getElementById('quickViewRating');
    ratingContainer.innerHTML = '';
    const rating = product.rating?.rate || 4.5;
    const fullStars = Math.floor(rating);
    const halfStar = rating - fullStars >= 0.5;
    
    for (let i = 0; i < 5; i++) {
        const star = document.createElement('i');
        if (i < fullStars) {
            star.className = 'fas fa-star';
        } else if (i === fullStars && halfStar) {
            star.className = 'fas fa-star-half-alt';
        } else {
            star.className = 'far fa-star';
        }
        ratingContainer.appendChild(star);
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
    modal.show();
}

// Function to update quantity in quick view
function updateQuantity(change) {
    const input = document.getElementById('quantity');
    const newValue = parseInt(input.value) + change;
    if (newValue >= 1 && newValue <= 10) {
        input.value = newValue;
    }
}

// Function to add to cart from quick view
function addToCartFromQuickView() {
    const productId = document.getElementById('quickViewModal').dataset.productId;
    const quantity = parseInt(document.getElementById('quantity').value);
    addToCart(productId, quantity);
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('quickViewModal'));
    modal.hide();
}
</script>
</body>
</html> 