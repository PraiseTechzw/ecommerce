USE ecommerce;

-- Add is_api_product column if it doesn't exist
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS is_api_product BOOLEAN DEFAULT FALSE; 