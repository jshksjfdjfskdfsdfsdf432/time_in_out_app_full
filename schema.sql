
CREATE DATABASE IF NOT EXISTS time_app;
USE time_app;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    pin VARCHAR(10)
);

CREATE TABLE IF NOT EXISTS attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    time_in DATETIME,
    time_out DATETIME,
    latitude_in VARCHAR(50),
    longitude_in VARCHAR(50),
    photo_in VARCHAR(255),
    latitude_out VARCHAR(50),
    longitude_out VARCHAR(50),
    photo_out VARCHAR(255),
    FOREIGN KEY (worker_id) REFERENCES workers(id)
);

INSERT INTO admins (username, password) VALUES
('admin', '$2y$10$B1sUgHhMEotP3ZSkCekAeOwUrC/axL3nK8rz8uRyQaXuk38c5rKiW');
