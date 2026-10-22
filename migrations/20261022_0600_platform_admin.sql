-- One account must be allowed to create and suspend salons. admins.role is free text
-- that PHP validates against Admin::ROLES, so this only re-labels the first account.
UPDATE admins a JOIN (SELECT MIN(id) AS mid FROM admins) m ON a.id = m.mid SET a.role = 'platform';

-- @down
UPDATE admins SET role = 'owner' WHERE role = 'platform';
