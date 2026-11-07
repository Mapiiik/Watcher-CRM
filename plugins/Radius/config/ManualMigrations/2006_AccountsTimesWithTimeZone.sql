-- Run this on BOTH the master and the slave DB - Bucardo replicates rows, not schema.
--
-- For a database that still has these two columns without a time zone. The application writes them
-- in its own time zone and not in that of the database, so that zone is what they are converted
-- from: put the value of APP_DEFAULT_TIMEZONE in place of Europe/Prague. Without it every record
-- would move by the difference. The table is rewritten under a lock, which takes a moment and fires
-- no triggers, so Bucardo has nothing to replicate.
--
-- Afterwards run `bin/cake schema_cache clear --connection radius` in the application, or it goes on
-- writing a local time with no zone.
ALTER TABLE "public"."accounts"
    ALTER COLUMN "created" TYPE timestamptz USING "created" AT TIME ZONE 'Europe/Prague',
    ALTER COLUMN "modified" TYPE timestamptz USING "modified" AT TIME ZONE 'Europe/Prague';
