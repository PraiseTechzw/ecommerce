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

    public function createOrder($amount, $description) {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "reference_id" => "test_ref_id1",
                "amount" => [
                    "value" => $amount,
                    "currency_code" => $this->config['currency']
                ],
                "description" => $description
            ]],
            "application_context" => [
                "cancel_url" => $this->config['cancel_url'],
                "return_url" => $this->config['return_url']
            ]
        ];

        try {
            $response = $this->client->execute($request);
            return $response;
        } catch (Exception $e) {
            error_log("PayPal Error: " . $e->getMessage());
            return false;
        }
    }

    public function captureOrder($orderId) {
        $request = new OrdersCaptureRequest($orderId);
        $request->prefer('return=representation');

        try {
            $response = $this->client->execute($request);
            return $response;
        } catch (Exception $e) {
            error_log("PayPal Capture Error: " . $e->getMessage());
            return false;
        }
    }
} 