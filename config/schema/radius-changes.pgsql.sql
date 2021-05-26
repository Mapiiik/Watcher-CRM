CREATE TABLE users (
	"id"			serial PRIMARY KEY,
	"username"		text NOT NULL DEFAULT '',
	"password"		text NOT NULL DEFAULT '',
	"type"			integer NOT NULL DEFAULT 0,
	"active"		boolean NOT NULL DEFAULT true,
	"customer_id"		integer NOT NULL,
	"contract_id"		integer NOT NULL,
	"created"		timestamp without time zone NOT NULL DEFAULT now(),
	"created_by"		integer NOT NULL DEFAULT 0,
	"modified"		timestamp without time zone,
	"modified_by"		integer
);
CREATE UNIQUE INDEX users_username on users (username);
