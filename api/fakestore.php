<?php
class FakeStoreAPI {
    private $base_url = 'https://fakestoreapi.com';

    public function getAllProducts() {
        echo "<pre>Making API request to: " . $this->base_url . '/products' . "</pre>";
        
        $url = $this->base_url . '/products';
        $response = file_get_contents($url);
        
        if ($response === false) {
            $error = error_get_last();
            echo "<pre>API request failed. Error: " . print_r($error, true) . "</pre>";
            throw new Exception("Failed to fetch products from API: " . ($error['message'] ?? 'Unknown error'));
        }
        
        echo "<pre>API response received. Length: " . strlen($response) . " bytes</pre>";
        
        $products = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "<pre>JSON decode error: " . json_last_error_msg() . "</pre>";
            throw new Exception("Failed to decode API response: " . json_last_error_msg());
        }
        
        echo "<pre>Successfully decoded " . count($products) . " products</pre>";
        return $products;
    }

    public function getProducts($limit = null) {
        $url = $this->base_url . '/products';
        if ($limit) {
            $url .= "?limit=$limit";
        }
        
        $response = file_get_contents($url);
        return json_decode($response, true);
    }

    public function getProduct($id) {
        $url = $this->base_url . "/products/$id";
        $response = file_get_contents($url);
        return json_decode($response, true);
    }

    public function getCategories() {
        $url = $this->base_url . '/products/categories';
        $response = file_get_contents($url);
        return json_decode($response, true);
    }

    public function getProductsByCategory($category) {
        $url = $this->base_url . "/products/category/$category";
        $response = file_get_contents($url);
        return json_decode($response, true);
    }
}
?> 