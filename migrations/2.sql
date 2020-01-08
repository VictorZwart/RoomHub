UPDATE user
SET role = 'tenant'
WHERE role = 'tennant';

-- previous constraint was wrong
ALTER TABLE user
    ADD CONSTRAINT only_tenant_or_owner CHECK (role IN ('tenant', 'owner'));