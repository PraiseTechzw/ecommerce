# E-Commerce Platform

A modern e-commerce platform built with PHP, featuring user authentication, product management, shopping cart functionality, and PayPal integration.

## Features

- User Authentication (Registration & Login)
- Product Management
- Shopping Cart System
- Order Processing
- PayPal Payment Integration
- Admin Dashboard
- Responsive Design

## Project Structure

```
├── admin/           # Admin dashboard and management interfaces
├── api/            # API endpoints for frontend communication
├── config/         # Configuration files and database schema
│   ├── config.php
│   ├── database.php
│   ├── paypal.php
│   └── schema.sql
├── includes/       # Shared PHP components and utilities
├── pages/          # Main application pages
├── public/         # Publicly accessible assets (images, CSS, JS)
├── vendor/         # Composer dependencies
├── composer.json   # PHP dependencies configuration
└── index.php       # Application entry point
```

## Database Schema

The application uses a MySQL database with the following main tables:
- `users`: User accounts and authentication
- `orders`: Order management and tracking
- `categories`: Product categorization
- `cart_items`: Shopping cart functionality

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- PayPal API credentials
- Web server (Apache/Nginx)

## Installation

1. Clone the repository:
   ```bash
   git clone [repository-url]
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Configure the database:
   - Import the schema from `config/schema.sql`
   - Update database credentials in `config/database.php`

4. Configure PayPal:
   - Update PayPal credentials in `config/paypal.php`

5. Set up your web server to point to the project directory

## Configuration

1. Database Configuration:
   - Edit `config/database.php` with your database credentials

2. PayPal Configuration:
   - Edit `config/paypal.php` with your PayPal API credentials

## Security Features

- Password hashing
- SQL injection prevention
- XSS protection
- CSRF protection
- Secure session management

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details. 