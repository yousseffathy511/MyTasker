-- MyTasker Database Setup Script
-- Character set: UTF8MB4
-- Engine: InnoDB

-- Create Database with UTF8MB4 charset
CREATE DATABASE ccs6344_a1 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Create MySQL User and Grant Privileges
CREATE USER 'ccs6344'@'localhost' IDENTIFIED BY 'your_password_here';
GRANT ALL PRIVILEGES ON ccs6344_a1.* TO 'ccs6344'@'localhost';
FLUSH PRIVILEGES;

-- Select Database
USE ccs6344_a1;

-- Create Users Table
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Create Tasks Table with Foreign Key
CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  is_done TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB; 