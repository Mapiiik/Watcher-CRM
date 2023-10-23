-- Run this on master/standalone DB

CREATE SEQUENCE accounts_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."accounts" (
    "id" integer DEFAULT nextval('accounts_id_seq') NOT NULL,
    "username" text DEFAULT '' NOT NULL,
    "password" text DEFAULT '' NOT NULL,
    "type" integer DEFAULT '0' NOT NULL,
    "active" boolean DEFAULT true NOT NULL,
    "customer_id" integer NOT NULL,
    "contract_id" integer NOT NULL,
    "created" timestamp DEFAULT now() NOT NULL,
    "created_by" integer DEFAULT '0' NOT NULL,
    "modified" timestamp,
    "modified_by" integer,
    CONSTRAINT "users_pkey" PRIMARY KEY ("id"),
    CONSTRAINT "users_username" UNIQUE ("username")
) WITH (oids = false);


CREATE SEQUENCE nas_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."nas" (
    "id" integer DEFAULT nextval('nas_id_seq') NOT NULL,
    "nasname" text NOT NULL,
    "shortname" text NOT NULL,
    "type" text DEFAULT 'other' NOT NULL,
    "ports" integer,
    "secret" text NOT NULL,
    "server" text,
    "community" text,
    "description" text,
    CONSTRAINT "nas_pkey" PRIMARY KEY ("id")
) WITH (oids = false);

CREATE INDEX "nas_nasname" ON "public"."nas" USING btree ("nasname");


CREATE SEQUENCE radacct_radacctid_seq INCREMENT 2 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1;

CREATE TABLE "public"."radacct" (
    "radacctid" bigint DEFAULT nextval('radacct_radacctid_seq') NOT NULL,
    "acctsessionid" text NOT NULL,
    "acctuniqueid" text NOT NULL,
    "username" text,
    "realm" text,
    "nasipaddress" inet NOT NULL,
    "nasportid" text,
    "nasporttype" text,
    "acctstarttime" timestamptz,
    "acctupdatetime" timestamptz,
    "acctstoptime" timestamptz,
    "acctinterval" bigint,
    "acctsessiontime" bigint,
    "acctauthentic" text,
    "connectinfo_start" text,
    "connectinfo_stop" text,
    "acctinputoctets" bigint,
    "acctoutputoctets" bigint,
    "calledstationid" text,
    "callingstationid" text,
    "acctterminatecause" text,
    "servicetype" text,
    "framedprotocol" text,
    "framedipaddress" inet,
    "framedipv6address" inet,
    "framedipv6prefix" inet,
    "framedinterfaceid" text,
    "delegatedipv6prefix" inet,
    CONSTRAINT "radacct_acctuniqueid_key" UNIQUE ("acctuniqueid"),
    CONSTRAINT "radacct_pkey" PRIMARY KEY ("radacctid")
) WITH (oids = false);

CREATE INDEX "radacct_active_session_idx" ON "public"."radacct" USING btree ("acctuniqueid");

CREATE INDEX "radacct_bulk_close" ON "public"."radacct" USING btree ("nasipaddress", "acctstarttime");

CREATE INDEX "radacct_start_user_idx" ON "public"."radacct" USING btree ("acctstarttime", "username");


CREATE SEQUENCE radcheck_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."radcheck" (
    "id" integer DEFAULT nextval('radcheck_id_seq') NOT NULL,
    "username" text DEFAULT '' NOT NULL,
    "attribute" text DEFAULT '' NOT NULL,
    "op" character varying(2) DEFAULT '==' NOT NULL,
    "value" text DEFAULT '' NOT NULL,
    CONSTRAINT "radcheck_pkey" PRIMARY KEY ("id")
) WITH (oids = false);

CREATE INDEX "radcheck_username" ON "public"."radcheck" USING btree ("username", "attribute");


CREATE SEQUENCE radgroupcheck_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."radgroupcheck" (
    "id" integer DEFAULT nextval('radgroupcheck_id_seq') NOT NULL,
    "groupname" text DEFAULT '' NOT NULL,
    "attribute" text DEFAULT '' NOT NULL,
    "op" character varying(2) DEFAULT '==' NOT NULL,
    "value" text DEFAULT '' NOT NULL,
    CONSTRAINT "radgroupcheck_pkey" PRIMARY KEY ("id")
) WITH (oids = false);

CREATE INDEX "radgroupcheck_groupname" ON "public"."radgroupcheck" USING btree ("groupname", "attribute");


CREATE SEQUENCE radgroupreply_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."radgroupreply" (
    "id" integer DEFAULT nextval('radgroupreply_id_seq') NOT NULL,
    "groupname" text DEFAULT '' NOT NULL,
    "attribute" text DEFAULT '' NOT NULL,
    "op" character varying(2) DEFAULT '=' NOT NULL,
    "value" text DEFAULT '' NOT NULL,
    CONSTRAINT "radgroupreply_pkey" PRIMARY KEY ("id")
) WITH (oids = false);

CREATE INDEX "radgroupreply_groupname" ON "public"."radgroupreply" USING btree ("groupname", "attribute");


CREATE SEQUENCE radpostauth_id_seq INCREMENT 2 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1;

CREATE TABLE "public"."radpostauth" (
    "id" bigint DEFAULT nextval('radpostauth_id_seq') NOT NULL,
    "username" text NOT NULL,
    "pass" text,
    "reply" text,
    "calledstationid" text,
    "callingstationid" text,
    "authdate" timestamptz DEFAULT now() NOT NULL,
    CONSTRAINT "radpostauth_pkey" PRIMARY KEY ("id")
) WITH (oids = false);


CREATE SEQUENCE radreply_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."radreply" (
    "id" integer DEFAULT nextval('radreply_id_seq') NOT NULL,
    "username" text DEFAULT '' NOT NULL,
    "attribute" text DEFAULT '' NOT NULL,
    "op" character varying(2) DEFAULT '=' NOT NULL,
    "value" text DEFAULT '' NOT NULL,
    CONSTRAINT "radreply_pkey" PRIMARY KEY ("id")
) WITH (oids = false);

CREATE INDEX "radreply_username" ON "public"."radreply" USING btree ("username", "attribute");


CREATE SEQUENCE radusergroup_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."radusergroup" (
    "id" integer DEFAULT nextval('radusergroup_id_seq') NOT NULL,
    "username" text DEFAULT '' NOT NULL,
    "groupname" text DEFAULT '' NOT NULL,
    "priority" integer DEFAULT '0' NOT NULL,
    CONSTRAINT "radusergroup_pkey" PRIMARY KEY ("id")
) WITH (oids = false);

CREATE INDEX "radusergroup_username" ON "public"."radusergroup" USING btree ("username");
