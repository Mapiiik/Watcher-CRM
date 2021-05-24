ALTER TABLE radcheck
ADD COLUMN type integer NOT NULL DEFAULT 0,
ADD COLUMN customer_id integer NOT NULL,
ADD COLUMN contract_id integer NOT NULL,
ADD COLUMN created timestamp NOT NULL DEFAULT now(),
ADD COLUMN created_by integer NOT NULL DEFAULT 0,
ADD COLUMN modified  timestamp,
ADD COLUMN modified_by integer;
