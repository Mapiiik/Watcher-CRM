-- Run this on BOTH the master and the slave DB - Bucardo replicates rows, not schema.
--
-- The tables of a running RADIUS are written to all the time, so it is built without locking them,
-- which cannot run inside a transaction.

-- The latest sign-ins of an account, for its page. Without it the whole of `radpostauth` is read.
CREATE INDEX CONCURRENTLY IF NOT EXISTS "radpostauth_username_authdate"
    ON "public"."radpostauth" USING btree ("username", "authdate");

ANALYZE "public"."radpostauth";
