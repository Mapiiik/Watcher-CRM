-- Run this on slave DB, after primary DB is migrated to 20231029090040_MigratePrimaryKeyToUuidOnAccounts

BEGIN;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
ALTER TABLE "public"."accounts" RENAME COLUMN "id" TO "nid";
ALTER TABLE "public"."accounts" ADD "id" UUID NOT NULL  DEFAULT uuid_generate_v4();
ALTER TABLE "public"."accounts" DROP CONSTRAINT "users_pkey", ADD CONSTRAINT "accounts_pkey" PRIMARY KEY ("id");
ALTER TABLE "public"."accounts" DROP COLUMN "nid";
COMMIT;