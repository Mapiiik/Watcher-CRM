-- Run this on BOTH the master and the slave DB.
--
-- Bucardo replicates rows, not schema, so an index created on one node is not
-- created on the other, and the node without it answers the same page by
-- reading the whole table.
--
-- The index is what lets "the latest session of this account" be read straight
-- out of the index instead of aggregating every session there has ever been.
-- The customer and contract pages both ask that question through the RADIUS
-- accounts cell. Measured over 422 000 sessions, the question was answered in
-- 780 ms without it and 5 ms with it - and without it the cost grows with the
-- table, which is the part that matters.
--
-- `radacct_start_user_idx` does not serve this: it leads with `acctstarttime`,
-- so it answers "what happened at this time", not "what did this account do".
--
-- On a live database create it CONCURRENTLY, which does not lock the table
-- against the accounting writes but cannot run inside a transaction:
--
--   CREATE INDEX CONCURRENTLY IF NOT EXISTS "radacct_user_start_idx"
--       ON "public"."radacct" USING btree ("username", "acctstarttime" DESC);
--
-- Roughly 20-25 MB over 422 000 sessions, and it can be dropped at any time:
--
--   DROP INDEX CONCURRENTLY IF EXISTS "radacct_user_start_idx";

CREATE INDEX IF NOT EXISTS "radacct_user_start_idx"
    ON "public"."radacct" USING btree ("username", "acctstarttime" DESC);

ANALYZE "public"."radacct";
