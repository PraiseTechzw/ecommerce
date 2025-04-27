<?php
// Start session and check admin access first
session_start();
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Debug session data
echo "<pre>";
echo "Session data:\n";
print_r($_SESSION);
echo "\nChecking admin status...\n";
echo "isAdmin() result: " . (isAdmin() ? 'true' : 'false') . "\n";
echo "Session role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'not set') . "\n";
echo "</pre>";

// Check if user is admin before any output
if (!isAdmin()) {
    echo "<p>Redirecting to login page...</p>";
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit;
}

// Now include other files
require_once '../../includes/header.php';
require_once '../../config/database.php';
require_once '../../api/fakestore.php';

$db = new Database();
$conn = $db->getConnection();
$api = new FakeStoreAPI();

// Handle import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    try {
        // Get all products from FakeStore API
        $products = $api->getAllProducts();
        $imported = 0;
        $errors = [];

        foreach ($products as $product) {
            // Check if product already exists
            $stmt = $conn->prepare("SELECT id FROM products WHERE title = ? AND is_api_product = 1");
            $stmt->execute([$product['title']]);
            if ($stmt->fetch()) {
                continue; // Skip if product already exists
            }

            // Download and save image
            $image_url = $product['image'];
            $image_name = basename($image_url);
            $local_image_path = '/public/images/products/' . $image_name;
            $full_image_path = __DIR__ . '/../../' . $local_image_path;
            
            // Create directory if it doesn't exist
            if (!file_exists(dirname($full_image_path))) {
                mkdir(dirname($full_image_path), 0777, true);
            }
            
            // Download image
            file_put_contents($full_image_path, file_get_contents($image_url));

            // Insert product into database
            $stmt = $conn->prepare("
                INSERT INTO products (title, description, price, category, image_url, is_api_product, rating)
                VALUES (?, ?, ?, ?, ?, 1, ?)
            ");
            
            $result = $stmt->execute([
                $product['title'],
                $product['description'],
                $product['price'],
                $product['category'],
                $local_image_path,
                $product['rating']['rate']
            ]);

            if ($result) {
                $imported++;
            } else {
                $errors[] = "Failed to import: " . $product['title'];
            }
        }

        if ($imported > 0) {
            $_SESSION['message'] = "Successfully imported $imported products";
            $_SESSION['message_type'] = 'success';
        }
        if (!empty($errors)) {
            $_SESSION['message'] .= "\nErrors: " . implode(", ", $errors);
            $_SESSION['message_type'] = 'warning';
        }
    } catch (Exception $e) {
        $_SESSION['message'] = 'Error importing products: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    
    // Redirect to refresh the page
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get current products count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE is_api_product = 1");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$imported_count = $result['count'];
?>

<style>
.import-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stats {
    margin: 2rem 0;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.import-btn {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 0.8rem 2rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.import-btn:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
}

.import-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}
</style>

<div class="import-container">
    <h1>Import Products from FakeStore API</h1>
    
    <div class="stats">
        <p>Currently imported API products: <?php echo $imported_count; ?></p>
    </div>

    <form method="POST" id="importForm">
        <button type="submit" name="import" class="import-btn" id="importBtn">
            Import Products
        </button>
    </form>
</div>

<script>
document.getElementById('importForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('importBtn');
    btn.disabled = true;
    btn.textContent = 'Importing...';
});
</script>

<?php require_once '../../includes/footer.php'; ?> 