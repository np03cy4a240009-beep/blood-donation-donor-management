CREATE DATABASE IF NOT EXISTS bloodline_home
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE bloodline_home;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

DROP TABLE IF EXISTS otp_codes;
DROP TABLE IF EXISTS tests;
DROP TABLE IF EXISTS blood_inventory;
DROP TABLE IF EXISTS blood_requests;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL,
    password VARCHAR(255) NOT NULL,

    hospital_name VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,

    age INT DEFAULT NULL,
    weight DECIMAL(5,2) DEFAULT NULL,
    gender ENUM('Male', 'Female', 'Other') DEFAULT NULL,

    address VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    province VARCHAR(100) DEFAULT NULL,
    zip_code VARCHAR(20) DEFAULT NULL,

    blood_group VARCHAR(30) DEFAULT NULL,
    medical_history TEXT DEFAULT NULL,

    eligibility_status ENUM('eligible', 'temporarily deferred', 'not eligible') NOT NULL DEFAULT 'eligible',
    total_donation INT NOT NULL DEFAULT 0,
    last_donated DATE DEFAULT NULL,
    next_eligible_date DATE DEFAULT NULL,

    hemoglobin DECIMAL(4,2) DEFAULT NULL,
    pulse INT DEFAULT NULL,
    systolic_bp INT DEFAULT NULL,
    diastolic_bp INT DEFAULT NULL,
    temperature DECIMAL(4,2) DEFAULT NULL,

    pregnancy_status TINYINT(1) NOT NULL DEFAULT 0,
    breastfeeding_status TINYINT(1) NOT NULL DEFAULT 0,
    menstruation_status TINYINT(1) NOT NULL DEFAULT 0,

    recent_illness_status TINYINT(1) NOT NULL DEFAULT 0,
    recent_surgery_status TINYINT(1) NOT NULL DEFAULT 0,
    recent_surgery_date DATE DEFAULT NULL,

    recent_vaccination_status TINYINT(1) NOT NULL DEFAULT 0,
    recent_vaccination_date DATE DEFAULT NULL,

    antibiotics_status TINYINT(1) NOT NULL DEFAULT 0,
    tattoo_piercing_status TINYINT(1) NOT NULL DEFAULT 0,
    tattoo_piercing_date DATE DEFAULT NULL,

    malaria_travel_status TINYINT(1) NOT NULL DEFAULT 0,
    recent_transfusion_status TINYINT(1) NOT NULL DEFAULT 0,

    alcohol_status TINYINT(1) NOT NULL DEFAULT 0,
    drug_use_status TINYINT(1) NOT NULL DEFAULT 0,
    unsafe_sexual_behavior_status TINYINT(1) NOT NULL DEFAULT 0,

    hiv_status TINYINT(1) NOT NULL DEFAULT 0,
    hepatitis_b_status TINYINT(1) NOT NULL DEFAULT 0,
    hepatitis_c_status TINYINT(1) NOT NULL DEFAULT 0,
    syphilis_status TINYINT(1) NOT NULL DEFAULT 0,

    cancer_status TINYINT(1) NOT NULL DEFAULT 0,
    heart_disease_status TINYINT(1) NOT NULL DEFAULT 0,
    liver_disease_status TINYINT(1) NOT NULL DEFAULT 0,
    kidney_disease_status TINYINT(1) NOT NULL DEFAULT 0,
    thalassemia_status TINYINT(1) NOT NULL DEFAULT 0,
    hemophilia_status TINYINT(1) NOT NULL DEFAULT 0,
    severe_diabetes_status TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_blood_group (blood_group),
    KEY idx_users_eligibility_status (eligibility_status),
    KEY idx_users_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE appointments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time VARCHAR(20) NOT NULL,
    location VARCHAR(150) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_appointments_user_id (user_id),
    KEY idx_appointments_date (appointment_date),
    KEY idx_appointments_status (status),
    CONSTRAINT fk_appointments_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE blood_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id VARCHAR(50) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    hospital_name VARCHAR(150) NOT NULL,
    contact VARCHAR(50) NOT NULL,
    location VARCHAR(150) NOT NULL,
    blood_type VARCHAR(30) NOT NULL,
    units INT NOT NULL,
    urgency ENUM('Normal', 'Urgent') NOT NULL DEFAULT 'Normal',
    status ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
    request_date DATE NOT NULL,
    required_by DATE NOT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_blood_requests_request_id (request_id),
    KEY idx_blood_requests_user_id (user_id),
    KEY idx_blood_requests_blood_type (blood_type),
    KEY idx_blood_requests_status (status),
    KEY idx_blood_requests_urgency (urgency),
    CONSTRAINT fk_blood_requests_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE blood_inventory (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    donor_id INT UNSIGNED NOT NULL,
    unit_id VARCHAR(50) NOT NULL,
    blood_type VARCHAR(30) NOT NULL,
    collection_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('Available', 'expired', 'unsafe', 'reserved') NOT NULL DEFAULT 'Available',
    special_note TEXT DEFAULT NULL,
    screening_status ENUM('pending', 'tested', 'safe', 'unsafe') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_blood_inventory_unit_id (unit_id),
    KEY idx_blood_inventory_donor_id (donor_id),
    KEY idx_blood_inventory_blood_type (blood_type),
    KEY idx_blood_inventory_status (status),
    KEY idx_blood_inventory_expiry_date (expiry_date),
    CONSTRAINT fk_blood_inventory_donor
        FOREIGN KEY (donor_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE tests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    inventory_id INT UNSIGNED NOT NULL,
    status ENUM('Tested', 'Safe', 'Approved', 'Unsafe') NOT NULL DEFAULT 'Tested',
    hiv_result ENUM('negative', 'positive', 'pending') NOT NULL DEFAULT 'pending',
    hepatitis_b_result ENUM('negative', 'positive', 'pending') NOT NULL DEFAULT 'pending',
    hepatitis_c_result ENUM('negative', 'positive', 'pending') NOT NULL DEFAULT 'pending',
    syphilis_result ENUM('negative', 'positive', 'pending') NOT NULL DEFAULT 'pending',
    remarks TEXT DEFAULT NULL,
    tested_at TIMESTAMP NULL DEFAULT NULL,
    approved_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_tests_inventory_id (inventory_id),
    KEY idx_tests_status (status),
    CONSTRAINT fk_tests_inventory
        FOREIGN KEY (inventory_id) REFERENCES blood_inventory(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE otp_codes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(191) NOT NULL,
    otp_code VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_otp_email (email),
    KEY idx_otp_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (
    role, full_name, email, password, hospital_name, eligibility_status
) VALUES (
    'admin',
    'System Admin',
    'admin@bloodline.com',
    '$2y$10$wH1D2H8m1Q5nD1YlH7D8Xu3JY2G7G4rJ6LQx7l2fQvI6C4C9A7g1m',
    'BloodLine Home Admin',
    'eligible'
);

COMMIT;