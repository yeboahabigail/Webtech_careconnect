-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2024 at 12:00 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Ensure the database exists and select it
CREATE DATABASE IF NOT EXISTS hospital_management;
USE hospital_management;

CREATE TABLE users (
  user_id int NOT NULL AUTO_INCREMENT,
  fname varchar(50) NOT NULL,
  lname varchar(50) NOT NULL,
  email varchar(100) NOT NULL UNIQUE,
  password varchar(255) NOT NULL,
  role enum('admin', 'user') NOT NULL DEFAULT 'user',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE hospitals (
  hospital_id int NOT NULL AUTO_INCREMENT,
  name varchar(100) NOT NULL,
  region varchar(100) NOT NULL,
  location varchar(255) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (hospital_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE doctors (
  doctor_id int NOT NULL AUTO_INCREMENT,
  hospital_id int NOT NULL,
  name varchar(100) NOT NULL,
  specialty varchar(100) NOT NULL,
  availability tinyint(1) NOT NULL DEFAULT 1,
  schedule text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (doctor_id),
  FOREIGN KEY (hospital_id) REFERENCES hospitals (hospital_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create AdminActions Table
CREATE TABLE AdminActions (
    action_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type ENUM('add', 'update', 'delete') NOT NULL,
    action_description TEXT,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES Users(user_id)
);

-- --------------------------------------------------------

CREATE TABLE appointments (
  appointment_id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  doctor_id int NOT NULL,
  appointment_date date NOT NULL,
  appointment_time time NOT NULL,
  status enum('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (appointment_id),
  FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES doctors (doctor_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE beds (
  bed_id int NOT NULL AUTO_INCREMENT,
  hospital_id int NOT NULL,
  is_available tinyint(1) NOT NULL DEFAULT 1,
  assigned_to int DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (bed_id),
  FOREIGN KEY (hospital_id) REFERENCES hospitals (hospital_id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_to) REFERENCES users (user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE notifications (
  notification_id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  content text NOT NULL,
  is_read tinyint(1) NOT NULL DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (notification_id),
  FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE analytics (
  analytics_id int NOT NULL AUTO_INCREMENT,
  metric varchar(50) NOT NULL,
  value int NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (analytics_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Removing the integer display width (e.g., int(11)) for all relevant columns
ALTER TABLE doctors 
  MODIFY doctor_id int NOT NULL AUTO_INCREMENT;

ALTER TABLE appointments
  MODIFY appointment_id int NOT NULL AUTO_INCREMENT;

ALTER TABLE beds
  MODIFY bed_id int NOT NULL AUTO_INCREMENT;

ALTER TABLE notifications
  MODIFY notification_id int NOT NULL AUTO_INCREMENT;

ALTER TABLE analytics
  MODIFY analytics_id int NOT NULL AUTO_INCREMENT;

COMMIT;