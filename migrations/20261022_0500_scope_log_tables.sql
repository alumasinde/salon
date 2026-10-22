-- Scope the three log tables by tenant so one salon cannot read another salon's
-- audit trail, SMS history or failed sign-in records.
-- Nullable on purpose: a log row must never fail to write because a tenant is unknown,
-- and removing a tenant keeps its history instead of cascading it away.

ALTER TABLE sms_queue ADD COLUMN tenant_id INT UNSIGNED NULL AFTER id;
ALTER TABLE sms_queue ADD INDEX idx_sms_tenant (tenant_id, created_at);
ALTER TABLE sms_queue ADD CONSTRAINT fk_sms_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL;

ALTER TABLE activity_log ADD COLUMN tenant_id INT UNSIGNED NULL AFTER id;
ALTER TABLE activity_log ADD INDEX idx_log_tenant (tenant_id, created_at);
ALTER TABLE activity_log ADD CONSTRAINT fk_log_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL;

ALTER TABLE login_attempts ADD COLUMN tenant_id INT UNSIGNED NULL AFTER id;
ALTER TABLE login_attempts ADD INDEX idx_attempt_tenant (tenant_id, created_at);
ALTER TABLE login_attempts ADD CONSTRAINT fk_attempt_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL;

UPDATE sms_queue SET tenant_id = (SELECT MIN(id) FROM tenants) WHERE tenant_id IS NULL;
UPDATE activity_log SET tenant_id = (SELECT MIN(id) FROM tenants) WHERE tenant_id IS NULL;
UPDATE login_attempts SET tenant_id = (SELECT MIN(id) FROM tenants) WHERE tenant_id IS NULL;

-- @down
ALTER TABLE sms_queue DROP FOREIGN KEY fk_sms_tenant;
ALTER TABLE sms_queue DROP INDEX idx_sms_tenant;
ALTER TABLE sms_queue DROP COLUMN tenant_id;

ALTER TABLE activity_log DROP FOREIGN KEY fk_log_tenant;
ALTER TABLE activity_log DROP INDEX idx_log_tenant;
ALTER TABLE activity_log DROP COLUMN tenant_id;

ALTER TABLE login_attempts DROP FOREIGN KEY fk_attempt_tenant;
ALTER TABLE login_attempts DROP INDEX idx_attempt_tenant;
ALTER TABLE login_attempts DROP COLUMN tenant_id;
