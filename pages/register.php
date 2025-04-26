<?php
require_once '../includes/header.php';
require_once '../config/database.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Enhanced validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters long";
    if (!preg_match("/[A-Z]/", $password)) $errors[] = "Password must contain at least one uppercase letter";
    if (!preg_match("/[0-9]/", $password)) $errors[] = "Password must contain at least one number";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";

    if (empty($errors)) {
        $db = new Database();
        $conn = $db->getConnection();

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email already registered";
        } else {
            // Create new user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $password_hash]);

            // Log in the new user
            $_SESSION['user_id'] = $conn->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = 'user';

            header('Location: home.php');
            exit();
        }
    }
}
?>

<link rel="stylesheet" href="/public/css/auth.css">

<div class="auth-container">
    <div class="auth-header">
        <h1>Create Account</h1>
        <p>Join our community today</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error-message">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="auth-form" id="registerForm">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <div class="password-strength">
                <div class="password-strength-bar" id="passwordStrength"></div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="btn">Create Account</button>
    </form>

    <div class="social-login">
        <p>Or sign up with</p>
        <div class="social-buttons">
            <button type="button" class="social-btn">
                <i class="fab fa-google"></i> Google
            </button>
            <button type="button" class="social-btn">
                <i class="fab fa-facebook"></i> Facebook
            </button>
        </div>
    </div>

    <p class="auth-link">Already have an account? <a href="login.php">Login here</a></p>
</div>

<script>
document.getElementById('password').addEventListener('input', function(e) {
    const password = e.target.value;
    const strengthBar = document.getElementById('passwordStrength');
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^A-Za-z0-9]/)) strength++;
    
    strengthBar.className = 'password-strength-bar';
    if (strength <= 1) {
        strengthBar.classList.add('password-strength-weak');
    } else if (strength <= 3) {
        strengthBar.classList.add('password-strength-medium');
    } else {
        strengthBar.classList.add('password-strength-strong');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 