-- Skema Database MySQL untuk Hostinger
-- Database: Inventory 3

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` JSON
);

-- Users table
CREATE TABLE IF NOT EXISTS app_users (
    id BIGINT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(20) DEFAULT 'admin'
);

-- Sales table
CREATE TABLE IF NOT EXISTS sales (
    id BIGINT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    buyer_count INT DEFAULT 0,
    debt DECIMAL(15,2) DEFAULT 0,
    limit_amount DECIMAL(15,2) DEFAULT 0,
    due_days INT DEFAULT 0,
    phone VARCHAR(20)
);

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id BIGINT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    ktp VARCHAR(30)
);

-- Suppliers table
CREATE TABLE IF NOT EXISTS suppliers (
    id BIGINT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20)
);

-- Items table
CREATE TABLE IF NOT EXISTS items (
    id BIGINT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    unit VARCHAR(20) DEFAULT 'Dus',
    cost_price DECIMAL(15,2) DEFAULT 0,
    sell_price DECIMAL(15,2) DEFAULT 0,
    stock INT DEFAULT 0
);

-- Invoices table
CREATE TABLE IF NOT EXISTS invoices (
    id BIGINT PRIMARY KEY,
    invoice_no VARCHAR(50) UNIQUE NOT NULL,
    invoice_date DATE NOT NULL,
    sales_id BIGINT,
    sales_name VARCHAR(100),
    customer_id BIGINT,
    customer_name VARCHAR(100),
    total_dus INT DEFAULT 0,
    total_bonus INT DEFAULT 0,
    total_amount DECIMAL(15,2) DEFAULT 0,
    is_return INT DEFAULT 0,
    is_cash INT DEFAULT 0,
    notes TEXT,
    items JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
