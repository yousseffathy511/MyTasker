-- Add role column to users table if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') NOT NULL DEFAULT 'user';

-- Update at least one user to be admin (adjust the user ID as needed)
-- Pick the first user as admin in this case
UPDATE users SET role = 'admin' WHERE id = 1;

-- Create an admin user if none exists 
INSERT INTO users (name, email, password_hash, data_retention_approved, data_retention_date, role, created_at)
SELECT 
    'Admin User',
    'admin@mytasker.local',
    '$2y$10$v/rrjoqVs8vUXmZJTrE4je3JA4FUCT1RbLhHHwNs6TXuLxj1Y.fdy', -- Password: Admin@123
    1,
    NOW(),
    'admin',
    NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'admin' LIMIT 1); 