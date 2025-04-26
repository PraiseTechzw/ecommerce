<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isAdmin()) {
    redirect('../index.php', 'You do not have permission to access this page.', 'error');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $price = floatval($_POST['price']);
                $category = sanitize($_POST['category']);
                $stock = intval($_POST['stock']);

                // Handle image upload
                $image = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = '../uploads/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $image = $upload_dir . time() . '_' . basename($_FILES['image']['name']);
                    move_uploaded_file($_FILES['image']['tmp_name'], $image);
                    $image = str_replace('../', '', $image); // Store relative path
                }

                $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $category, $stock, $image]);
                redirect('products.php', 'Product added successfully!', 'success');
                break;

            case 'edit':
                $id = intval($_POST['id']);
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $price = floatval($_POST['price']);
                $category = sanitize($_POST['category']);
                $stock = intval($_POST['stock']);

                // Handle image upload for edit
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = '../uploads/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $image = $upload_dir . time() . '_' . basename($_FILES['image']['name']);
                    move_uploaded_file($_FILES['image']['tmp_name'], $image);
                    $image = str_replace('../', '', $image);
                    
                    $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, category=?, stock=?, image=? WHERE id=?");
                    $stmt->execute([$name, $description, $price, $category, $stock, $image, $id]);
                } else {
                    $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, category=?, stock=? WHERE id=?");
                    $stmt->execute([$name, $description, $price, $category, $stock, $id]);
                }
                redirect('products.php', 'Product updated successfully!', 'success');
                break;

            case 'delete':
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
                $stmt->execute([$id]);
                redirect('products.php', 'Product deleted successfully!', 'success');
                break;
        }
    }
}

// Get all products from database
$stmt = $conn->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique categories
$stmt = $conn->query("SELECT DISTINCT category FROM products");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Product Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Add Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <h1>Product Management</h1>
        <?php echo displayMessage(); ?>

        <!-- Add Product Button -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
            Add New Product
        </button>

        <!-- Products Table -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td>
                            <?php if ($product['image']): ?>
                                <img src="../<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                                <img src="../assets/images/no-image.jpg" alt="No Image" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td><?php echo formatPrice($product['price']); ?></td>
                        <td><?php echo $product['stock']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">Edit</button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Product Modal -->
        <div class="modal fade" id="addProductModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <input type="text" class="form-control" id="category" name="category" list="categories" required>
                                <datalist id="categories">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" required>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Product Modal -->
        <div class="modal fade" id="editProductModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" id="edit_id">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="edit_price" class="form-label">Price</label>
                                <input type="number" class="form-control" id="edit_price" name="price" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_category" class="form-label">Category</label>
                                <input type="text" class="form-control" id="edit_category" name="category" list="categories" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="edit_stock" name="stock" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_image" class="form-label">Image</label>
                                <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                                <div id="current_image" class="mt-2"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</div>

    <!-- Add Bootstrap JS and its dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
    
    <script>
    function editProduct(product) {
        document.getElementById('edit_id').value = product.id;
        document.getElementById('edit_name').value = product.name;
        document.getElementById('edit_description').value = product.description;
        document.getElementById('edit_price').value = product.price;
        document.getElementById('edit_category').value = product.category;
        document.getElementById('edit_stock').value = product.stock;
        
        const currentImageDiv = document.getElementById('current_image');
        if (product.image) {
            currentImageDiv.innerHTML = `<img src="../${product.image}" alt="${product.name}" style="max-width: 100px;">`;
        } else {
            currentImageDiv.innerHTML = '<p>No image currently set</p>';
        }
        
        const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
        editModal.show();
    }
    </script>
</body>
</html> 