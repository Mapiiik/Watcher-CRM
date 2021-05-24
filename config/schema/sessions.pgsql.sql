-- Definition

-- DROP TABLE "public"."sessions";

CREATE TABLE "public"."sessions" (
    "id" character varying(40) NOT NULL PRIMARY KEY,
    "data" bytea,
    "expires" integer,
    "created" timestamp with time zone,
    "modified" timestamp with time zone
) WITHOUT OIDS;
