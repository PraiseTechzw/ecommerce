<?php
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'admin'");
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<pre>";
if ($admin) {
    echo "Admin user found:\n";
    print_r($admin);
} else {
    echo "No admin user found in database.\n";
    echo "Running admin user creation...\n";
    
    // Create admin user
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password, role) 
        VALUES ('admin', 'admin@example.com', ?, 'admin')
    ");
    $hashed_password = password_hash('password', PASSWORD_DEFAULT);
    $result = $stmt->execute([$hashed_password]);
    
    if ($result) {
        echo "Admin user created successfully!\n";
        echo "You can now login with:\n";
        echo "Email: admin@example.com\n";
        echo "Password: password\n";
    } else {
        echo "Failed to create admin user.\n";
        print_r($stmt->errorInfo());
    }
}
echo "</pre>"; 