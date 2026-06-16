-- Create Database
CREATE DATABASE IF NOT EXISTS agri_social_db;
USE agri_social_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('farmer', 'investor', 'customer', 'admin', 'agri_expert') NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(255),
    profile_photo VARCHAR(500),
    bio TEXT,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Farmer Profiles (verification)
CREATE TABLE IF NOT EXISTS farmer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    farm_name VARCHAR(100),
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Agri Expert Profiles
CREATE TABLE IF NOT EXISTS agri_expert_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    expertise_area VARCHAR(100) NOT NULL,
    years_experience INT NOT NULL,
    qualifications TEXT,
    bio TEXT,
    availability_status ENUM('available', 'busy', 'unavailable') DEFAULT 'available',
    consultation_rate DECIMAL(10, 2),
    rating DECIMAL(3, 2) DEFAULT 0.00,
    total_consultations INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Crops/Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL,
    unit VARCHAR(20) DEFAULT 'kg',
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Impact Reports
CREATE TABLE IF NOT EXISTS impact_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    unit VARCHAR(20),
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Donations/Social Contributions
CREATE TABLE IF NOT EXISTS contributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investor_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    project_name VARCHAR(100),
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Resource Library
CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    type ENUM('pdf', 'video', 'link') NOT NULL,
    content_url VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order Items
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Expert Resources (Videos, PDFs, Climate Content)
CREATE TABLE IF NOT EXISTS expert_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expert_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    content_type ENUM('video', 'pdf', 'climate_guide', 'research_paper') NOT NULL,
    file_url VARCHAR(500),
    file_size DECIMAL(10, 2),
    duration_minutes INT,
    category VARCHAR(100),
    tags TEXT,
    download_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    rating DECIMAL(3, 2) DEFAULT 0.00,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (expert_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Farmer Media (Grain Videos and Pictures)
CREATE TABLE IF NOT EXISTS farmer_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    product_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    media_type ENUM('video', 'image') NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    file_size DECIMAL(10, 2),
    thumbnail_url VARCHAR(500),
    duration_seconds INT,
    grain_type VARCHAR(100),
    growth_stage VARCHAR(50),
    location VARCHAR(255),
    planting_date DATE,
    harvest_date DATE,
    tags TEXT,
    view_count INT DEFAULT 0,
    like_count INT DEFAULT 0,
    status ENUM('draft', 'published', 'archived') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Email Verification Codes
CREATE TABLE IF NOT EXISTS email_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    verification_code VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_code (verification_code),
    INDEX idx_expires (expires_at)
);

-- Password Reset Tokens
CREATE TABLE IF NOT EXISTS password_reset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    reset_token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (reset_token),
    INDEX idx_expires (expires_at)
);

-- Payment Methods
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    provider VARCHAR(50) NOT NULL,
    type ENUM('bank', 'mobile_money', 'card') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    config_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payment Transactions
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    order_id INT,
    contribution_id INT,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'ETB',
    payment_method_id INT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    provider_transaction_id VARCHAR(100),
    provider_response JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (contribution_id) REFERENCES contributions(id) ON DELETE SET NULL,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- User Payment Accounts
CREATE TABLE IF NOT EXISTS user_payment_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    payment_method_id INT NOT NULL,
    account_number VARCHAR(100),
    account_name VARCHAR(100),
    is_default BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id),
    UNIQUE KEY unique_user_method (user_id, payment_method_id, account_number)
);
