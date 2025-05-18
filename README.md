# MyTasker - Personal To-Do List Manager

A modern, lightweight task management application built with PHP and MySQL for the CCS6344 Database & Cloud Security course.

## 👋 Introduction

MyTasker helps you organize your daily tasks with a clean, dark-themed interface that's easy on the eyes. Whether you're tracking personal projects or managing your daily to-dos, MyTasker makes it simple to stay organized.

## ✨ Features

- **User Management**: Easy registration and secure authentication
- **Task Organization**: Create, update, delete, and mark tasks as complete
- **Admin Dashboard**: Manage users and system functions
- **Dark Mode UI**: Comfortable viewing experience with reduced eye strain
- **Notifications**: System-wide announcements from administrators
- **Security**: Robust protection against common web vulnerabilities
- **PDPA Compliance**: Designed with personal data protection in mind
- **Database Backup**: Built-in backup and restore functionality

## 🔧 Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache web server (XAMPP recommended)
- Composer (for installing dependencies)

## 📦 Installation

1. **Set up your environment**
   - Install XAMPP (or equivalent) if not already installed
   - Start Apache and MySQL services

2. **Database Setup**
   - Open phpMyAdmin or MySQL command line
   - Run the SQL commands from `database_setup.sql` to create the database, user, and tables

3. **Application Setup**
   - Copy all files to your web server's document root (e.g., `htdocs` folder in XAMPP)
   - Run `composer install` to install required dependencies (including vlucas/phpdotenv)
   - Copy `.env.example` to `.env` and update the database credentials
   - Ensure your web server has write permissions for the `backups` directory

4. **Access the Application**
   - Open your web browser and navigate to: `http://localhost/mytasker/`
   - Register a new user and start managing your tasks

## 🚀 Running Locally with XAMPP

1. **Install XAMPP**:
   - Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - During installation, select at minimum: Apache, MySQL, PHP, and phpMyAdmin

2. **Start XAMPP Services**:
   - Launch the XAMPP Control Panel
   - Start the Apache and MySQL services by clicking the "Start" buttons

3. **Clone or Download MyTasker**:
   - Clone this repository: `git clone https://github.com/yousseffathy511/MyTasker.git`
   - Or download as ZIP and extract to your XAMPP htdocs folder: `C:\xampp\htdocs\taskmaker` (Windows) or `/Applications/XAMPP/htdocs/taskmaker` (Mac)

4. **Import Database Schema**:
   - Open your web browser and navigate to: `http://localhost/phpmyadmin`
   - Create a new database named `taskmaker`
   - Select the new database, then click the "Import" tab
   - Import these SQL files in the following order:
     1. `database_setup.sql` (creates base tables)
     2. `notification_tables.sql` (adds notification functionality)
     3. `security_setup.sql` (adds security enhancements)
     4. `update_role_column.sql` (updates schema for roles)

5. **Configure Environment**:
   - Copy `.env.example` to `.env` in the project root
   - Edit the `.env` file to match your local database settings:
     ```
     DB_HOST=localhost
     DB_NAME=taskmaker
     DB_USER=root
     DB_PASS=
     ```
     Note: Default XAMPP MySQL user is 'root' with an empty password

6. **Install Dependencies**:
   - Open a terminal/command prompt
   - Navigate to the project directory: `cd C:\xampp\htdocs\taskmaker` (adjust path as needed)
   - Run: `composer install`

7. **Set Permissions**:
   - Make sure the `backups` and `logs` directories are writable
   - On Windows, this is usually not an issue
   - On Linux/Mac: `chmod -R 755 backups/ logs/`

8. **Access Application**:
   - Open your browser and navigate to: `http://localhost/taskmaker`
   - Register an account to begin using the application

9. **Default Administrator**:
   - Username: admin@mytasker.local
   - Password: Admin@123
   - Use these credentials to access admin features

## 🛡️ Security Features

- Strong password hashing with PHP's secure `password_hash()` function
- CSRF token protection on all forms to prevent cross-site request forgery
- Comprehensive input sanitization and validation
- Prepared statements for all database queries to prevent SQL injection
- Secure session management with session ID regeneration on login
- Environment-based configuration for sensitive data
- Complete audit logging of sensitive operations

## 🏗️ Directory Structure

```
mytasker/
├── assets/
│   ├── css/
│   ├── img/
│   └── js/
├── backups/
├── bootstrap.php
├── composer.json
├── composer.lock
├── config/
│   └── Database.php
├── includes/
│   ├── auth.php
│   ├── footer.php
│   ├── functions.php
│   ├── header.php
│   ├── task.php
│   └── audit.php
├── logs/
├── vendor/
├── admin.php
├── backup.php
├── change_password.php
├── create.php
├── create_notification.php
├── database_setup.sql
├── delete.php
├── delete_account.php
├── delete_backup.php
├── delete_notification.php
├── download_backup.php
├── edit.php
├── index.php
├── login.php
├── logout.php
├── notification_tables.sql
├── notifications.php
├── pdpa.php
├── PDPA_MAPPING.md
├── profile.php
├── README.md
├── register.php
├── restore.php
├── security_setup.sql
├── THREAT_MODEL.md
├── update_role_column.sql
├── update_status.php
└── users.php
```

## 🤝 Team Contribution

This project was developed equally by three team members, each contributing 33.33% of the work:

### Abdullah Omar Hamad Bin Afeef
- Frontend development and responsive design
- Task management functionality
- Database security implementation
- PDPA compliance features
- Security testing

### Al Baraa Al Refai
- Authentication system
- Database schema design
- Security audit logging
- User management system
- PDPA documentation

### Youssef Fathy Fathy Mahrous Elsakkar
- Admin dashboard implementation
- Notification system
- Database backup/restore functionality
- Threat modeling
- Security controls testing

## 📝 Notes

- This application was developed for educational purposes as part of the CCS6344 Database & Cloud Security course
- The application demonstrates secure coding practices and PDPA compliance
- While designed for educational use, the security features implemented represent industry best practices 