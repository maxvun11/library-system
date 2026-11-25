# Library Exchange System

Run the project with WampServer and import `database.sql` for a fresh database.

For an existing `book_exchange_system` database, run `database_migration_offered_book.sql` once to add the selected-offer field while preserving existing accounts and books.

Books uploaded from the User page are listed in the catalog immediately. Exchange requests use a book already listed by the requester; the requested book's owner can review the offer and accept or decline it from their User page.

Purchasing a cart item marks it as sold, removes it from the catalog, and clears exchange requests involving that book.
