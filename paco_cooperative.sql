-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2026 at 06:38 AM
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
(1, 1, 'ACC-10001234', 'Savings', 9100.00, 'active', '2026-09-07 14:08:14'),
(2, 2, 'ACC-10001235', 'Savings', 2000.00, 'active', '2026-09-07 14:08:14');

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

--
-- Dumping data for table `account_requests`
--

INSERT INTO `account_requests` (`id`, `member_id`, `account_type`, `status`, `admin_notes`, `requested_at`, `reviewed_by`, `reviewed_at`) VALUES
(1, 1, 'Education Savings Deposit', 'pending', NULL, '2026-09-13 22:32:22', NULL, NULL);

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
(1, 1, 'CREATE_ACCOUNT', 'Created savings account for member MEM-2026-0001', '127.0.0.1', 'success', '2026-09-07 13:41:00'),
(2, 2, 'login', 'Successful login', '::1', 'success', '2026-09-13 22:31:53'),
(3, 2, 'account_request', 'Requested a new savings account: Education Savings Deposit', '::1', 'success', '2026-09-13 22:32:22'),
(4, 2, 'login', 'Successful login', '::1', 'success', '2026-09-13 22:33:26'),
(5, 1, 'login', 'Successful login', '::1', 'success', '2026-09-13 22:42:38'),
(6, 1, 'login', 'Successful login', '::1', 'success', '2026-09-13 22:43:24'),
(7, 2, 'login', 'Successful login', '::1', 'success', '2026-09-13 22:43:30'),
(8, 2, 'otp_generated', 'OTP generated after successful password login.', '::1', 'success', '2026-09-13 22:50:33'),
(9, 2, 'otp_generated', 'OTP generated after successful password login.', '::1', 'success', '2026-09-13 22:52:40'),
(10, 2, 'otp_generated', 'OTP generated after successful password login.', '::1', 'success', '2026-09-13 22:54:20'),
(11, 2, 'login', 'Successful login with OTP verification.', '::1', 'success', '2026-09-13 22:54:25'),
(12, 2, 'otp_generated', 'OTP generated after successful password login.', '::1', 'success', '2026-09-13 22:54:37'),
(13, 2, 'otp_generated', 'OTP generated after successful password login.', '::1', 'success', '2026-09-13 23:05:12'),
(14, 2, 'login', 'Successful login with OTP verification.', '::1', 'success', '2026-09-13 23:05:16'),
(15, 2, 'otp_generated', 'OTP generated after successful password login.', '::1', 'success', '2026-09-13 23:26:29'),
(16, 2, 'login', 'Successful login with OTP verification.', '::1', 'success', '2026-09-13 23:26:32'),
(17, 2, 'otp_generated', 'OTP generated after successful password login.', '::1', 'success', '2026-09-13 23:55:04'),
(18, 2, 'login', 'Successful login with OTP verification.', '::1', 'success', '2026-09-13 23:55:10'),
(19, 2, 'otp_generated', 'OTP generated after successful password login.', '::1', 'success', '2026-09-13 23:56:39'),
(20, 2, 'login', 'Successful login with OTP verification.', '::1', 'success', '2026-09-13 23:56:45'),
(21, 2, 'otp_generated', 'OTP generated after successful password login.', '::1', 'success', '2026-09-14 03:18:41'),
(22, 2, 'login', 'Successful login with OTP verification.', '::1', 'success', '2026-09-14 03:19:20'),
(23, 2, 'otp_generated', 'OTP generated after successful password login.', '::1', 'success', '2026-09-14 03:40:23'),
(24, 2, 'login', 'Successful login with OTP verification.', '::1', 'success', '2026-09-14 03:40:27'),
(25, 2, 'otp_generated', 'OTP generated after successful password login.', '::1', 'success', '2026-09-14 03:48:29'),
(26, 2, 'login', 'Successful login with OTP verification.', '::1', 'success', '2026-09-14 03:48:32'),
(27, 1, 'otp_generated', 'OTP generated after successful password login.', '::1', 'success', '2026-09-14 03:50:24'),
(28, 1, 'login', 'Successful login with OTP verification.', '::1', 'success', '2026-09-14 03:50:29'),
(29, 2, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-14 04:19:03'),
(38, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:20:36'),
(39, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:20:36'),
(40, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:20:36'),
(41, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:20:36'),
(42, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:20:36'),
(43, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:20:36'),
(44, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:20:36'),
(45, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:20:36'),
(46, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:20:36'),
(47, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:20:36'),
(56, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:25:57'),
(57, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:25:57'),
(58, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:25:57'),
(59, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:25:57'),
(60, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:25:57'),
(61, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:25:58'),
(62, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:25:58'),
(63, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:25:58'),
(64, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:25:58'),
(65, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-14 04:25:58'),
(70, 2, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-14 04:29:41'),
(71, 2, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-14 04:31:43'),
(72, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-14 04:33:16');

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
('0dec399fb0f5d09ed0313b16c963cfd3b60b1de7ff7bf0aa43683b47ea842e1b', 1, 1789360181),
('198286edff5123d5e59ea8a2b10bfdfbc9cb3f69ba159d1b44726957b6ed85db', 21, 1789359635),
('5456e675810acd0a48867c668ea9590525c04f114ff73105cd550ef7734f24c0', 1, 1789360303),
('6646fe8bd738e49e009c0984738f8ecf0755879406a7b0bdcb5b68db3fd9efa0', 1, 1789360396),
('894d5b5cb309107d0fe3419e68fd7c5bb7ea29177ec5e667c1555c57cd324035', 33, 1789359543),
('8cb6770c76892c5f44c561d7944231905bfd548ee3214b4e445b9380e3733c8e', 1, 1789359543);

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

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `member_id`, `loan_number`, `loan_product`, `loan_amount`, `remaining_balance`, `status`, `application_date`, `loan_term`, `loan_purpose`, `contact_number`, `birth_date`, `civil_status`, `address`, `occupation`, `employer`, `monthly_income`, `id_type`, `id_number`, `id_document_path`, `proof_income_path`, `identity_status`, `created_at`, `identity_verified_by`, `identity_verified_at`, `identity_notes`) VALUES
(1, 1, 'LN-2026-0001', NULL, 15000.00, 5000.00, 'approved', '2026-09-07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-09-07 14:07:49', NULL, NULL, NULL),
(2, 1, 'LN-2026-1518', NULL, 10100.00, 10100.00, 'approved', '2026-09-07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-09-07 14:01:23', NULL, NULL, NULL),
(3, 1, 'LN-2026-4297', NULL, 15000.00, 15000.00, 'approved', '2026-09-07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-09-07 14:02:37', NULL, NULL, NULL);

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
(1, 2, 'MEM-2026-0001', 'Maria', 'Santos', 'maria.santos@email.com', '09171234567', '2026-09-07 13:39:56'),
(2, 3, 'MEM-2026-0002', 'Juan', 'Cruz', 'juan.cruz@email.com', '09181234567', '2026-09-07 14:05:27');

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
(1, NULL, 'Juan', 'Dela Cruz', 'Juan Dela Cruz', 'juan@test.com', '09123456789', 'MNL', 'online', 'APP-2026-150690', NULL, NULL, NULL, NULL, NULL, 'uploads/membership_documents/495415a5f2bd07059b29fae444f827eb110243fd.jpg', 'uploads/membership_documents/edf614c8ca20ac3b9eb1dcf298d8d8ec0cbda6fb.jpg', 'uploads/membership_documents/b3fbc90cefd1b185c9cda4bc45303dc04f1d22a4.jpg', 'uploads/membership_documents/d88a2d14694b0ac031de1414ececa26e80b13a0a.png', 'uploads/membership_documents/6710b76f803f3f5ebe0d80ad5870304873f1619b.jpg', 'uploads/membership_documents/50204a9c058cb71197ed1b911c7a5e12e6e8b075.jpg', 'uploads/membership_documents/bb92f272b00d8d7f6fd4e32b7ae525b0695b7ae9.jpg', NULL, NULL, NULL, 'documents_submitted', NULL, NULL, '2026-09-13 23:22:00', '2026-09-13 23:46:37'),
(2, NULL, 'Renz', 'Lumabi', 'Renz Lumabi', 'raalumabi2025@plm.edu.ph', '09999313521', '218 Masikap st. Tondo, Manila', 'online', 'APP-2026-18B6E2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending_schedule', NULL, NULL, '2026-09-13 23:25:40', '2026-09-13 23:25:40');

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

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `account_id`, `transaction_type`, `amount`, `recipient_account_id`, `description`, `status`, `reference_number`, `transaction_date`, `created_at`) VALUES
(1, 1, 'deposit', 5000.00, NULL, 'Initial membership deposit', 'completed', 'TXN-20260907-001', 1788788460, 1788788460),
(2, 1, 'withdrawal', 1000.00, NULL, 'ATM Cash Withdrawal', 'completed', 'TXN-TEST-002', 1788788649, 1788788649),
(3, 1, 'deposit', 500.00, NULL, 'Savings Deposit', 'completed', 'TXN-20260907155347-647', 1788789227, 1788789227),
(4, 1, 'deposit', 500.00, NULL, 'Savings Deposit', 'completed', 'TXN-20260907155626-366', 1788789386, 1788789386),
(5, 1, 'deposit', 10100.00, NULL, 'Loan Disbursement', 'completed', 'DISB-20260907160123-265', 1788789683, 1788789683),
(6, 1, 'deposit', 15000.00, NULL, 'Loan Disbursement', 'completed', 'DISB-20260907160237-327', 1788789757, 1788789757),
(7, 1, 'withdrawal', 5000.00, NULL, 'Savings Withdraw', 'completed', 'TXN-20260907160255-311', 1788789775, 1788789775),
(8, 1, 'withdrawal', 5000.00, NULL, 'Savings Withdraw', 'completed', 'TXN-20260907160412-509', 1788789852, 1788789852),
(9, 1, 'withdrawal', 2000.00, NULL, 'Loan Payment', 'completed', 'REPAY-20260907160432-592', 1788789872, 1788789872),
(10, 1, 'withdrawal', 2000.00, NULL, 'Loan Payment', 'completed', 'REPAY-20260907160543-117', 1788789943, 1788789943),
(11, 1, 'withdrawal', 2000.00, NULL, 'Loan Payment', 'completed', 'REPAY-20260907160612-144', 1788789972, 1788789972),
(12, 1, 'withdrawal', 2000.00, NULL, 'Loan Payment', 'completed', 'REPAY-20260907160735-132', 1788790055, 1788790055),
(13, 1, 'withdrawal', 2000.00, NULL, 'Loan Payment', 'completed', 'REPAY-20260907160749-366', 1788790069, 1788790069),
(14, 1, 'transfer', 1000.00, 2, 'Transfer to Juan Cruz (nice)', 'completed', 'TRF-20260907160814-117', 1788790094, 1788790094);

--
-- Triggers `transactions`
--
DELIMITER $$
CREATE TRIGGER `update_balance_after_transaction` AFTER INSERT ON `transactions` FOR EACH ROW BEGIN
    -- If deposit: Add money to the account
    IF NEW.transaction_type = 'deposit' THEN
        UPDATE accounts 
        SET balance = balance + NEW.amount 
        WHERE id = NEW.account_id;

    -- If withdrawal: Subtract money from the account
    ELSEIF NEW.transaction_type = 'withdrawal' THEN
        UPDATE accounts 
        SET balance = balance - NEW.amount 
        WHERE id = NEW.account_id;

    -- If transfer: Deduct from sender, add to recipient
    ELSEIF NEW.transaction_type = 'transfer' THEN
        UPDATE accounts 
        SET balance = balance - NEW.amount 
        WHERE id = NEW.account_id;
        
        UPDATE accounts 
        SET balance = balance + NEW.amount 
        WHERE id = NEW.recipient_account_id;
    END IF;
END
$$
DELIMITER ;

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
(1, 'admin_juan', NULL, '$2y$10$jDFEleZdO2CQTnWIl/5treHBiq.InlLAcH9OCyz/5m.P7Ao8AS2I.', 'admin', '2026-09-14 03:50:29', 'active', NULL, NULL, 0),
(2, 'maria_santos', NULL, '$2y$10$jDFEleZdO2CQTnWIl/5treHBiq.InlLAcH9OCyz/5m.P7Ao8AS2I.', 'member', '2026-09-14 03:48:32', 'active', NULL, NULL, 0),
(3, 'juan_cruz', NULL, '$2y$10$4.aC6TqS4hP4xVn9E/HZe.WJ3s.W5g/fB4R5L2vN7T0ZzS6B8N/5m', 'member', '2026-09-07 14:05:27', 'active', NULL, NULL, 0);

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
(2, 'NQglsFrYIqsl3VZ6e3rAbMooD917reGvNTvWat7OC4LVKjPemMdbN0UvpdfxiS1cPBMbj0Nk1mfm3v6d', 59645343);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `deposit_requests`
--
ALTER TABLE `deposit_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `membership_applications`
--
ALTER TABLE `membership_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
