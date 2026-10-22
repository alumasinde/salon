-- Baseline schema for the salon application.
-- MySQL 8 and MariaDB 10.4+ compatible. All tables InnoDB / utf8mb4.

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(64) NOT NULL PRIMARY KEY,
  `value` VARCHAR(255) NOT NULL DEFAULT '',
  `label` VARCHAR(120) NOT NULL DEFAULT '',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  blurb VARCHAR(255) NOT NULL DEFAULT '',
  sort INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uniq_category_name (name),
  KEY idx_category_sort (sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(500) NOT NULL DEFAULT '',
  duration VARCHAR(40) NOT NULL DEFAULT '',
  duration_min SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  price INT UNSIGNED NOT NULL DEFAULT 0,
  deposit INT UNSIGNED NOT NULL DEFAULT 0,
  popular TINYINT(1) NOT NULL DEFAULT 0,
  sort INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_service_sort (sort),
  KEY idx_service_active (active, category_id),
  CONSTRAINT fk_service_category FOREIGN KEY (category_id) REFERENCES service_categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Booking workflow states are configurable; "code" is what the application reads.
CREATE TABLE IF NOT EXISTS booking_statuses (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL,
  name VARCHAR(60) NOT NULL,
  color CHAR(7) NOT NULL DEFAULT '#6f5d6b',
  text_color CHAR(7) NOT NULL DEFAULT '#ffffff',
  sort INT NOT NULL DEFAULT 0,
  counts_as_booked TINYINT(1) NOT NULL DEFAULT 0,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uniq_status_code (code),
  KEY idx_status_sort (sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ref_code CHAR(6) NOT NULL DEFAULT '',
  client_name VARCHAR(120) NOT NULL,
  phone VARCHAR(24) NOT NULL,
  email VARCHAR(160) NOT NULL DEFAULT '',
  service_id INT UNSIGNED NULL,
  service_name VARCHAR(120) NOT NULL,
  service_price INT UNSIGNED NOT NULL DEFAULT 0,
  booking_date DATE NOT NULL,
  booking_time VARCHAR(10) NOT NULL,
  notes VARCHAR(500) NOT NULL DEFAULT '',
  status_id INT UNSIGNED NOT NULL,
  source VARCHAR(20) NOT NULL DEFAULT 'website',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_booking_ref (ref_code),
  KEY idx_booking_slot (booking_date, booking_time),
  KEY idx_booking_status (status_id),
  KEY idx_booking_phone (phone),
  KEY idx_booking_created (ip, created_at),
  CONSTRAINT fk_booking_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL,
  CONSTRAINT fk_booking_status FOREIGN KEY (status_id) REFERENCES booking_statuses (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(60) NOT NULL,
  last_name VARCHAR(60) NOT NULL DEFAULT '',
  username VARCHAR(60) NOT NULL,
  email VARCHAR(160) NOT NULL DEFAULT '',
  phone VARCHAR(24) NOT NULL DEFAULT '',
  pass_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'staff',
  active TINYINT(1) NOT NULL DEFAULT 1,
  sms_opt_in TINYINT(1) NOT NULL DEFAULT 0,
  force_password_change TINYINT(1) NOT NULL DEFAULT 0,
  theme_preset VARCHAR(30) NOT NULL DEFAULT 'house',
  theme_primary CHAR(7) NOT NULL DEFAULT '#3a1233',
  theme_accent CHAR(7) NOT NULL DEFAULT '#d6336c',
  theme_bg CHAR(7) NOT NULL DEFAULT '#f6f1f4',
  theme_surface CHAR(7) NOT NULL DEFAULT '#ffffff',
  theme_text CHAR(7) NOT NULL DEFAULT '#2a1626',
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_admin_username (username),
  KEY idx_admin_active (active, role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS opening_hours (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  day_of_week TINYINT NOT NULL,
  opens TIME NOT NULL DEFAULT '08:00:00',
  closes TIME NOT NULL DEFAULT '20:00:00',
  closed TINYINT(1) NOT NULL DEFAULT 0,
  note VARCHAR(120) NOT NULL DEFAULT '',
  UNIQUE KEY uniq_day (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS special_days (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `date` DATE NOT NULL,
  closed TINYINT(1) NOT NULL DEFAULT 1,
  opens TIME NULL,
  closes TIME NULL,
  label VARCHAR(80) NOT NULL DEFAULT '',
  UNIQUE KEY uniq_special_date (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_content (
  `key` VARCHAR(64) NOT NULL PRIMARY KEY,
  `value` TEXT NOT NULL,
  label VARCHAR(120) NOT NULL DEFAULT '',
  sort INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_blocks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  block_type VARCHAR(30) NOT NULL,
  title VARCHAR(120) NOT NULL,
  body VARCHAR(500) NOT NULL DEFAULT '',
  icon CHAR(2) NOT NULL DEFAULT '',
  sort INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  KEY idx_block_type (block_type, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  author VARCHAR(80) NOT NULL,
  location VARCHAR(80) NOT NULL DEFAULT '',
  rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
  body VARCHAR(600) NOT NULL,
  sort INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  KEY idx_review_active (active, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_queue (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NULL,
  to_number VARCHAR(24) NOT NULL,
  recipient_label VARCHAR(60) NOT NULL DEFAULT '',
  message VARCHAR(480) NOT NULL,
  gateway VARCHAR(40) NOT NULL DEFAULT 'textsms',
  status VARCHAR(12) NOT NULL DEFAULT 'queued',
  provider_response VARCHAR(255) NOT NULL DEFAULT '',
  error VARCHAR(255) NOT NULL DEFAULT '',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL,
  KEY idx_sms_status (status, created_at),
  KEY idx_sms_booking (booking_id),
  CONSTRAINT fk_sms_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(120) NOT NULL,
  ip VARCHAR(45) NOT NULL DEFAULT '',
  successful TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attempt_lookup (identifier, created_at),
  KEY idx_attempt_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  detail VARCHAR(255) NOT NULL DEFAULT '',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_log_admin (admin_id, created_at),
  CONSTRAINT fk_log_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @down
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS sms_queue;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS site_blocks;
DROP TABLE IF EXISTS site_content;
DROP TABLE IF EXISTS special_days;
DROP TABLE IF EXISTS opening_hours;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS booking_statuses;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS service_categories;
DROP TABLE IF EXISTS settings;
