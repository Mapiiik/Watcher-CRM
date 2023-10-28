-- Test database schema for RUIAN
DROP TABLE IF EXISTS "addresses";
DROP SEQUENCE IF EXISTS addresses_kod_adm_seq;
CREATE SEQUENCE addresses_kod_adm_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."addresses" (
    "kod_adm" integer DEFAULT nextval('addresses_kod_adm_seq') NOT NULL,
    "obec_kod" integer,
    "obec_nazev" character varying,
    "momc_kod" integer,
    "momc_nazev" character varying,
    "mop_kod" integer,
    "mop_nazev" character varying,
    "cast_obce_kod" integer,
    "cast_obce_nazev" character varying,
    "ulice_kod" integer,
    "ulice_nazev" character varying,
    "typ_so" character varying,
    "cislo_domovni" integer,
    "cislo_orientacni" integer,
    "cislo_orientacni_znak" character varying,
    "psc" integer,
    "plati_od" date,
    --"geometry" geometry(Point,4326),
    --"geometry_jtsk" geometry(Point,5514),
    CONSTRAINT "addresses_pkey" PRIMARY KEY ("kod_adm")
) WITH (oids = false);

--CREATE INDEX "idx_addresses_geometry" ON "public"."addresses" USING btree ("geometry");

--CREATE INDEX "idx_addresses_geometry_jtsk" ON "public"."addresses" USING btree ("geometry_jtsk");

CREATE INDEX "ix_addresses_obec_nazev" ON "public"."addresses" USING btree ("obec_nazev");

CREATE INDEX "ix_addresses_psc" ON "public"."addresses" USING btree ("psc");

