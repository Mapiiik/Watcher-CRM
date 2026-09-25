# RADIUS plugin for Watcher CRM

Customer accounts, their sessions and the addresses they get, kept in the
FreeRADIUS database. Part of Watcher CRM, not a standalone package.

- `config/ManualMigrations/` - the RADIUS database schema (master and slave
  for two servers replicated by Bucardo) and changes to it that are applied by
  hand.
- `config/bucardo.radacct.acctuniqueid.exception.pl` - resolves `acctuniqueid`
  conflicts in the Bucardo sync.

The application never deletes sessions (`radacct`) or authentication records
(`radpostauth`). How they are kept to their retention period is described in
`docs/2026-radius-data-retention.txt`.
