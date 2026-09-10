# Files plugin for Watcher CRM

Keeps what documents are made of: the bytes once, and a row for every record
that has a use for them.

Two tables. `files` is the content, addressed by the hash of what is in it, so
the same bytes are stored once however many records point at them. The
algorithm is a column of its own rather than the name of one, so changing it is
a migration of the rows and not of the schema.

`file_links` is a use of that content: which record has it (`model` and
`foreign_key`), which document it is (`document_type`), which variant of that
document this is (`variant`), and where a document runs to several pages, which
page this one is (`position`).

A variant rather than a state: the blank copy, the one we signed and the one
that came back are files standing side by side, not stages of one, and nothing
ever moves from being one to being another.

The bytes themselves go through `league/flysystem`, so moving them off local
disk later is a change of adapter rather than a change of code.

What a file *means* is the application's business, not this plugin's. The
values in `document_type` and `variant` are the application's to choose; here
they are only strings.
