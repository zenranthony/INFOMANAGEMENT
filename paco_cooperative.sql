-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2026 at 04:19 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `paco_cooperative`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) UNSIGNED NOT NULL,
  `member_id` int(11) UNSIGNED NOT NULL,
  `account_number` varchar(30) NOT NULL,
  `account_type` varchar(30) NOT NULL,
  `balance` decimal(15,2) NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `member_id`, `account_number`, `account_type`, `balance`, `status`, `created_at`) VALUES
(1, 1, 'SAV-2026-000001', 'Savings', 0.00, 'active', '2026-09-15 08:59:49'),
(2, 2, 'SAV-2026-000002', 'Savings', 0.00, 'active', '2026-09-15 09:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `account_requests`
--

CREATE TABLE `account_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` int(10) UNSIGNED NOT NULL,
  `account_type` varchar(120) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(160) NOT NULL,
  `body` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `published_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('published','archived') NOT NULL DEFAULT 'published',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL DEFAULT 'success',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `status`, `created_at`) VALUES
(1, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-15 07:33:41'),
(2, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-15 07:52:27'),
(3, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-15 07:56:46'),
(4, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-15 07:57:50'),
(5, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-15 08:47:55'),
(6, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-15 08:48:01'),
(7, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-15 08:48:13'),
(8, 1, 'membership_review', 'Updated registration application status to scheduled', '::1', 'success', '2026-09-15 08:57:13'),
(9, 1, 'membership_review', 'Updated registration application status to pmes_completed', '::1', 'success', '2026-09-15 08:57:47'),
(10, 1, 'membership_review', 'Updated registration application status to approved', '::1', 'success', '2026-09-15 08:59:49'),
(11, 1, 'membership_review', 'Updated registration application status to approved', '::1', 'success', '2026-09-15 09:08:08'),
(12, 1, 'pmes_completed', 'Marked PMES as completed for application APP-2026-63F416', '::1', 'success', '2026-09-15 09:16:38'),
(13, 1, 'membership_review', 'Updated registration application status to rejected', '::1', 'success', '2026-09-15 09:17:48'),
(14, 1, 'membership_review', 'Updated registration application status to approved', '::1', 'success', '2026-09-15 09:17:53'),
(15, 1, 'membership_review', 'Updated registration application status to approved', '::1', 'success', '2026-09-15 09:43:10'),
(16, 1, 'membership_review', 'Updated registration application status to approved', '::1', 'success', '2026-09-15 09:43:20'),
(17, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-15 14:01:52'),
(18, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-15 14:02:13'),
(19, 1, 'membership_review', 'Updated registration application status to scheduled', '::1', 'success', '2026-09-15 14:05:09'),
(20, 1, 'pmes_completed', 'Marked PMES as completed for application APP-2026-5825A4', '::1', 'success', '2026-09-15 14:05:17'),
(21, 1, 'resend_member_credentials', 'Generated a new temporary password and resent login credentials for application APP-2026-63F416', '::1', 'success', '2026-09-15 14:19:45'),
(22, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-15 14:32:06'),
(23, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-15 14:32:38'),
(24, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-15 14:32:50'),
(25, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-15 14:34:37'),
(26, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-15 14:39:18'),
(27, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-15 14:48:30'),
(28, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-15 14:48:40'),
(29, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-15 14:51:02'),
(30, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-15 14:51:14'),
(31, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-15 14:55:41'),
(32, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-15 14:55:52'),
(33, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-15 14:59:10'),
(34, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-15 14:59:17'),
(35, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-15 15:00:23'),
(36, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-15 15:00:35'),
(37, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-15 15:00:42'),
(38, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-16 14:06:58'),
(39, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-16 14:07:04');

-- --------------------------------------------------------

--
-- Table structure for table `auth_throttle`
--

CREATE TABLE `auth_throttle` (
  `bucket` char(64) NOT NULL,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `window_started` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `auth_throttle`
--

INSERT INTO `auth_throttle` (`bucket`, `attempts`, `window_started`) VALUES
('0dec399fb0f5d09ed0313b16c963cfd3b60b1de7ff7bf0aa43683b47ea842e1b', 1, 1789484423),
('164f437932afb830b189668b8f813ddc9e6567a0b8fe850271bc369a4661672e', 1, 1789567623),
('198286edff5123d5e59ea8a2b10bfdfbc9cb3f69ba159d1b44726957b6ed85db', 2, 1789484152),
('6646fe8bd738e49e009c0984738f8ecf0755879406a7b0bdcb5b68db3fd9efa0', 2, 1789484141),
('6d96e5b3d0d28d477a78c296b9e98b4eed80f70121f8a6abd57fbcff1794a4f1', 1, 1789567610),
('80347c8c68360d3d86e4a8d34a9acae9921321b75c4c59741ead9e70ed873523', 1, 1789567623),
('894d5b5cb309107d0fe3419e68fd7c5bb7ea29177ec5e667c1555c57cd324035', 1, 1789567610),
('8cb6770c76892c5f44c561d7944231905bfd548ee3214b4e445b9380e3733c8e', 1, 1789457621),
('c0f6723051fbf74bd9ea787a1d38ba8cc89862f139aaf6f26935d44369aff818', 2, 1789484152),
('fe6ae851f763471a787d9851fa0dfaf3694f2df9fc2869a6445f5a86758427fb', 1, 1789458747);

-- --------------------------------------------------------

--
-- Table structure for table `deposit_requests`
--

CREATE TABLE `deposit_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `account_id` int(10) UNSIGNED NOT NULL,
  `member_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(40) NOT NULL,
  `payment_reference` varchar(120) NOT NULL,
  `description` varchar(255) NOT NULL,
  `receipt_path` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` varchar(500) DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `loan_number` varchar(30) NOT NULL,
  `loan_product` varchar(100) DEFAULT NULL,
  `loan_amount` decimal(15,2) NOT NULL,
  `remaining_balance` decimal(15,2) NOT NULL,
  `status` enum('pending','approved','rejected','paid') NOT NULL,
  `application_date` date NOT NULL,
  `loan_term` varchar(20) DEFAULT NULL,
  `loan_purpose` text DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `employer` varchar(150) DEFAULT NULL,
  `monthly_income` decimal(15,2) DEFAULT NULL,
  `id_type` varchar(50) DEFAULT NULL,
  `id_number` varchar(100) DEFAULT NULL,
  `id_document_path` varchar(255) DEFAULT NULL,
  `proof_income_path` varchar(255) DEFAULT NULL,
  `identity_status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `identity_verified_by` int(10) UNSIGNED DEFAULT NULL,
  `identity_verified_at` datetime DEFAULT NULL,
  `identity_notes` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `member_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `user_id`, `member_number`, `first_name`, `last_name`, `email`, `phone`, `created_at`) VALUES
(1, 2, 'PAS-2026-00002', 'Maria', 'Santos', 'renzpizza172@gmail.com', '09999313521', '2026-09-15 08:59:49'),
(2, 3, 'PAS-2026-00003', 'Francis', 'Balsy', 'renzpizza172@gmail.com', '0999 931 3521', '2026-09-15 09:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `membership_applications`
--

CREATE TABLE `membership_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `member_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `preferred_mode` enum('face_to_face','online','either') DEFAULT NULL,
  `application_reference` varchar(30) DEFAULT NULL,
  `preferred_schedule_date` date DEFAULT NULL,
  `pmes_schedule` varchar(100) DEFAULT NULL,
  `pmes_mode` enum('face_to_face','online') DEFAULT NULL,
  `pmes_location` varchar(255) DEFAULT NULL,
  `pmes_link` varchar(255) DEFAULT NULL,
  `certificate_path` varchar(255) DEFAULT NULL,
  `id_picture_path` varchar(255) DEFAULT NULL,
  `barangay_certificate_path` varchar(255) DEFAULT NULL,
  `birth_certificate_path` varchar(255) DEFAULT NULL,
  `government_id_path` varchar(255) DEFAULT NULL,
  `proof_billing_path` varchar(255) DEFAULT NULL,
  `tin_document_path` varchar(255) DEFAULT NULL,
  `valid_id_path` varchar(255) DEFAULT NULL,
  `proof_address_path` varchar(255) DEFAULT NULL,
  `document_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`document_paths`)),
  `status` enum('pending_schedule','scheduled','pmes_completed','documents_submitted','under_review','approved','rejected') NOT NULL DEFAULT 'pending_schedule',
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `membership_applications`
--

INSERT INTO `membership_applications` (`id`, `user_id`, `first_name`, `last_name`, `member_name`, `email`, `phone`, `address`, `preferred_mode`, `application_reference`, `preferred_schedule_date`, `pmes_schedule`, `pmes_mode`, `pmes_location`, `pmes_link`, `certificate_path`, `id_picture_path`, `barangay_certificate_path`, `birth_certificate_path`, `government_id_path`, `proof_billing_path`, `tin_document_path`, `valid_id_path`, `proof_address_path`, `document_paths`, `status`, `notes`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 2, 'Maria', 'Santos', 'Maria Santos', 'renzpizza172@gmail.com', '09999313521', 'Manila', 'face_to_face', 'APP-2026-B1BFCF', NULL, '2026-09-20 10:00', 'online', NULL, 'https://meet.google.com/test-link', 'uploads/membership_documents/1dcce49435b4b08e75dca22e86e4d74a0a77733d.jpg', 'uploads/membership_documents/a0249a6871d5335f9b7355125a792d687cd6a0d2.jpg', 'uploads/membership_documents/6eea2cb892ae077f5aa879bc64151b84908421e7.jpg', 'uploads/membership_documents/7857ddd4894849d954fc26e6a0795a921a74d6f1.jpg', 'uploads/membership_documents/99f432ecb2fa56d4707d7ccf0971c45643a76966.jpg', 'uploads/membership_documents/b0f295e385b1c0079e1fd415ed0ff44772fd87b5.jpg', 'uploads/membership_documents/d0b9c3369f7e8dfb879e98e9c1afd5225a86daf8.jpg', NULL, NULL, NULL, 'approved', NULL, '', '2026-09-15 08:47:24', '2026-09-15 08:59:49'),
(2, 3, 'Francis', 'Balsy', 'Francis Balsy', 'renzpizza172@gmail.com', '0999 931 3521', 'Manila', 'face_to_face', 'APP-2026-63F416', NULL, '2026-09-21 18:15', 'face_to_face', 'BRGY. 29 Basketball Court', NULL, 'uploads/membership_documents/aa79d5bb7e91f5e590a8f23571c11f7d8fb72a99.jpg', 'uploads/membership_documents/747609b0d84d9c5a2ecf8a20397e0f7828c83e79.jpg', 'uploads/membership_documents/bb40d879e2de448de2cd643c0d020175996af036.jpg', 'uploads/membership_documents/aeb561e31f644040695eb35758d311207f12e930.jpg', 'uploads/membership_documents/f26a04edf077706cae3aa6e38fe3e1b6cc3d3840.jpg', 'uploads/membership_documents/b5cdccbf033b8e2f92fcac06e8520974f7e7a7f4.jpg', 'uploads/membership_documents/d38b2ddf4e6da5b2bd586f82982d107d17f49d64.jpg', NULL, NULL, NULL, 'approved', NULL, '', '2026-09-15 09:11:06', '2026-09-15 09:17:53'),
(3, NULL, 'Kim', 'Generoso', 'Kim Generoso', 'renzpizza172@gmail.com', '09123456789', 'Tondo', 'face_to_face', 'APP-2026-5825A4', NULL, '2026-09-22 22:00', 'face_to_face', 'BRGY. 29 Basketball Court', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pmes_completed', NULL, '', '2026-09-15 14:03:05', '2026-09-15 14:05:17');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `transaction_type` enum('deposit','withdrawal','transfer') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `recipient_account_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `status` enum('pending','completed','failed') NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `transaction_date` int(11) NOT NULL,
  `created_at` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('member','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') NOT NULL,
  `otp_code` varchar(255) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `otp_attempts` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `status`, `otp_code`, `otp_expires_at`, `otp_attempts`) VALUES
(1, 'admin_juan', 'admin@pascco.local', '$2y$10$qbbFCYFpkoXS/PdnWdym.OVEgBwR8dEIAwMJ66JWrSfdo5/1SCJyW', 'admin', '2026-09-15 07:56:34', 'active', NULL, NULL, 0),
(2, 'maria_santos', 'renzpizza172@gmail.com', '$2y$10$goT3ROzNj1F7BMXVsYflJO/GvovYR6VMJVNe99HmWjjbXs85gE9Um', 'member', '2026-09-15 08:59:49', 'active', NULL, NULL, 0),
(3, 'francis_balsy', 'renzpizza172@gmail.com', '$2y$10$IQeH.6vXfccPALXT5uHkUu.OT6Prob1R/rNXeED0ljsqqLg/NyiwS', 'member', '2026-09-16 14:07:04', 'active', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_mfa`
--

CREATE TABLE `user_mfa` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `secret` varchar(255) NOT NULL,
  `last_step` bigint(20) NOT NULL DEFAULT -1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_mfa`
--

INSERT INTO `user_mfa` (`user_id`, `secret`, `last_step`) VALUES
(1, 'NEg0LraKqcx+3v2WOMXXNONVYHNodlId15kX1a3x+ryPlE3StAvdsSBryV1AjDO4yG9P/EQA0g5zlnc4', 59649478);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_number` (`account_number`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `account_requests`
--
ALTER TABLE `account_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_account_requests_status` (`status`,`requested_at`),
  ADD KEY `fk_account_requests_member` (`member_id`),
  ADD KEY `fk_account_requests_reviewer` (`reviewed_by`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcements_status_date` (`status`,`published_at`),
  ADD KEY `fk_announcements_user` (`created_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `auth_throttle`
--
ALTER TABLE `auth_throttle`
  ADD PRIMARY KEY (`bucket`);

--
-- Indexes for table `deposit_requests`
--
ALTER TABLE `deposit_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_deposit_requests_status` (`status`,`created_at`),
  ADD KEY `fk_deposit_requests_account` (`account_id`),
  ADD KEY `fk_deposit_requests_member` (`member_id`),
  ADD KEY `fk_deposit_requests_reviewer` (`reviewed_by`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loan_number` (`loan_number`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_number` (`member_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `membership_applications`
--
ALTER TABLE `membership_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_reference` (`application_reference`),
  ADD KEY `idx_membership_applications_status` (`status`,`created_at`),
  ADD KEY `fk_membership_applications_user` (`user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_reset_token` (`token_hash`),
  ADD KEY `idx_password_reset_user` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_number` (`reference_number`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_mfa`
--
ALTER TABLE `user_mfa`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `account_requests`
--
ALTER TABLE `account_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `deposit_requests`
--
ALTER TABLE `deposit_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `membership_applications`
--
ALTER TABLE `membership_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `fk_accounts_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `account_requests`
--
ALTER TABLE `account_requests`
  ADD CONSTRAINT `fk_account_requests_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_account_requests_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announcements_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `deposit_requests`
--
ALTER TABLE `deposit_requests`
  ADD CONSTRAINT `fk_deposit_requests_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `fk_deposit_requests_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `fk_deposit_requests_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_mfa`
--
ALTER TABLE `user_mfa`
  ADD CONSTRAINT `fk_mfa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
