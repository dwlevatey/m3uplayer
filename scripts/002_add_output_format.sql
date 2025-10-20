-- Add output_format column to client_credentials table
ALTER TABLE client_credentials 
ADD COLUMN output_format VARCHAR(20) DEFAULT 'mpegts' AFTER password;

-- Update existing records to have the default format
UPDATE client_credentials SET output_format = 'mpegts' WHERE output_format IS NULL;
