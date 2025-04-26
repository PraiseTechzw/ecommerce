<?php
class FakeStoreAPI {
    private $base_url = 'https://fakestoreapi.com';

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