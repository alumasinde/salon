-- Multi-tenant support: tenants table and tenant_id columns
-- Custom domain-based resolution with shared database isolation

CREATE TABLE IF NOT EXISTS tenants (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  domain VARCHAR(255) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(60) NOT NULL UNIQUE,
  status ENUM('active', 'suspended', 'pending') NOT NULL DEFAULT 'pending',
  plan VARCHAR(30) NOT NULL DEFAULT 'basic',
  max_admins INT UNSIGNED NOT NULL DEFAULT 5,
  max_bookings_per_month INT UNSIGNED NOT NULL DEFAULT 500,
  custom_css TEXT NULL,
  logo_url VARCHAR(255) NOT NULL DEFAULT '',
  favicon_url VARCHAR(255) NOT NULL DEFAULT '',
  primary_color CHAR(7) NOT NULL DEFAULT '#3a1233',
  accent_color CHAR(7) NOT NULL DEFAULT '#d6336c',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_tenant_status (status),
  KEY idx_tenant_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add tenant_id to all data tables
ALTER TABLE settings ADD COLUMN tenant_id INT UNSIGNED NULL AFTER `value`;
ALTER TABLE settings ADD INDEX idx_settings_tenant (tenant_id);
ALTER TABLE settings ADD CONSTRAINT fk_settings_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE service_categories ADD COLUMN tenant_id INT UNSIGNED NULL AFTER id;
ALTER TABLE service_categories ADD INDEX idx_category_tenant (tenant_id);
ALTER TABLE service_categories ADD CONSTRAINT fk_category_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE services ADD COLUMN tenant_id INT UNSIGNED NULL AFTER category_id;
ALTER TABLE services ADD INDEX idx_service_tenant (tenant_id);
ALTER TABLE services ADD CONSTRAINT fk_service_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE booking_statuses ADD COLUMN tenant_id INT UNSIGNED NULL AFTER id;
ALTER TABLE booking_statuses ADD INDEX idx_status_tenant (tenant_id);
ALTER TABLE booking_statuses ADD CONSTRAINT fk_status_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE bookings ADD COLUMN tenant_id INT UNSIGNED NULL AFTER id;
ALTER TABLE bookings ADD INDEX idx_booking_tenant (tenant_id);
ALTER TABLE bookings ADD CONSTRAINT fk_booking_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE admins ADD COLUMN tenant_id INT UNSIGNED NULL AFTER id;
ALTER TABLE admins ADD INDEX idx_admin_tenant (tenant_id);
ALTER TABLE admins ADD CONSTRAINT fk_admin_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE opening_hours ADD COLUMN tenant_id INT UNSIGNED NULL AFTER id;
ALTER TABLE opening_hours ADD INDEX idx_hours_tenant (tenant_id);
ALTER TABLE opening_hours ADD CONSTRAINT fk_hours_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE special_days ADD COLUMN tenant_id INT UNSIGNED NULL AFTER id;
ALTER TABLE special_days ADD INDEX idx_special_tenant (tenant_id);
ALTER TABLE special_days ADD CONSTRAINT fk_special_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE site_content ADD COLUMN tenant_id INT UNSIGNED NULL AFTER `value`;
ALTER TABLE site_content ADD INDEX idx_content_tenant (tenant_id);
ALTER TABLE site_content ADD CONSTRAINT fk_content_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE site_blocks ADD COLUMN tenant_id INT UNSIGNED NULL AFTER id;
ALTER TABLE site_blocks ADD INDEX idx_blocks_tenant (tenant_id);
ALTER TABLE site_blocks ADD CONSTRAINT fk_blocks_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE reviews ADD COLUMN tenant_id INT UNSIGNED NULL AFTER id;
ALTER TABLE reviews ADD INDEX idx_review_tenant (tenant_id);
ALTER TABLE reviews ADD CONSTRAINT fk_review_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

-- Create default tenant from existing data
INSERT INTO tenants (domain, name, slug, status, plan, primary_color, accent_color)
VALUES ('zuribeauty.co.ke', 'Zuri Beauty Lounge', 'zuri-beauty-lounge', 'active', 'premium', '#3a1233', '#d6336c');

-- Assign existing data to default tenant
UPDATE settings SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE service_categories SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE services SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE booking_statuses SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE bookings SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE admins SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE opening_hours SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE special_days SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE site_content SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE site_blocks SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE reviews SET tenant_id = 1 WHERE tenant_id IS NULL;

-- Make tenant_id NOT NULL after migration
ALTER TABLE settings MODIFY COLUMN tenant_id INT UNSIGNED NOT NULL;
ALTER TABLE service_categories MODIFY COLUMN tenant_id INT UNSIGNED NOT NULL;
ALTER TABLE services MODIFY COLUMN tenant_id INT UNSIGNED NOT NULL;
ALTER TABLE booking_statuses MODIFY COLUMN tenant_id INT UNSIGNED NOT NULL;
ALTER TABLE bookings MODIFY COLUMN tenant_id INT UNSIGNED NOT NULL;
ALTER TABLE admins MODIFY COLUMN tenant_id INT UNSIGNED NOT NULL;
ALTER TABLE opening_hours MODIFY COLUMN tenant_id INT UNSIGNED NOT NULL;
ALTER TABLE special_days MODIFY COLUMN tenant_id INT UNSIGNED NOT NULL;
ALTER TABLE site_content MODIFY COLUMN tenant_id INT UNSIGNED NOT NULL;
ALTER TABLE site_blocks MODIFY COLUMN tenant_id INT UNSIGNED NOT NULL;
ALTER TABLE reviews MODIFY COLUMN tenant_id INT UNSIGNED NOT NULL;

-- @down
ALTER TABLE reviews DROP FOREIGN KEY fk_review_tenant;
ALTER TABLE reviews DROP INDEX idx_review_tenant;
ALTER TABLE reviews DROP COLUMN tenant_id;

ALTER TABLE site_blocks DROP FOREIGN KEY fk_blocks_tenant;
ALTER TABLE site_blocks DROP INDEX idx_blocks_tenant;
ALTER TABLE site_blocks DROP COLUMN tenant_id;

ALTER TABLE site_content DROP FOREIGN KEY fk_content_tenant;
ALTER TABLE site_content DROP INDEX idx_content_tenant;
ALTER TABLE site_content DROP COLUMN tenant_id;

ALTER TABLE special_days DROP FOREIGN KEY fk_special_tenant;
ALTER TABLE special_days DROP INDEX idx_special_tenant;
ALTER TABLE special_days DROP COLUMN tenant_id;

ALTER TABLE opening_hours DROP FOREIGN KEY fk_hours_tenant;
ALTER TABLE opening_hours DROP INDEX idx_hours_tenant;
ALTER TABLE opening_hours DROP COLUMN tenant_id;

ALTER TABLE admins DROP FOREIGN KEY fk_admin_tenant;
ALTER TABLE admins DROP INDEX idx_admin_tenant;
ALTER TABLE admins DROP COLUMN tenant_id;

ALTER TABLE bookings DROP FOREIGN KEY fk_booking_tenant;
ALTER TABLE bookings DROP INDEX idx_booking_tenant;
ALTER TABLE bookings DROP COLUMN tenant_id;

ALTER TABLE booking_statuses DROP FOREIGN KEY fk_status_tenant;
ALTER TABLE booking_statuses DROP INDEX idx_status_tenant;
ALTER TABLE booking_statuses DROP COLUMN tenant_id;

ALTER TABLE services DROP FOREIGN KEY fk_service_tenant;
ALTER TABLE services DROP INDEX idx_service_tenant;
ALTER TABLE services DROP COLUMN tenant_id;

ALTER TABLE service_categories DROP FOREIGN KEY fk_category_tenant;
ALTER TABLE service_categories DROP INDEX idx_category_tenant;
ALTER TABLE service_categories DROP COLUMN tenant_id;

ALTER TABLE settings DROP FOREIGN KEY fk_settings_tenant;
ALTER TABLE settings DROP INDEX idx_settings_tenant;
ALTER TABLE settings DROP COLUMN tenant_id;

DROP TABLE IF EXISTS tenants;
