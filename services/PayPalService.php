<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

class PayPalService {
    private $client;
    private $config;

    public function __construct() {
        // Set error logging to a specific file
        ini_set('error_log', __DIR__ . '/../logs/paypal_errors.log');
        
        $this->config = require __DIR__ . '/../config/paypal.php';
        
        // Verify PayPal credentials
        if (empty($this->config['client_id']) || empty($this->config['client_secret'])) {
            throw new Exception('PayPal credentials are not configured properly');
        }
        
        error_log("PayPal Config loaded: " . print_r([
            'client_id' => substr($this->config['client_id'], 0, 10) . '...',
            'mode' => $this->config['mode'],
            'currency' => $this->config['currency']
        ], true));
        
        try {
            $environment = new SandboxEnvironment($this->config['client_id'], $this->config['client_secret']);
            $this->client = new PayPalHttpClient($environment);
            error_log("PayPal client initialized successfully");
        } catch (Exception $e) {
            error_log("Failed to initialize PayPal client: " . $e->getMessage());
            throw $e;
        }
    }

    public function createOrder($items, $total) {
        error_log("Creating PayPal order with items: " . print_r($items, true) . " and total: " . $total);
        
        if (empty($items)) {
            error_log("No items in cart");
            throw new Exception("Cart is empty");
        }
        
        if ($total <= 0) {
            error_log("Invalid total amount: " . $total);
            throw new Exception("Invalid total amount");
        }
        
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        
        // Calculate item total
        $itemTotal = 0;
        foreach ($items as $item) {
            $itemTotal += $item['price'] * $item['quantity'];
        }
        
        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $this->config['currency'],
                    'value' => number_format($total, 2, '.', ''),
                    'breakdown' => [
                        'item_total' => [
                            'currency_code' => $this->config['currency'],
                            'value' => number_format($itemTotal, 2, '.', '')
                        ]
                    ]
                ],
                'items' => array_map(function($item) {
                    return [
                        'name' => $item['title'],
                        'unit_amount' => [
                            'currency_code' => $this->config['currency'],
                            'value' => number_format($item['price'], 2, '.', '')
                        ],
                        'quantity' => $item['quantity']
                    ];
                }, $items)
            ]],
            'application_context' => [
                'return_url' => $this->config['return_url'],
                'cancel_url' => $this->config['cancel_url']
            ]
        ];

        try {
            error_log("Sending PayPal request: " . print_r($request->body, true));
            $response = $this->client->execute($request);
            error_log("PayPal response: " . print_r($response, true));
            return $response->result;
        } catch (Exception $e) {
            error_log("PayPal Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function captureOrder($orderId) {
        $request = new OrdersCaptureRequest($orderId);
        $request->prefer('return=representation');

        try {
            $response = $this->client->execute($request);
            return $response->result;
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }
} 