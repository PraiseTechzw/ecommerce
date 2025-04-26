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
        $this->config = require __DIR__ . '/../config/paypal.php';
        $environment = new SandboxEnvironment($this->config['client_id'], $this->config['client_secret']);
        $this->client = new PayPalHttpClient($environment);
    }

    public function createOrder($items, $total) {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        
        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $this->config['currency'],
                    'value' => $total
                ],
                'items' => array_map(function($item) {
                    return [
                        'name' => $item['title'],
                        'unit_amount' => [
                            'currency_code' => $this->config['currency'],
                            'value' => $item['price']
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
            $response = $this->client->execute($request);
            return $response->result;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return null;
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
            return null;
        }
    }
} 