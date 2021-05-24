-- Definition

-- DROP TABLE "public"."social_accounts";
-- DROP TABLE "public"."users";

CREATE TABLE "public"."users" (
    "id" SERIAL PRIMARY KEY,
    "username" character varying(255) NOT NULL,
    "email" character varying(255),
    "password" character varying(255) NOT NULL,
    "first_name" character varying(50),
    "last_name" character varying(50),
    "token" character varying(255),
    "token_expires" timestamp without time zone,
    "api_token" character varying(255),
    "activation_date" timestamp without time zone,
    "tos_date" timestamp without time zone,
    "active" boolean NOT NULL DEFAULT false,
    "is_superuser" boolean NOT NULL DEFAULT false,
    "role" character varying(255) DEFAULT 'user'::character varying,
    "created" timestamp without time zone NOT NULL,
    "modified" timestamp without time zone NOT NULL,
    "secret" character varying(32),
    "secret_verified" boolean,
    "additional_data" text
) WITHOUT OIDS;

CREATE TABLE "public"."social_accounts" (
    "id" SERIAL PRIMARY KEY,
    "user_id" integer NOT NULL,
    "provider" character varying(255) NOT NULL,
    "username" character varying(255),
    "reference" character varying(255) NOT NULL,
    "avatar" character varying(255),
    "description" text,
    "link" character varying(255) NOT NULL,
    "token" character varying(500) NOT NULL,
    "token_secret" character varying(500),
    "token_expires" timestamp without time zone,
    "active" boolean NOT NULL DEFAULT true,
    "data" text NOT NULL,
    "created" timestamp without time zone NOT NULL,
    "modified" timestamp without time zone NOT NULL,
    CONSTRAINT "social_accounts_user_id_fkey" FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) WITHOUT OIDS;
