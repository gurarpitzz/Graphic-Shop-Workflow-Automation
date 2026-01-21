-- Database Schema for Graphic Shop Operations Management System (v2)

CREATE DATABASE IF NOT EXISTS graphic_shop_db;
USE graphic_shop_db;

-- 1. Customers Table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cust_id VARCHAR(20) UNIQUE, 
    name VARCHAR(100) NOT NULL,
    mobile_number VARCHAR(20) NOT NULL UNIQUE,
    is_recurring BOOLEAN DEFAULT FALSE,
    blacklist_status BOOLEAN DEFAULT FALSE,
    payment_pattern_score INT DEFAULT 100,
    rate_request_count INT DEFAULT 0,
    actual_order_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Users Table ... (unchanged)

-- 3. Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    order_id_code VARCHAR(20) UNIQUE,
    source ENUM('WhatsApp', 'In-Person') DEFAULT 'In-Person',
    
    project_type VARCHAR(50), 
    thumbnail_path VARCHAR(255),
    design_description TEXT,
    material_used VARCHAR(100),
    sizes VARCHAR(100),
    
    whatsapp_link VARCHAR(255),
    pancake_link VARCHAR(255),
    telegram_link VARCHAR(255),
    mail_link VARCHAR(255),
    
    total_amount DECIMAL(10, 2) DEFAULT 0.00,
    advance_paid DECIMAL(10, 2) DEFAULT 0.00,
    due_amount DECIMAL(10, 2) AS (total_amount - advance_paid) STORED,
    bill_number VARCHAR(50),
    bill_date DATE,
    tally_invoice_path VARCHAR(255),
    
    current_stage_name VARCHAR(100) DEFAULT 'Order Received',
    last_updated_by INT,
    is_closed BOOLEAN DEFAULT FALSE,
    is_archived BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (last_updated_by) REFERENCES users(id)
);

-- 4. Order Media (Multiple Uploads)
CREATE TABLE IF NOT EXISTS order_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('Design', 'Bill', 'Reference', 'Other') DEFAULT 'Other',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- 4. Order Workflow Stages (Milestones)
CREATE TABLE IF NOT EXISTS order_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    stage_name VARCHAR(100) NOT NULL,
    stage_index INT NOT NULL,
    
    responsible_person_id INT,
    start_date DATE,
    finish_date DATE,
    extra_notes TEXT,
    is_required BOOLEAN DEFAULT TRUE,
    is_completed BOOLEAN DEFAULT FALSE,
    
    completed_by INT,
    completed_at TIMESTAMP NULL,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (responsible_person_id) REFERENCES users(id),
    FOREIGN KEY (completed_by) REFERENCES users(id)
);

-- 5. Worker Daily Performance Tracking
CREATE TABLE IF NOT EXISTS worker_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    date DATE DEFAULT (CURRENT_DATE),
    tasks_completed INT DEFAULT 0,
    notes TEXT,
    FOREIGN KEY (worker_id) REFERENCES users(id)
);
