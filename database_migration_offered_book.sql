USE book_exchange_system;

ALTER TABLE book_exchange
    ADD COLUMN offered_book_id INT DEFAULT NULL AFTER user_id;
