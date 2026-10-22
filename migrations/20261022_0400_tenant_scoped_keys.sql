-- Tenant-scoped uniqueness.
-- These rows are edited per salon, so the old single-column keys had to become
-- (tenant_id, ...) keys or a second tenant could never hold its own copy.
-- bookings.ref_code and admins.username stay globally unique on purpose: a short
-- booking reference must never collide across salons, and a username identifies a login.

ALTER TABLE settings DROP PRIMARY KEY, ADD PRIMARY KEY (`key`, tenant_id);
ALTER TABLE site_content DROP PRIMARY KEY, ADD PRIMARY KEY (`key`, tenant_id);
ALTER TABLE service_categories DROP INDEX uniq_category_name, ADD UNIQUE KEY uniq_category_tenant_name (tenant_id, name);
ALTER TABLE booking_statuses DROP INDEX uniq_status_code, ADD UNIQUE KEY uniq_status_tenant_code (tenant_id, code);
ALTER TABLE opening_hours DROP INDEX uniq_day, ADD UNIQUE KEY uniq_hours_tenant_day (tenant_id, day_of_week);
ALTER TABLE special_days DROP INDEX uniq_special_date, ADD UNIQUE KEY uniq_special_tenant_date (tenant_id, `date`);

-- @down
ALTER TABLE settings DROP PRIMARY KEY, ADD PRIMARY KEY (`key`);
ALTER TABLE site_content DROP PRIMARY KEY, ADD PRIMARY KEY (`key`);
ALTER TABLE service_categories DROP INDEX uniq_category_tenant_name, ADD UNIQUE KEY uniq_category_name (name);
ALTER TABLE booking_statuses DROP INDEX uniq_status_tenant_code, ADD UNIQUE KEY uniq_status_code (code);
ALTER TABLE opening_hours DROP INDEX uniq_hours_tenant_day, ADD UNIQUE KEY uniq_day (day_of_week);
ALTER TABLE special_days DROP INDEX uniq_special_tenant_date, ADD UNIQUE KEY uniq_special_date (`date`);
