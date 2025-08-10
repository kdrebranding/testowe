-- Telegram Bot Admin Panel Database Schema
-- Complete schema with all Phase 3 features - No duplicates

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    telegram_id INTEGER UNIQUE NOT NULL,
    username VARCHAR(100),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    is_admin BOOLEAN DEFAULT 0,
    is_approved BOOLEAN DEFAULT 0,
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Applications table
CREATE TABLE IF NOT EXISTS applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PLN',
    downloader_code VARCHAR(100),
    panel_url TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    application_id INTEGER NOT NULL,
    logo_file_id VARCHAR(200),
    logo_filename VARCHAR(200),
    status VARCHAR(20) DEFAULT 'pending',
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    completion_date DATETIME,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (application_id) REFERENCES applications(id)
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    status VARCHAR(20) DEFAULT 'pending',
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Issues table
CREATE TABLE IF NOT EXISTS issues (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'open',
    priority VARCHAR(20) DEFAULT 'medium',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME,
    admin_notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Client files table (Phase 3 - File Management)
CREATE TABLE IF NOT EXISTS client_files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    application_name VARCHAR(200),
    downloader_code VARCHAR(100),
    file_url TEXT,
    file_name VARCHAR(200),
    file_type VARCHAR(20) DEFAULT 'file',
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Admin messages table
CREATE TABLE IF NOT EXISTS admin_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_id INTEGER NOT NULL,
    recipient_id INTEGER,
    message_text TEXT,
    file_path VARCHAR(500),
    file_type VARCHAR(50),
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    delivery_status VARCHAR(20) DEFAULT 'sent',
    FOREIGN KEY (admin_id) REFERENCES users(id),
    FOREIGN KEY (recipient_id) REFERENCES users(id)
);

-- Access requests table
CREATE TABLE IF NOT EXISTS access_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    telegram_id INTEGER NOT NULL,
    username VARCHAR(100),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    request_message TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME,
    processed_by INTEGER,
    FOREIGN KEY (processed_by) REFERENCES users(id)
);

-- User sessions table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    telegram_id INTEGER NOT NULL,
    current_state VARCHAR(100),
    session_data TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME
);

-- Payment methods table (Phase 3 - Enhanced)
CREATE TABLE IF NOT EXISTS payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    provider VARCHAR(50) NOT NULL,
    api_key VARCHAR(255),
    secret_key VARCHAR(255),
    config_data TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Activity logs table (Phase 3 - Activity Logging)
CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_id INTEGER NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    resource_type VARCHAR(100),
    resource_id INTEGER,
    description TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- Insert sample data
INSERT OR IGNORE INTO users (telegram_id, username, first_name, last_name, is_admin, is_approved) 
VALUES (123456789, 'admin_test', 'Admin', 'User', 1, 1);

INSERT OR IGNORE INTO users (telegram_id, username, first_name, last_name, is_approved) VALUES 
(111111111, 'user1', 'Jan', 'Kowalski', 1),
(222222222, 'user2', 'Anna', 'Nowak', 0),
(333333333, 'user3', 'Piotr', 'Wiśniewski', 1);

INSERT OR IGNORE INTO applications (name, description, price, currency, downloader_code, panel_url) VALUES 
('Mobile App Premium', 'Aplikacja mobilna z funkcjami premium', 299.99, 'PLN', 'MOBILE_PREM_001', 'https://panel.example.com/mobile'),
('Web Dashboard Pro', 'Profesjonalny dashboard webowy', 499.99, 'PLN', 'WEB_DASH_001', 'https://panel.example.com/dashboard'),
('Analytics Tool', 'Narzędzie do analizy danych', 199.99, 'PLN', 'ANALYTICS_001', 'https://panel.example.com/analytics');

INSERT OR IGNORE INTO access_requests (telegram_id, username, first_name, last_name, request_message, status) VALUES 
(444444444, 'newuser1', 'Marek', 'Zieliński', 'Chcę otrzymać dostęp do systemu', 'pending'),
(555555555, 'newuser2', 'Katarzyna', 'Lewandowska', 'Potrzebuję dostępu do aplikacji', 'pending');

-- Sample client files (Phase 3)
INSERT OR IGNORE INTO client_files (user_id, application_name, downloader_code, file_name, file_url, file_type) VALUES 
(1, 'Mobile App Premium', 'MOBILE_PREM_001', 'mobile_app_v1.2.3.apk', 'https://files.example.com/mobile_app_v1.2.3.apk', 'app'),
(1, 'Web Dashboard Pro', 'WEB_DASH_001', 'dashboard_installer.zip', 'https://files.example.com/dashboard_installer.zip', 'file'),
(1, 'Analytics Tool', 'ANALYTICS_001', 'analytics_manual.pdf', 'https://files.example.com/analytics_manual.pdf', 'file');

-- Sample payment methods (Phase 3)
INSERT OR IGNORE INTO payment_methods (name, provider, api_key, secret_key, config_data) VALUES 
('PayPal Standard', 'paypal', 'pk_test_123456789', 'sk_test_987654321', '{"mode":"sandbox","currency":"PLN"}'),
('Stripe Payments', 'stripe', 'pk_live_abcdefgh', 'sk_live_ijklmnop', '{"webhook_secret":"whsec_test123"}');

