<?php
require_once '../includes/header.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        header('Location: home.php');
        exit();
    } else {
        $error = "Invalid email or password";
    }
}
?>

<div class="container">
    <div class="auth-section">
        <div class="auth-card">
            <h1 class="text-center mb-4">Login</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="auth-links text-center mt-3">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<style>
    .auth-section {
        max-width: 400px;
        margin: 2rem auto;
    }
    
    .auth-card {
        background: white;
        border-radius: 10px;
        padding: 2rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .auth-form {
        margin-top: 2rem;
    }
    
    .auth-links {
        margin-top: 1rem;
    }
    
    .auth-links a {
        color: var(--secondary-color);
        text-decoration: none;
    }
    
    .auth-links a:hover {
        text-decoration: underline;
    }
</style>

<?php require_once '../includes/footer.php'; ?> 