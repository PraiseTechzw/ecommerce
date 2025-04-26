<?php
session_start();
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/config.php';

// Check if user is already logged in and redirect if needed
if (isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) === 'login.php') {
    header('Location: home.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo BASE_URL; ?>">
    <title>E-Commerce Store</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Add Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Add Bootstrap JS and its dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
    <!-- Add Toastify for notifications -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        :root {
            --primary-color: #1a1a2e;
            --secondary-color: #4a90e2;
            --accent-color: #e74c3c;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --header-height: 70px;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: var(--primary-color);
            padding: 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: var(--header-height);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
        }

        .logo {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            position: relative;
        }

        .nav-links a:hover {
            color: var(--secondary-color);
            background: rgba(255,255,255,0.1);
        }

        .nav-links a.active {
            color: var(--secondary-color);
            background: rgba(255,255,255,0.1);
        }

        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50%;
            height: 2px;
            background: var(--secondary-color);
        }

        .search-bar {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 20px;
            padding: 0.5rem 1rem;
            margin: 0 1rem;
            transition: box-shadow 0.3s ease;
        }

        .search-bar:focus-within {
            box-shadow: 0 0 0 2px var(--secondary-color);
        }

        .search-bar input {
            border: none;
            outline: none;
            background: none;
            width: 200px;
            font-size: 0.9rem;
        }

        .search-bar button {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .search-bar button:hover {
            color: var(--secondary-color);
        }

        .cart-icon {
            position: relative;
            color: white;
            font-size: 1.2rem;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--accent-color);
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .mobile-menu-btn:hover {
            transform: rotate(90deg);
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: var(--header-height);
                left: 0;
                right: 0;
                background: var(--primary-color);
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                transform: translateY(-100%);
                transition: transform 0.3s ease;
            }

            .nav-links.active {
                display: flex;
                transform: translateY(0);
            }

            .mobile-menu-btn {
                display: block;
            }

            .search-bar {
                display: none;
            }
        }

        /* Add styles for the welcome message */
        .user-welcome {
            color: white;
            padding: 0.5rem 1rem;
            margin-right: 1rem;
            border-radius: 4px;
            background: rgba(255,255,255,0.1);
        }

        .welcome-text {
            font-size: 0.9rem;
        }

        .welcome-text strong {
            color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .user-welcome {
                text-align: center;
                margin: 0.5rem 0;
            }
        }

        /* Add new styles for user status */
        .user-status {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
        }

        .user-status .welcome-text {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .auth-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .auth-buttons a {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-login {
            background: var(--secondary-color);
            color: white;
        }

        .btn-register {
            background: transparent;
            color: white;
            border: 1px solid var(--secondary-color);
        }

        .btn-login:hover, .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <header>
        <div class="nav-container">
            <a href="<?php echo BASE_URL; ?>/" class="logo">E-Commerce Store</a>
            
            <div class="search-bar">
                <input type="text" placeholder="Search products..." name="search">
                <button type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <nav>
                <ul class="nav-links">
                    <li><a href="<?php echo BASE_URL; ?>/pages/home.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">Products</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'active' : ''; ?>">Admin</a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="user-status">
                <?php if (isLoggedIn() && isset($_SESSION['username'])): ?>
                    <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="<?php echo BASE_URL; ?>/pages/cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="headerCartCount"><?php echo getCartCount(); ?></span>
                    </a>
                    <div class="auth-buttons">
                        <a href="<?php echo BASE_URL; ?>/pages/profile.php" class="btn-login">Profile</a>
                        <a href="<?php echo BASE_URL; ?>/pages/logout.php" class="btn-register">Logout</a>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="<?php echo BASE_URL; ?>/pages/login.php" class="btn-login">Login</a>
                        <a href="<?php echo BASE_URL; ?>/pages/register.php" class="btn-register">Register</a>
                    </div>
                <?php endif; ?>
            </div>

            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>
    <main class="container">
        <?php echo displayMessage(); ?>
    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('active');
        });
    </script> 