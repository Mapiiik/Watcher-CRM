-- Run this on the SLAVE DB after the primary DB has been migrated
-- using migration 20231029090040_MigratePrimaryKeyToUuidOnAccounts.
--
-- Due to the primary key type change, the table will most likely need
-- to be manually resynced from the master DB. If Bucardo is used,
-- the sync must be reconfigured.

-- Example Bucardo resync procedure:
-- bucardo stop sync radius
-- bucardo del table public.accounts
-- bucardo del sequence public.accounts_id_seq

-- To be absolutely sure (run on BOTH databases if the table was previously
-- part of a Bucardo sync and the primary key type was changed):
-- DROP TRIGGER bucardo_delta ON public.accounts;
-- DROP FUNCTION bucardo.delta_public_accounts();
-- DROP TABLE IF EXISTS bucardo.delta_public_accounts;
-- TRUNCATE TABLE public.accounts;
-- DROP SEQUENCE IF EXISTS public.accounts_id_seq;

-- Re-add the table to Bucardo:
-- bucardo add table public.accounts relgroup=radius
-- bucardo start sync radius
--
-- If the sync does not kick in automatically, perform a manual
-- export → truncate → import of the accounts table on the PRIMARY DB.

BEGIN;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
ALTER TABLE "public"."accounts" RENAME COLUMN "id" TO "nid";
ALTER TABLE "public"."accounts" ADD "id" UUID NOT NULL  DEFAULT uuid_generate_v4();
ALTER TABLE "public"."accounts" DROP CONSTRAINT "users_pkey", ADD CONSTRAINT "accounts_pkey" PRIMARY KEY ("id");
ALTER TABLE "public"."accounts" DROP COLUMN "nid";
COMMIT;
