USE ecommerce;

-- Add paypal_order_id column if it doesn't exist
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS paypal_order_id VARCHAR(255); 