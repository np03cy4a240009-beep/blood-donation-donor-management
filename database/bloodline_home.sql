-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 10, 2026 at 04:37 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bloodline_home`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` varchar(20) NOT NULL,
  `location` varchar(150) NOT NULL,
  `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `appointment_date`, `appointment_time`, `location`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, '2026-04-06', '3 PM', 'Bir Hospital', 'confirmed', NULL, '2026-04-06 01:13:30', '2026-04-08 19:04:42'),
(2, 4, '2026-04-11', '10 AM', 'Bir Hospital', 'pending', NULL, '2026-04-08 19:11:08', '2026-04-08 19:11:08');

-- --------------------------------------------------------

--
-- Table structure for table `blood_inventory`
--

CREATE TABLE `blood_inventory` (
  `id` int(10) UNSIGNED NOT NULL,
  `donor_id` int(10) UNSIGNED NOT NULL,
  `unit_id` varchar(50) NOT NULL,
  `blood_type` varchar(30) NOT NULL,
  `collection_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('Available','expired','unsafe','reserved') NOT NULL DEFAULT 'Available',
  `special_note` text DEFAULT NULL,
  `screening_status` enum('pending','tested','safe','unsafe') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blood_requests`
--

CREATE TABLE `blood_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_id` varchar(50) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `hospital_name` varchar(150) NOT NULL,
  `contact` varchar(50) NOT NULL,
  `location` varchar(150) NOT NULL,
  `blood_type` varchar(30) NOT NULL,
  `units` int(11) NOT NULL,
  `urgency` enum('Normal','Urgent') NOT NULL DEFAULT 'Normal',
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `request_date` date NOT NULL,
  `required_by` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blood_requests`
--

INSERT INTO `blood_requests` (`id`, `request_id`, `user_id`, `hospital_name`, `contact`, `location`, `blood_type`, `units`, `urgency`, `status`, `request_date`, `required_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'RID177543791029', 2, 'Darpan', '9742995242', 'Lalitpur', 'A+', 4, 'Urgent', 'approved', '2026-04-06', '2026-04-07', NULL, '2026-04-06 01:11:50', '2026-04-08 19:04:20');

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(191) NOT NULL,
  `otp_code` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`id`, `email`, `otp_code`, `expires_at`, `created_at`) VALUES
(1, 'darpangc01@gmail.com', '$2y$10$HvpW3PHV9NFQWkBqm08waOe1a3X.IaWA0UuNIxgam.B4YsCTrV2EC', '2026-04-08 21:15:13', '2026-04-08 19:05:13');

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

CREATE TABLE `tests` (
  `id` int(10) UNSIGNED NOT NULL,
  `inventory_id` int(10) UNSIGNED NOT NULL,
  `status` enum('Tested','Safe','Approved','Unsafe') NOT NULL DEFAULT 'Tested',
  `hiv_result` enum('negative','positive','pending') NOT NULL DEFAULT 'pending',
  `hepatitis_b_result` enum('negative','positive','pending') NOT NULL DEFAULT 'pending',
  `hepatitis_c_result` enum('negative','positive','pending') NOT NULL DEFAULT 'pending',
  `syphilis_result` enum('negative','positive','pending') NOT NULL DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `tested_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `full_name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL,
  `hospital_name` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `blood_group` varchar(30) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `eligibility_status` enum('eligible','temporarily deferred','not eligible') NOT NULL DEFAULT 'eligible',
  `total_donation` int(11) NOT NULL DEFAULT 0,
  `last_donated` date DEFAULT NULL,
  `next_eligible_date` date DEFAULT NULL,
  `hemoglobin` decimal(4,2) DEFAULT NULL,
  `pulse` int(11) DEFAULT NULL,
  `systolic_bp` int(11) DEFAULT NULL,
  `diastolic_bp` int(11) DEFAULT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `pregnancy_status` tinyint(1) NOT NULL DEFAULT 0,
  `breastfeeding_status` tinyint(1) NOT NULL DEFAULT 0,
  `menstruation_status` tinyint(1) NOT NULL DEFAULT 0,
  `recent_illness_status` tinyint(1) NOT NULL DEFAULT 0,
  `recent_surgery_status` tinyint(1) NOT NULL DEFAULT 0,
  `recent_surgery_date` date DEFAULT NULL,
  `recent_vaccination_status` tinyint(1) NOT NULL DEFAULT 0,
  `recent_vaccination_date` date DEFAULT NULL,
  `antibiotics_status` tinyint(1) NOT NULL DEFAULT 0,
  `tattoo_piercing_status` tinyint(1) NOT NULL DEFAULT 0,
  `tattoo_piercing_date` date DEFAULT NULL,
  `malaria_travel_status` tinyint(1) NOT NULL DEFAULT 0,
  `recent_transfusion_status` tinyint(1) NOT NULL DEFAULT 0,
  `alcohol_status` tinyint(1) NOT NULL DEFAULT 0,
  `drug_use_status` tinyint(1) NOT NULL DEFAULT 0,
  `unsafe_sexual_behavior_status` tinyint(1) NOT NULL DEFAULT 0,
  `hiv_status` tinyint(1) NOT NULL DEFAULT 0,
  `hepatitis_b_status` tinyint(1) NOT NULL DEFAULT 0,
  `hepatitis_c_status` tinyint(1) NOT NULL DEFAULT 0,
  `syphilis_status` tinyint(1) NOT NULL DEFAULT 0,
  `cancer_status` tinyint(1) NOT NULL DEFAULT 0,
  `heart_disease_status` tinyint(1) NOT NULL DEFAULT 0,
  `liver_disease_status` tinyint(1) NOT NULL DEFAULT 0,
  `kidney_disease_status` tinyint(1) NOT NULL DEFAULT 0,
  `thalassemia_status` tinyint(1) NOT NULL DEFAULT 0,
  `hemophilia_status` tinyint(1) NOT NULL DEFAULT 0,
  `severe_diabetes_status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `full_name`, `email`, `password`, `hospital_name`, `phone`, `age`, `weight`, `gender`, `address`, `city`, `state`, `zip_code`, `blood_group`, `medical_history`, `eligibility_status`, `total_donation`, `last_donated`, `next_eligible_date`, `hemoglobin`, `pulse`, `systolic_bp`, `diastolic_bp`, `temperature`, `pregnancy_status`, `breastfeeding_status`, `menstruation_status`, `recent_illness_status`, `recent_surgery_status`, `recent_surgery_date`, `recent_vaccination_status`, `recent_vaccination_date`, `antibiotics_status`, `tattoo_piercing_status`, `tattoo_piercing_date`, `malaria_travel_status`, `recent_transfusion_status`, `alcohol_status`, `drug_use_status`, `unsafe_sexual_behavior_status`, `hiv_status`, `hepatitis_b_status`, `hepatitis_c_status`, `syphilis_status`, `cancer_status`, `heart_disease_status`, `liver_disease_status`, `kidney_disease_status`, `thalassemia_status`, `hemophilia_status`, `severe_diabetes_status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'System Admin', 'admin@bloodline.com', '$2y$10$iWxjEtSEQmzqMuGnzeHsXuvLqKIm1ZbwlP3cKszPzTNIxsz0NkhAO', 'BloodLine Home Admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'eligible', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, 0, 0, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-04-06 01:04:23', '2026-04-07 17:00:14'),
(2, 'user', 'Darpan G.C.', 'darpangc01@gmail.com', '$2y$10$EriSegOozKmJ95wMbDqghOKB6twPOUhk/tGzHIVTQEW9z/QUMLecq', '', '9742995242', 20, 52.00, 'Male', 'Masuriya-03, Dang', 'Dang', '', '23132', '', 'Healthy', 'eligible', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, 0, 0, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-04-06 01:06:20', '2026-04-06 01:06:46'),
(4, 'user', 'kapil', 'kapil01@gmail.com', '$2y$10$oAHkoGIs3FK7OoJasTynb.Jb.5Np5zi8uLWTw9ZQBAvNP6hkwjVo.', '', '9777777777', 31, 98.00, 'Male', 'adcas', 'sCAS', 'CS', '24242', 'AB+', 'ZCC', 'eligible', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, 0, 0, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-04-08 19:08:05', '2026-04-08 19:08:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appointments_user_id` (`user_id`),
  ADD KEY `idx_appointments_date` (`appointment_date`),
  ADD KEY `idx_appointments_status` (`status`);

--
-- Indexes for table `blood_inventory`
--
ALTER TABLE `blood_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_blood_inventory_unit_id` (`unit_id`),
  ADD KEY `idx_blood_inventory_donor_id` (`donor_id`),
  ADD KEY `idx_blood_inventory_blood_type` (`blood_type`),
  ADD KEY `idx_blood_inventory_status` (`status`),
  ADD KEY `idx_blood_inventory_expiry_date` (`expiry_date`);

--
-- Indexes for table `blood_requests`
--
ALTER TABLE `blood_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_blood_requests_request_id` (`request_id`),
  ADD KEY `idx_blood_requests_user_id` (`user_id`),
  ADD KEY `idx_blood_requests_blood_type` (`blood_type`),
  ADD KEY `idx_blood_requests_status` (`status`),
  ADD KEY `idx_blood_requests_urgency` (`urgency`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_otp_email` (`email`),
  ADD KEY `idx_otp_expires_at` (`expires_at`);

--
-- Indexes for table `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tests_inventory_id` (`inventory_id`),
  ADD KEY `idx_tests_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_blood_group` (`blood_group`),
  ADD KEY `idx_users_eligibility_status` (`eligibility_status`),
  ADD KEY `idx_users_city` (`city`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `blood_inventory`
--
ALTER TABLE `blood_inventory`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blood_requests`
--
ALTER TABLE `blood_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `blood_inventory`
--
ALTER TABLE `blood_inventory`
  ADD CONSTRAINT `fk_blood_inventory_donor` FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `blood_requests`
--
ALTER TABLE `blood_requests`
  ADD CONSTRAINT `fk_blood_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tests`
--
ALTER TABLE `tests`
  ADD CONSTRAINT `fk_tests_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `blood_inventory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
