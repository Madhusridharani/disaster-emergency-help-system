-- ============================================================
--  Disaster Emergency Help System — Database Schema
--  File: schema.sql
--  Run this in phpMyAdmin or MySQL CLI to set up the database
-- ============================================================

-- 1. Create and select database
CREATE DATABASE IF NOT EXISTS disaster_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE disaster_db;

-- ============================================================
-- 2. Users table
--    Stores registered public users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT          NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(180) NOT NULL UNIQUE,
    phone       VARCHAR(20)  NOT NULL,
    password    VARCHAR(255) NOT NULL,         -- bcrypt hash
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. Emergency reports table
--    Stores all disaster reports submitted by users
-- ============================================================
CREATE TABLE IF NOT EXISTS emergency_reports (
    id            INT          NOT NULL AUTO_INCREMENT,
    user_id       INT          NOT NULL,
    disaster_type ENUM('fire','flood','accident','medical','other') NOT NULL,
    location      VARCHAR(255) NOT NULL,
    description   TEXT         NOT NULL,
    status        ENUM('Pending','In Progress','Resolved') NOT NULL DEFAULT 'Pending',
    reported_at   DATETIME     NOT NULL,       -- when the incident occurred
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- when record was inserted
    PRIMARY KEY (id),
    INDEX idx_user    (user_id),
    INDEX idx_status  (status),
    INDEX idx_dtype   (disaster_type),
    CONSTRAINT fk_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. Sample data (optional — for testing / demo)
-- ============================================================

-- Sample user (password: password123)
INSERT IGNORE INTO users (name, email, phone, password, created_at) VALUES
('Arjun Kumar',   'arjun@example.com',  '+91 9876543210',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 NOW() - INTERVAL 5 DAY),
('Priya Sharma',  'priya@example.com',  '+91 9123456789',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 NOW() - INTERVAL 3 DAY);

-- Sample reports
INSERT IGNORE INTO emergency_reports
  (user_id, disaster_type, location, description, status, reported_at, created_at)
VALUES
(1, 'fire',
 'MG Road, Bengaluru, Karnataka',
 'Large fire in a commercial building on MG Road near Metro Station. Three floors engulfed. Multiple people trapped on upper floors.',
 'In Progress',
 NOW() - INTERVAL 2 HOUR,
 NOW() - INTERVAL 2 HOUR),

(1, 'flood',
 'Anna Nagar, Chennai, Tamil Nadu',
 'Severe waterlogging after heavy overnight rain. Roads blocked, knee-deep water in residential areas. Several cars submerged.',
 'Pending',
 NOW() - INTERVAL 1 DAY,
 NOW() - INTERVAL 1 DAY),

(2, 'accident',
 'NH-44, Krishnagiri Bypass, Tamil Nadu',
 'Head-on collision between truck and passenger bus near Krishnagiri toll. Approximately 15 injured. Ambulance needed urgently.',
 'Resolved',
 NOW() - INTERVAL 3 DAY,
 NOW() - INTERVAL 3 DAY),

(2, 'medical',
 'Jubilee Hills, Hyderabad, Telangana',
 'Elderly man collapsed on street. Suspected cardiac arrest. Bystanders administering CPR. Ambulance has not arrived yet.',
 'Pending',
 NOW() - INTERVAL 30 MINUTE,
 NOW() - INTERVAL 30 MINUTE);

-- ============================================================
-- 5. Admin account is hard-coded in index.php:
--    Email   : admin@disaster.gov
--    Password: admin123
--    (No DB entry needed for admin)
-- ============================================================
