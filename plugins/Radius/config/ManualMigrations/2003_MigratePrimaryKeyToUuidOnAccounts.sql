-- Run this on slave DB, after primary DB is migrated to 20231029090040_MigratePrimaryKeyToUuidOnAccounts
-- (You will probably need to manually resync the table from the master to the slave DB and if you are using Bucardo you will need to re-set the sync due to the change in primary key type)

BEGIN;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
ALTER TABLE "public"."accounts" RENAME COLUMN "id" TO "nid";
ALTER TABLE "public"."accounts" ADD "id" UUID NOT NULL  DEFAULT uuid_generate_v4();
ALTER TABLE "public"."accounts" DROP CONSTRAINT "users_pkey", ADD CONSTRAINT "accounts_pkey" PRIMARY KEY ("id");
ALTER TABLE "public"."accounts" DROP COLUMN "nid";
COMMIT;