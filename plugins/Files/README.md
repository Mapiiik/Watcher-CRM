# Files plugin for Watcher CRM

Keeps what documents are made of: the bytes once, and a row for every record
that has a use for them.

Two tables. `files` is the content, addressed by the SHA-256 of what is in it,
so the same bytes are stored once however many records point at them.
`file_links` is a use of that content - which record has it, what document it
is, whose signatures it carries and, where a document runs to several pages,
which page this one is.

The bytes themselves go through `league/flysystem`, so moving them off local
disk later is a change of adapter rather than a change of code.

What a file *means* is the application's business, not this plugin's. The
values in `document_type` and `role` are the application's to choose; here they
are only strings.
