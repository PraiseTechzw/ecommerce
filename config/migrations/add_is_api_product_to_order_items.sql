USE ecommerce;

-- Add is_api_product column to order_items table if it doesn't exist
ALTER TABLE order_items 
ADD COLUMN IF NOT EXISTS is_api_product BOOLEAN DEFAULT FALSE; 