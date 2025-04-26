<?php
return [
    'client_id' => 'YOUR_PAYPAL_CLIENT_ID',
    'client_secret' => 'YOUR_PAYPAL_CLIENT_SECRET',
    'mode' => 'sandbox', // Change to 'live' for production
    'currency' => 'USD',
    'return_url' => 'http://localhost/ecommerce/pages/checkout-success.php',
    'cancel_url' => 'http://localhost/ecommerce/pages/checkout.php'
]; 