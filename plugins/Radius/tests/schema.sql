-- Test database schema for RADIUS - cleanup

DROP TABLE IF EXISTS "accounts";
DROP SEQUENCE IF EXISTS accounts_id_seq;

DROP TABLE IF EXISTS "nas";
DROP SEQUENCE IF EXISTS nas_id_seq;

DROP TABLE IF EXISTS "radacct";
DROP SEQUENCE IF EXISTS radacct_radacctid_seq;

DROP TABLE IF EXISTS "radcheck";
DROP SEQUENCE IF EXISTS radcheck_id_seq;

DROP TABLE IF EXISTS "radgroupcheck";
DROP SEQUENCE IF EXISTS radgroupcheck_id_seq;

DROP TABLE IF EXISTS "radgroupreply";
DROP SEQUENCE IF EXISTS radgroupreply_id_seq;

DROP TABLE IF EXISTS "radpostauth";
DROP SEQUENCE IF EXISTS radpostauth_id_seq;

DROP TABLE IF EXISTS "radreply";
DROP SEQUENCE IF EXISTS radreply_id_seq;

DROP TABLE IF EXISTS "radusergroup";
DROP SEQUENCE IF EXISTS radusergroup_id_seq;
