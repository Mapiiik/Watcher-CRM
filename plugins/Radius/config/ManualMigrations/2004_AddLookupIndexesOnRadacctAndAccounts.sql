-- Run this on BOTH the master and the slave DB - Bucardo replicates rows, not schema.
--
-- It takes a couple of seconds and holds a lock against the accounting writes for that long. If
-- `radacct` is large enough for that to matter, issue the statements one at a time with
-- CONCURRENTLY, which does not lock but cannot run inside a transaction.

-- The latest session of an account, for the customer and contract pages. Without it the whole
-- of `radacct` is read. `radacct_start_user_idx` leads with `acctstarttime` and cannot serve it.
DROP INDEX IF EXISTS "radacct_user_start_idx";

CREATE INDEX IF NOT EXISTS "radacct_user_start_idx"
    ON "public"."radacct" USING btree ("username", "acctstarttime");

-- How the CRM reaches the accounts.
DROP INDEX IF EXISTS "accounts_customer_id_idx";

CREATE INDEX IF NOT EXISTS "accounts_customer_id_idx"
    ON "public"."accounts" USING btree ("customer_id");

DROP INDEX IF EXISTS "accounts_contract_id_idx";

CREATE INDEX IF NOT EXISTS "accounts_contract_id_idx"
    ON "public"."accounts" USING btree ("contract_id");

ANALYZE "public"."radacct";
ANALYZE "public"."accounts";
