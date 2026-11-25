CREATE DATABASE IF NOT EXISTS book_exchange_system;
USE book_exchange_system;

CREATE TABLE IF NOT EXISTS `user` (
    user_id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) DEFAULT NULL UNIQUE,
    PRIMARY KEY (user_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS book (
    book_id INT NOT NULL AUTO_INCREMENT,
    book_title VARCHAR(255) NOT NULL,
    book_author VARCHAR(255) DEFAULT NULL,
    book_age VARCHAR(50) DEFAULT NULL,
    book_price DECIMAL(10,2) DEFAULT NULL,
    book_summary TEXT,
    book_condition VARCHAR(100) DEFAULT NULL,
    book_rate DECIMAL(10,2) DEFAULT NULL,
    upload_status VARCHAR(50) DEFAULT NULL,
    user_id INT DEFAULT NULL,
    PRIMARY KEY (book_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS book_exchange (
    exchange_id INT NOT NULL AUTO_INCREMENT,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    offered_book_id INT DEFAULT NULL,
    PRIMARY KEY (exchange_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
