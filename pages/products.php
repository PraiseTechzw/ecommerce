<?php
require_once '../includes/header.php';
require_once '../api/fakestore.php';
require_once '../config/database.php';

// Check if user is logged in (needed for cart functionality check)
$isUserLoggedIn = isLoggedIn();

$api = new FakeStoreAPI();
$db = new Database();
$conn = $db->getConnection();

// Get categories from both API and database
$api_categories = $api->getCategories();
$db_categories = $conn->query("SELECT DISTINCT category FROM products")->fetchAll(PDO::FETCH_COLUMN);
$categories = array_unique(array_merge($api_categories, $db_categories));

// Get filter parameters
$selected_category = isset($_GET['category']) ? $_GET['category'] : null;
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$price_range = isset($_GET['price_range']) ? $_GET['price_range'] : 'all';
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : 'all';

// Get products from both sources
$api_products = $selected_category 
    ? $api->getProductsByCategory($selected_category)
    : $api->getProducts();

$db_products = $selected_category
    ? getProductsByCategory($conn, $selected_category)
    : getAllProducts($conn);

// Combine products from both sources
$products = array_merge($api_products, $db_products);

// Apply filters
if ($search_query) {
    $products = array_filter($products, function($product) use ($search_query) {
        return stripos($product['title'], $search_query) !== false ||
               stripos($product['description'], $search_query) !== false;
    });
}

// Apply price range filter
if ($price_range !== 'all') {
    list($min, $max) = explode('-', $price_range);
    $products = array_filter($products, function($product) use ($min, $max) {
        return $product['price'] >= $min && $product['price'] <= $max;
    });
}

// Apply rating filter
if ($rating_filter !== 'all') {
    $products = array_filter($products, function($product) use ($rating_filter) {
        $rating = isset($product['rating']['rate']) ? $product['rating']['rate'] : 4.5;
        return $rating >= $rating_filter;
    });
}

// Sort products
switch ($sort_by) {
    case 'price_low':
        usort($products, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });
        break;
    case 'price_high':
        usort($products, function($a, $b) {
            return $b['price'] <=> $a['price'];
        });
        break;
    case 'rating':
        usort($products, function($a, $b) {
            $rating_a = isset($a['rating']['rate']) ? $a['rating']['rate'] : 4.5;
            $rating_b = isset($b['rating']['rate']) ? $b['rating']['rate'] : 4.5;
            return $rating_b <=> $rating_a;
        });
        break;
    case 'newest':
    default:
        usort($products, function($a, $b) {
            return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
        });
        break;
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
                        $stock = $product['stock'] ?? 100;
                        $stockClass = $stock > 20 ? 'in-stock' : ($stock > 0 ? 'low-stock' : 'out-of-stock');
                        $stockText = $stock > 20 ? 'In Stock' : ($stock > 0 ? 'Low Stock' : 'Out of Stock');
                        ?>
                        <div class="stock-status <?php echo $stockClass; ?>">
                            <?php echo $stockText; ?>
                        </div>
                        <div class="product-image-container">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?? $product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['title']); ?>"
                             class="product-image">
                        </div>
                        <div class="product-info">
                            <div class="product-category">
                                <?php echo htmlspecialchars(ucfirst($product['category'])); ?>
                            </div>
                            <h3 class="product-title">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-title-link">
                                    <?php echo htmlspecialchars($product['title']); ?>
                                </a>
                            </h3>
                            <p class="product-price"><?php echo formatPrice($product['price']); ?></p>
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
                            <div class="product-actions">
                                <button class="btn-view-details" onclick="quickView(<?php echo htmlspecialchars(json_encode($product)); ?>)">Quick View</button>
                                <?php if ($stock > 0): ?>
                                    <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
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
                                <input type="number" class="quantity-input" id="quantity" value="1" min="1">
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

<!-- Add the script.js file -->
<script src="/public/js/script.js"></script>
</body>
</html> 