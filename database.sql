CREATE DATABASE Book_Exchange;

USE Book_Exchange;

CREATE TABLE users ( 
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE, 
    password VARCHAR(255) NOT NULL, 
    email VARCHAR(100) UNIQUE NOT NULL, 
    account_type ENUM('Standard', 'Admin') DEFAULT 'Standard',
    posted DATETIME
);