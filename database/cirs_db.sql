-- ==========================================================
-- CITIZEN ISSUE REPORTING SYSTEM (CIRS) DATABASE SCHEMA
-- Compatible with MySQL 5.7+ / MariaDB 10.4+ / XAMPP
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `cirs_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cirs_db`;

-- --------------------------------------------------------
-- Table: users (Registered Citizens)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `mobile` VARCHAR(20) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `is_verified` TINYINT(1) DEFAULT 1,
  `address` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: admins (System Administrators)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) DEFAULT 'Super Admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: departments (Civic Departments)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `department_name` VARCHAR(100) NOT NULL,
  `category_keyword` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `contact_email` VARCHAR(100),
  `phone` VARCHAR(20),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: complaints (Citizen Filed Complaints)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` VARCHAR(30) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `department_id` INT DEFAULT NULL,
  `category` ENUM('Pothole', 'Garbage', 'Water Leakage', 'Streetlight', 'Drainage', 'Illegal Dumping', 'Other') NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `latitude` DECIMAL(10, 8) DEFAULT NULL,
  `longitude` DECIMAL(11, 8) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `priority` ENUM('Low', 'Medium', 'High', 'Emergency') DEFAULT 'Medium',
  `status` ENUM('PENDING', 'IN PROGRESS', 'RESOLVED') DEFAULT 'PENDING',
  `is_verified` TINYINT(1) DEFAULT 0,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `assigned_at` TIMESTAMP NULL DEFAULT NULL,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_complaint_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_complaint_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: complaint_updates (Progress Logs & Official Remarks)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaint_updates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL,
  `admin_id` INT DEFAULT NULL,
  `status` ENUM('PENDING', 'IN PROGRESS', 'RESOLVED') NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_update_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_update_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: otp_verifications (Email & Mobile OTP Store)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `otp_verifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `email` VARCHAR(100) NULL,
  `mobile` VARCHAR(20) NULL,
  `otp_code` VARCHAR(255) NOT NULL,
  `otp_type` VARCHAR(30) DEFAULT 'REGISTRATION',
  `expires_at` TIMESTAMP NOT NULL,
  `is_used` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: contact_inquiries (Citizen General Inquiries)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_inquiries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('NEW', 'READ', 'REPLIED') DEFAULT 'NEW',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- PRE-POPULATED SEED DATA
-- ==========================================================

-- 1. Insert Predefined Departments
INSERT INTO `departments` (`id`, `department_name`, `category_keyword`, `description`, `contact_email`, `phone`) VALUES
(1, 'Road / Public Works Department', 'Pothole', 'Responsible for road maintenance, pothole repairs, bridges, and public infrastructure works.', 'pwd.roads@cirs.gov', '+91 800-111-2001'),
(2, 'Sanitation Department', 'Garbage', 'Handles municipal solid waste management, garbage collection, and illegal dumping cleanup.', 'sanitation@cirs.gov', '+91 800-111-2002'),
(3, 'Water Supply Department', 'Water Leakage', 'Oversees clean water distribution, pipeline maintenance, leak fixing, and water pressure issues.', 'watersupply@cirs.gov', '+91 800-111-2003'),
(4, 'Electricity Department', 'Streetlight', 'Maintains street lighting, electrical poles, cables, transformers, and public lighting networks.', 'electricity@cirs.gov', '+91 800-111-2004'),
(5, 'Drainage & Sewerage Department', 'Drainage', 'Maintains stormwater drainage lines, covers open drains, sewage treatment, and waterlogging.', 'drainage@cirs.gov', '+91 800-111-2005'),
(6, 'Municipal Administration Department', 'Other', 'General municipal administration, public encroachment, parks, and unspecified civic matters.', 'municipal@cirs.gov', '+91 800-111-2006');

-- 2. Insert Default Admin (Password: admin.gov)
-- Hash generated via password_hash('admin.gov', PASSWORD_DEFAULT)
INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Municipal Commissioner', 'admin@cirs.gov', '$2y$10$305.zu5LoD44U2vN9IBnC.EaAa397DfrNh4WsKc2MqKif7MuViyla', 'Super Admin');

-- 3. Insert Demo Citizens (Password: citizen123)
-- Hash generated via password_hash('citizen123', PASSWORD_DEFAULT)
INSERT INTO `users` (`id`, `full_name`, `email`, `mobile`, `password`, `is_verified`, `address`) VALUES
(1, 'Rahul Sharma', 'rahul.sharma@example.com', '9876543210', '$2y$10$qpaq1XFHRdfNHFQcR/G93e/VqQFUYZ3kKfsBaoRzD7xfwvuAH.RJe', 1, 'Flat 402, Green Valley Apartments, Station Road'),
(2, 'Priya Patel', 'priya.patel@example.com', '9876543211', '$2y$10$qpaq1XFHRdfNHFQcR/G93e/VqQFUYZ3kKfsBaoRzD7xfwvuAH.RJe', 1, '12-B, Shanti Nagar, Near City Library');

-- 4. Insert Demo Complaints
INSERT INTO `complaints` (`id`, `complaint_id`, `user_id`, `department_id`, `category`, `title`, `description`, `location`, `latitude`, `longitude`, `image`, `priority`, `status`, `is_verified`, `verified_at`, `assigned_at`, `resolved_at`, `created_at`) VALUES
(1, 'CIRS-2026-00001', 1, 1, 'Pothole', 'Deep dangerous pothole near MG Road flyover junction', 'There is a severe crater-like pothole right after descending the flyover. Two-wheelers are losing balance, especially during evening hours.', 'MG Road Junction, Pillar 42, Ward 14', 19.07600000, 72.87770000, NULL, 'High', 'PENDING', 0, NULL, NULL, NULL, '2026-09-12 10:15:00'),
(2, 'CIRS-2026-00002', 1, 2, 'Garbage', 'Unattended garbage pile accumulation near Vegetable Market', 'Community garbage bin has been overflowing for 4 days. Foul odor and stray animals causing severe health hazards for neighborhood residents.', 'Vegetable Market Yard, Sector 4', 19.08200000, 72.88300000, NULL, 'Medium', 'IN PROGRESS', 1, '2026-09-13 09:30:00', '2026-09-13 10:00:00', NULL, '2026-09-13 08:45:00'),
(3, 'CIRS-2026-00003', 2, 4, 'Streetlight', 'Multiple streetlights non-functional on 5th Cross Link Road', 'Three consecutive LED streetlights are not working for over a week, causing pitch dark conditions at night for pedestrians.', '5th Cross Road, Near Community Hall, Ward 9', 19.09100000, 72.89100000, NULL, 'High', 'RESOLVED', 1, '2026-09-10 11:00:00', '2026-09-10 11:30:00', '2026-09-11 16:45:00', '2026-09-10 09:00:00'),
(4, 'CIRS-2026-00004', 2, 5, 'Drainage', 'Open overflowing stormwater drainage near Primary School', 'Concrete drain slab is damaged and sewage water is overflowing onto the footpath right in front of the school gate.', 'Opposite St. Xavier Primary School, MG Colony', 19.07100000, 72.86500000, NULL, 'Emergency', 'IN PROGRESS', 1, '2026-09-14 08:30:00', '2026-09-14 09:00:00', NULL, '2026-09-14 08:00:00');

-- 5. Insert Demo Complaint Updates (Progress Logs)
INSERT INTO `complaint_updates` (`id`, `complaint_id`, `admin_id`, `status`, `message`, `created_at`) VALUES
(1, 2, 1, 'PENDING', 'Complaint received and verified by Municipal Control Room.', '2026-09-13 09:30:00'),
(2, 2, 1, 'IN PROGRESS', 'Assigned to Sanitation Department Ward 4 Supervisor. Waste clearance truck dispatched.', '2026-09-13 10:00:00'),
(3, 3, 1, 'PENDING', 'Complaint verified and forwarded to Electricity Department maintenance team.', '2026-09-10 11:00:00'),
(4, 3, 1, 'IN PROGRESS', 'Lineman unit assigned. Faulty fixture and burned driver circuit under repair.', '2026-09-10 14:20:00'),
(5, 3, 1, 'RESOLVED', 'All 3 LED fixtures replaced with 90W smart LED streetlights. Night illumination inspected and restored.', '2026-09-11 16:45:00'),
(6, 4, 1, 'PENDING', 'Emergency status verified. Field team notified immediately due to school vicinity.', '2026-09-14 08:30:00'),
(7, 4, 1, 'IN PROGRESS', 'Drainage desilting suction truck on site. Heavy concrete cover replacement in progress.', '2026-09-14 09:15:00');
