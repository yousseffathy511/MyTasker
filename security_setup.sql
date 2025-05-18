-- Security setup for TaskMaker application

-- 1. Create audit_logs table for tracking user actions
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    resource VARCHAR(50) NOT NULL,
    resource_id VARCHAR(50) NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NOT NULL, -- IPv6 addresses can be longer
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Add index to improve query performance on audit logs
CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_action ON audit_logs(action);
CREATE INDEX idx_audit_logs_resource ON audit_logs(resource);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);

-- 3. Create users with limited privileges for enhanced security
-- First, create a read-only user for reporting purposes
CREATE USER IF NOT EXISTS 'taskmaker_readonly'@'localhost' IDENTIFIED BY 'ChangeThisPassword!';
GRANT SELECT ON taskmaker.* TO 'taskmaker_readonly'@'localhost';

-- Create application user with necessary privileges
CREATE USER IF NOT EXISTS 'taskmaker_app'@'localhost' IDENTIFIED BY 'ChangeThisAppPassword!';
GRANT SELECT, INSERT, UPDATE, DELETE ON taskmaker.* TO 'taskmaker_app'@'localhost';

-- 4. Prevent direct database administration operations for application user
REVOKE DROP, CREATE, ALTER, REFERENCES, INDEX, CREATE VIEW, SHOW VIEW, 
       TRIGGER, CREATE ROUTINE, ALTER ROUTINE, EXECUTE 
ON taskmaker.* FROM 'taskmaker_app'@'localhost';

-- 5. Encrypt sensitive columns (MySQL 5.7+ Enterprise or MariaDB 10.1+)
-- Note: This requires proper key management setup that is outside the scope of this script
-- ALTER TABLE users MODIFY password VARBINARY(255);

-- 6. Add a field for login attempts to help prevent brute force attacks
ALTER TABLE users ADD COLUMN login_attempts INT DEFAULT 0;
ALTER TABLE users ADD COLUMN last_login_attempt TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN account_locked BOOLEAN DEFAULT FALSE;

-- 7. Add data retention policy field for PDPA compliance
ALTER TABLE users ADD COLUMN data_retention_approved BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN data_retention_date TIMESTAMP NULL;

-- 8. Add session management columns
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN last_activity TIMESTAMP NULL;

-- Remember to flush privileges at the end
FLUSH PRIVILEGES; 