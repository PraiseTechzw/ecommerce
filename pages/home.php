<?php
require_once '../includes/header.php';
require_once '../api/fakestore.php';
require_once '../config/database.php';

$api = new FakeStoreAPI();
$db = new Database();
$conn = $db->getConnection();

// Get 4 featured products from API
$featured_products = $api->getProducts(4);

// Get categories
$api_categories = $api->getCategories();
$stmt = $conn->query("SELECT DISTINCT c.name FROM category c JOIN products p ON c.id = p.category_id");
$db_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
$categories = array_unique(array_merge($api_categories, $db_categories));
?>

<style>
    /* Hero Section Styles */
    .hero-section {
        background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://source.unsplash.com/random/1600x900/?ecommerce,shopping') no-repeat center center;
        background-size: cover;
        color: white;
        padding: 6rem 0;
        text-align: center;
    }

    .hero-section h1 {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    }

    .hero-section p {
        font-size: 1.3rem;
        margin-bottom: 2.5rem;
    }

    .btn-hero {
        background: var(--secondary-color);
        color: white;
        padding: 1rem 2.5rem;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-hero:hover {
        background: var(--accent-color);
        transform: translateY(-3px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    /* Featured Products Section */
    .featured-products {
        padding: 3rem 0;
        background: #f8f9fa;
    }

    .section-title {
        font-size: 2.5rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 2.5rem;
        text-align: center;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 2rem;
    }

    /* Enhanced Product Card Styles (copied from products.php) */
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
        text-align: center;
        text-decoration: none;
    }

    .btn-view-details:hover {
        background: var(--secondary-color);
        transform: translateY(-2px);
    }

    /* Categories Section */
    .categories-section {
        padding: 3rem 0;
    }

    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
    }

    .category-card {
        background: white;
        border-radius: 10px;
        padding: 2.5rem 1.5rem;
        text-align: center;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        text-decoration: none;
        color: var(--primary-color);
    }

    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        background: var(--secondary-color);
        color: white;
    }

    .category-card:hover .category-icon {
        color: white;
    }

    .category-icon {
        font-size: 3rem;
        margin-bottom: 1.5rem;
        color: var(--secondary-color);
        transition: color 0.3s ease;
    }

    .category-title {
        font-size: 1.3rem;
        font-weight: 600;
    }

</style>

<div class="container">
    <section class="hero-section">
        <h1>Welcome to E-Store</h1>
        <p>Your one-stop shop for the best products online</p>
        <a href="products.php" class="btn-hero">Shop Now</a>
    </section>

    <section class="featured-products">
        <h2 class="section-title">Featured Products</h2>
        <div class="product-grid">
            <?php if (empty($featured_products)): ?>
                <p class="text-center">No featured products available right now.</p>
            <?php else: ?>
            <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                        <div class="product-image-container">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                         class="product-image">
                        </div>
                    <div class="product-info">
                            <div class="product-category">
                                <?php echo htmlspecialchars(ucfirst($product['category'])); ?>
                            </div>
                        <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
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
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-view-details">View Details</a>
                                <!-- Optional: Add quick view or add to cart button here if desired -->
                            </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="categories-section">
        <h2 class="section-title">Shop by Category</h2>
        <div class="category-grid">
            <?php foreach ($categories as $category): ?>
                <a href="products.php?category=<?php echo urlencode($category); ?>" class="category-card">
                    <div class="category-icon">
                        <?php 
                        // Assign icons based on category name
                        $icon = 'fas fa-tag'; // Default icon
                        if ($category === 'electronics') $icon = 'fas fa-laptop';
                        if ($category === 'jewelery') $icon = 'fas fa-gem';
                        if ($category === "men's clothing") $icon = 'fas fa-male';
                        if ($category === "women's clothing") $icon = 'fas fa-female';
                        ?>
                        <i class="<?php echo $icon; ?>"></i>
                    </div>
                    <h3 class="category-title"><?php echo htmlspecialchars(ucfirst($category)); ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?> 