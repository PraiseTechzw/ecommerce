<?php
require_once '../../includes/header_logic.php';

if (!isAdmin()) {
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit;
}

require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

function handleImageUpload($file, $existingImage = null) {
    $targetDir = "../../uploads/products/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($file["name"]);
    $targetFile = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        throw new Exception("File is not an image.");
    }

    // Check file size
    if ($file["size"] > 5000000) {
        throw new Exception("Sorry, your file is too large.");
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        throw new Exception("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
    }

    // Generate unique filename
    $fileName = uniqid() . '.' . $imageFileType;
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        // Delete old image if exists
        if ($existingImage && file_exists($existingImage)) {
            unlink($existingImage);
        }
        return "uploads/products/" . $fileName;
    } else {
        throw new Exception("Sorry, there was an error uploading your file.");
    }
}

try {
    if (isset($_POST['add_product'])) {
        // Handle new product
        $imageUrl = handleImageUpload($_FILES["image"]);
        
        $stmt = $conn->prepare("
            INSERT INTO products (title, description, price, stock, category_id, image_url) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['price'],
            $_POST['stock'],
            $_POST['category_id'] ?: null,
            $imageUrl
        ]);
        
        header('Location: products.php?message=Product added successfully');
        exit;
    }
    
    if (isset($_POST['edit_product'])) {
        $productId = $_POST['product_id'];
        
        // Get current product data
        $stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $currentProduct = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Handle image upload if new image is provided
        $imageUrl = $currentProduct['image_url'];
        if (!empty($_FILES["image"]["name"])) {
            $imageUrl = handleImageUpload($_FILES["image"], "../../" . $currentProduct['image_url']);
        }
        
        $stmt = $conn->prepare("
            UPDATE products 
            SET title = ?, description = ?, price = ?, stock = ?, category_id = ?, image_url = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['price'],
            $_POST['stock'],
            $_POST['category_id'] ?: null,
            $imageUrl,
            $productId
        ]);
        
        header('Location: products.php?message=Product updated successfully');
        exit;
    }
} catch (Exception $e) {
    header('Location: products.php?error=' . urlencode($e->getMessage()));
    exit;
} 