-- Run after MigrateRelatedKeysToUuidOnAccounts (e.g. bin/cake migrations migrate -p Radius -c radius)

BEGIN;
ALTER TABLE "public"."accounts" ALTER COLUMN "customer_id" SET NOT NULL;
ALTER TABLE "public"."accounts" ALTER COLUMN "contract_id" SET NOT NULL;
ALTER TABLE "public"."accounts" DROP COLUMN "customer_nid", DROP COLUMN "contract_nid", DROP COLUMN "created_nid", DROP COLUMN "modified_nid";
COMMIT;