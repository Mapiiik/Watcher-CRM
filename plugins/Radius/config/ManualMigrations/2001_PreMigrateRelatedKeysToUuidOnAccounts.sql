-- Run before MigrateRelatedKeysToUuidOnAccounts (e.g. bin/cake migrations migrate -p Radius -c radius)

BEGIN;
ALTER TABLE "public"."accounts" RENAME COLUMN "customer_id" TO "customer_nid";
ALTER TABLE "public"."accounts" RENAME COLUMN "contract_id" TO "contract_nid";
ALTER TABLE "public"."accounts" RENAME COLUMN "created_by" TO "created_nid";
ALTER TABLE "public"."accounts" RENAME COLUMN "modified_by" TO "modified_nid";
ALTER TABLE "public"."accounts" ADD "customer_id" UUID NULL , ADD "contract_id" UUID NULL , ADD "created_by" UUID NULL , ADD "modified_by" UUID NULL;
COMMIT;