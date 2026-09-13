# The tables the documentation wants

`Files\Model\Table\DocumentationsTable` and `DocumentationTypesTable` read two tables this plugin
does not create. Both samples here are what they expect. Copy them into your own
`config/Migrations/`, under names beginning with a timestamp:

```bash
cp plugins/Files/config/ExampleMigrations/CreateDocumentationTypes.php.example \
   config/Migrations/20260913090000_CreateDocumentationTypes.php
cp plugins/Files/config/ExampleMigrations/CreateDocumentations.php.example \
   config/Migrations/20260913090100_CreateDocumentations.php
```

## Why they are not the plugin's own

The plugin's migrations run after the application's, and they have to: an application reshapes
`users` after the users plugin has created it, and a table of ours with a foreign key to `users`
would stand in the way of that. So the plugin cannot go first — and an application cannot add a
column to a table that is not there yet. The way out is the one the tasks took: the schema in the
application, the code that reads it here.

Which is just as well, because the second table names a record only the application knows.

## What to change

In `CreateDocumentations`, the column a piece of documentation hangs on. The sample hangs it on an
access point. Give it whatever your routes nest under, keep the foreign key and the index beside
it, and leave it nullable.

In `CreateDocumentationTypes`, one `*_required` flag for each of those columns, so that the
operator can say which kinds of documentation have to be filed under what. The names follow the
family the task types use.

Then, in the application:

- a `DocumentationsTable` extending the plugin's, declaring `belongsTo` for the column and
  returning the requirement in `askedFor()` — the key is the flag, the value is the column and
  what to say when it is missing
- a `DocumentationTypesTable` extending the plugin's, validating the flags
- entities for both, listing the new columns among the ones that may be assigned
- a `DocumentationsController` and a `DocumentationTypesController` over the traits in
  `Files\Controller\Trait`

The column is best named after the route parameter it comes from. Then the keys fill themselves
in from the address the form was opened at, and nothing has to be mapped from one to the other.
