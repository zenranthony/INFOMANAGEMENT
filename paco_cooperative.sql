-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2026 at 05:15 PM
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
(2, 2, 'SAV-2026-000002', 'Savings', 54000.00, 'active', '2026-09-18 12:10:10'),
(3, 2, 'ACC-10000003', 'Laboratory Cooperative Savings', 0.00, 'active', '2026-09-18 10:30:01');

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
(1, 2, 'Laboratory Cooperative Savings', 'approved', 'dsdsds', '2026-09-18 10:28:57', 1, '2026-09-18 18:30:01');

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

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `body`, `image_path`, `published_at`, `status`, `created_by`, `created_at`) VALUES
(1, 'DWDW', 'DWDW', 'uploads/announcements/438f2c20844e03fba8e538ccf3984e51.jpg', '2026-09-18 20:02:17', 'published', 1, '2026-09-18 12:02:17');

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
(39, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-16 14:07:04'),
(40, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-16 14:55:59'),
(41, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-16 14:56:06'),
(42, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-16 15:39:44'),
(43, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-16 15:39:50'),
(44, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-16 16:14:03'),
(45, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-16 16:14:13'),
(46, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-16 16:14:23'),
(47, 3, 'deposit_request', 'Submitted deposit request 1', '::1', 'success', '2026-09-16 16:15:30'),
(48, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-16 16:51:27'),
(49, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-16 16:51:33'),
(50, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-16 17:23:29'),
(51, 3, 'member_otp_failed', 'Incorrect member email OTP entered.', '::1', 'failed', '2026-09-16 17:23:33'),
(52, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-16 17:23:40'),
(53, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-16 17:40:27'),
(54, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-16 17:40:33'),
(55, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-16 18:27:37'),
(56, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-16 18:27:46'),
(57, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-17 06:58:32'),
(58, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-17 06:58:49'),
(59, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-17 07:02:51'),
(60, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-17 07:02:58'),
(61, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-17 07:03:55'),
(62, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-17 07:04:01'),
(63, 1, 'pmes_completed', 'Marked PMES as completed for application APP-2026-158E8A', '::1', 'success', '2026-09-17 07:04:08'),
(64, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-17 08:11:56'),
(65, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-17 08:36:48'),
(66, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-17 08:39:42'),
(67, 1, 'otp_failed', 'Authenticator verification failed.', '::1', 'failed', '2026-09-17 08:43:31'),
(68, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-17 08:50:10'),
(69, 1, 'otp_failed', 'Authenticator verification failed.', '::1', 'failed', '2026-09-17 08:51:48'),
(70, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-17 09:03:21'),
(71, 3, 'member_otp_failed', 'Incorrect member email OTP entered.', '::1', 'failed', '2026-09-17 09:05:00'),
(72, 3, 'member_otp_failed', 'Incorrect member email OTP entered.', '::1', 'failed', '2026-09-17 09:05:02'),
(73, 3, 'member_otp_failed', 'Incorrect member email OTP entered.', '::1', 'failed', '2026-09-17 09:05:03'),
(74, 3, 'member_otp_failed', 'Incorrect member email OTP entered.', '::1', 'failed', '2026-09-17 09:05:04'),
(75, 3, 'member_otp_failed', 'Incorrect member email OTP entered.', '::1', 'failed', '2026-09-17 09:05:04'),
(76, 3, 'member_otp_resent', 'Member requested a new email OTP.', '::1', 'success', '2026-09-17 09:10:11'),
(77, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-18 03:34:40'),
(78, 1, 'otp_failed', 'Authenticator verification failed.', '::1', 'failed', '2026-09-18 03:34:55'),
(79, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-18 03:35:12'),
(80, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 03:51:50'),
(81, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 04:05:59'),
(82, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 04:08:03'),
(83, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-18 04:08:31'),
(84, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 08:46:58'),
(85, 3, 'member_otp_failed', 'Incorrect member email OTP entered.', '::1', 'failed', '2026-09-18 08:47:00'),
(86, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-18 08:47:08'),
(87, 3, 'loan_application', 'Submitted loan application LN-2026-3EFD2B5B', '::1', 'success', '2026-09-18 08:48:02'),
(88, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-18 08:52:43'),
(89, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-18 08:52:53'),
(90, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 08:57:18'),
(91, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 08:57:23'),
(92, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-18 08:57:33'),
(93, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 09:18:46'),
(94, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-18 09:18:54'),
(95, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 09:35:17'),
(96, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-18 09:35:25'),
(97, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-18 09:45:13'),
(98, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-18 09:46:33'),
(99, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-18 10:05:55'),
(100, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-18 10:06:03'),
(101, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 10:28:16'),
(102, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 10:28:32'),
(103, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-18 10:28:40'),
(104, 3, 'account_request', 'Requested a new savings account: Laboratory Cooperative Savings', '::1', 'success', '2026-09-18 10:28:57'),
(105, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-18 10:29:01'),
(106, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-18 10:29:08'),
(107, 1, 'account_request_review', 'Reviewed account request #1 -> approved', '::1', 'success', '2026-09-18 10:30:01'),
(108, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-18 10:39:32'),
(109, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-18 10:39:39'),
(110, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-18 10:40:26'),
(111, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-18 10:40:30'),
(112, 1, 'pmes_completed', 'Marked PMES as completed for application APP-2026-0E91E1', '::1', 'success', '2026-09-18 10:40:42'),
(113, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 10:54:44'),
(114, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-18 10:54:48'),
(115, NULL, 'login', 'Failed login attempt.', '::1', 'failed', '2026-09-18 10:56:51'),
(116, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 11:12:48'),
(117, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-18 11:13:10'),
(118, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 11:14:07'),
(119, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-18 11:14:19'),
(120, 3, 'loan_application', 'Submitted loan application LN-2026-FEE3878E', '::1', 'success', '2026-09-18 11:19:15'),
(121, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-18 11:19:21'),
(122, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-18 11:19:27'),
(123, 1, 'identity_review', 'Verified identity for loan 2', '::1', 'success', '2026-09-18 11:21:09'),
(124, 1, 'loan_approval', 'Approved and disbursed loan 2', '::1', 'success', '2026-09-18 11:21:39'),
(125, 1, 'loan_rejection', 'Rejected loan 1', '::1', 'success', '2026-09-18 11:21:47'),
(126, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-18 11:29:13'),
(127, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-18 11:29:21'),
(128, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 12:08:49'),
(129, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-18 12:09:13'),
(130, 3, 'deposit_request', 'Submitted deposit request 2', '::1', 'success', '2026-09-18 12:09:29'),
(131, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-18 12:09:33'),
(132, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-18 12:09:41'),
(133, 1, 'deposit_approval', 'Approved deposit request 2', '::1', 'success', '2026-09-18 12:10:10'),
(134, 1, 'deposit_rejection', 'Rejected deposit request 1', '::1', 'success', '2026-09-18 12:10:20'),
(135, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-18 14:27:10'),
(136, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-18 14:27:21'),
(137, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 14:47:55'),
(138, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-18 14:48:41'),
(139, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-18 14:49:53'),
(140, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-18 14:50:05'),
(141, 1, 'membership_review', 'Updated registration application status to documents_submitted', '::1', 'success', '2026-09-18 14:53:15'),
(142, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 15:00:41'),
(143, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-18 15:04:05'),
(144, 1, 'password_verified', 'Password verified; authenticator verification required.', '::1', 'success', '2026-09-18 15:08:04'),
(145, 1, 'login', 'Successful login with authenticator verification.', '::1', 'success', '2026-09-18 15:08:12'),
(146, 3, 'member_otp_sent', 'Email OTP sent after successful member password login.', '::1', 'success', '2026-09-18 15:10:10'),
(147, 3, 'login', 'Successful member login with email OTP verification.', '::1', 'success', '2026-09-18 15:10:40');

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
('05eb6450c1ef1c1240ea1b100c163ae160711e29db0c80048130ae967870413f', 1, 1789636205),
('0dec399fb0f5d09ed0313b16c963cfd3b60b1de7ff7bf0aa43683b47ea842e1b', 1, 1789575243),
('164f437932afb830b189668b8f813ddc9e6567a0b8fe850271bc369a4661672e', 2, 1789743845),
('198286edff5123d5e59ea8a2b10bfdfbc9cb3f69ba159d1b44726957b6ed85db', 1, 1789744092),
('5baac7f56ac5fdd0fd1a4ca1af43c531c58b6a49e15be059e7284e77c09f7574', 1, 1789636205),
('6646fe8bd738e49e009c0984738f8ecf0755879406a7b0bdcb5b68db3fd9efa0', 1, 1789744084),
('6d96e5b3d0d28d477a78c296b9e98b4eed80f70121f8a6abd57fbcff1794a4f1', 1, 1789744206),
('80347c8c68360d3d86e4a8d34a9acae9921321b75c4c59741ead9e70ed873523', 2, 1789743845),
('894d5b5cb309107d0fe3419e68fd7c5bb7ea29177ec5e667c1555c57cd324035', 2, 1789744084),
('8cb6770c76892c5f44c561d7944231905bfd548ee3214b4e445b9380e3733c8e', 1, 1789457621),
('c0f6723051fbf74bd9ea787a1d38ba8cc89862f139aaf6f26935d44369aff818', 1, 1789744092),
('c8bf58f35c64f041204c270b1bb744c4f0efa132908547860a145d9a8e190f41', 1, 1789729011),
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

--
-- Dumping data for table `deposit_requests`
--

INSERT INTO `deposit_requests` (`id`, `account_id`, `member_id`, `amount`, `payment_method`, `payment_reference`, `description`, `receipt_path`, `status`, `admin_notes`, `reviewed_by`, `reviewed_at`, `created_at`) VALUES
(1, 2, 2, 500.00, 'Maya', '4242424242', 'Counter Deposit', 'uploads/deposit_receipts/1c3e132ea97e4e9cc3ab06b269210182ebe9da939d02388d.jpg', 'rejected', 'No', 1, '2026-09-18 20:10:20', '2026-09-16 16:15:30'),
(2, 2, 2, 50000.00, 'Maya', '4241421412', 'Counter Deposit', 'uploads/deposit_receipts/bfa1145e64067568b8fbdcba991504f16e50df290c7da417.jpg', 'approved', '', 1, '2026-09-18 20:10:10', '2026-09-18 12:09:29');

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
(1, 2, 'LN-2026-3EFD2B5B', 'Livelihood Loan', 4000.00, 4000.00, 'rejected', '2026-09-18', '12 months', 'dsds', '09999313521', '2026-09-30', 'Married', '218 Masikap st. Tondo, Manila', 'Singer', 'N/A', 2500.00, 'PRC ID', '5255252', 'uploads/loan_documents/01faeb540c9dac0e357257b533caa56f44f14a4c4a42f090.jpg', 'uploads/loan_documents/40bf676df7c1624cbdb73b8badfce908b7a1eb8786fa33fe.jpg', 'pending', '2026-09-18 11:21:47', NULL, NULL, NULL),
(2, 2, 'LN-2026-FEE3878E', 'Memorial Plan', 4000.00, 4000.00, 'approved', '2026-09-18', '6 months', 'YES', '09999313521', '2026-10-07', 'Single', '218 Masikap st. Tondo, Manila', 'YES', 'N/A', 250.00, 'Other Government ID', '5255252', 'uploads/loan_documents/d7eb950f5248d1ee025ec959662ff878c12461a7bf49082a.jpg', 'uploads/loan_documents/d5ec77142117d5af13a9c40afbdc7c20da4ad71e46b5804b.jpg', 'verified', '2026-09-18 11:21:39', 1, '2026-09-18 19:21:09', 'yes');

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
(3, NULL, 'Kim', 'Generoso', 'Kim Generoso', 'renzpizza172@gmail.com', '09123456789', 'Tondo', 'face_to_face', 'APP-2026-5825A4', NULL, '2026-09-22 22:00', 'face_to_face', 'BRGY. 29 Basketball Court', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pmes_completed', NULL, '', '2026-09-15 14:03:05', '2026-09-15 14:05:17'),
(4, NULL, 'Renz', 'Lumabi', 'Renz Lumabi', 'renzlumabi@gmail.com', '09999313521', '218 Masikap st. Tondo, Manila', 'online', 'APP-2026-588268', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending_schedule', NULL, NULL, '2026-09-16 15:30:27', '2026-09-16 15:30:27'),
(5, NULL, 'Renz', 'Lumabi', 'Renz Lumabi', 'balcefrancis3@gmail.com', '09999313521', '218 Masikap st. Tondo, Manila', 'face_to_face', 'APP-2026-217BF5', NULL, '2026-09-02 23:42', 'online', NULL, 'https://meet.google.com/test-link', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'scheduled', NULL, NULL, '2026-09-16 15:38:10', '2026-09-16 15:40:10'),
(6, NULL, 'Renz', 'Lumabi', 'Renz Lumabi', 'czarinareignpaguntalan@gmail.com', '09999313521', '218 Masikap st. Tondo, Manila', 'face_to_face', 'APP-2026-8B6659', NULL, '2026-09-25 15:00', 'online', NULL, 'https://meet.google.com/test-link', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'scheduled', NULL, NULL, '2026-09-16 18:25:47', '2026-09-17 06:59:42'),
(7, NULL, 'Renz', 'Lumabi', 'Renz Lumabi', 'ctf-player@picoctf.org', '09999313521', '218 Masikap st. Tondo, Manila', 'online', 'APP-2026-A9B76B', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending_schedule', NULL, NULL, '2026-09-17 06:14:52', '2026-09-17 06:14:52'),
(8, NULL, 'Renz', 'Lumabi', 'Renz Lumabi', 'juan@test.com', '09123456789', '218 Masikap st. Tondo, Manila', 'online', 'APP-2026-CD75CE', NULL, '2026-09-26 16:00', 'online', NULL, 'https://meet.google.com/test-link', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'scheduled', NULL, NULL, '2026-09-17 06:52:50', '2026-09-17 06:59:16'),
(9, NULL, 'Shika', 'World', 'Shika World', 'siczarinalang@gmail.com', '09123456789', 'candy world', 'face_to_face', 'APP-2026-158E8A', NULL, '2026-09-25 19:00', 'face_to_face', 'BRGY. 29 Basketball Court', NULL, 'uploads/membership_documents/bb26def1140a2550c12483f20cee0c5aa43a9e79.jpg', 'uploads/membership_documents/d5d1413ba7aa3dc75801627d9d6a0fddeee150a5.jpg', 'uploads/membership_documents/b021d9c1bed6b565cebf16d84f487245f65da74e.jpg', 'uploads/membership_documents/aca933d7921b0c8f638e0ba72c52416b9e901ec1.jpg', 'uploads/membership_documents/bd282151933d9a5ef500c64ddf7f9e8c0e144861.jpg', 'uploads/membership_documents/ce9ef175c739674ef20edda68c965a18009b4671.jpg', 'uploads/membership_documents/056b0a89d8856d1b5f17737a0007628cae651661.jpg', NULL, NULL, NULL, 'documents_submitted', NULL, NULL, '2026-09-17 07:02:21', '2026-09-17 07:17:10'),
(10, NULL, 'Renz', 'Lumabi', 'Renz Lumabi', 'raalumabi2025@plm.edu.ph', '09999313521', '218 Masikap st. Tondo, Manila', 'face_to_face', 'APP-2026-0E91E1', NULL, '2026-09-05 21:42', 'face_to_face', 'BRGY. 29 Basketball Court', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'documents_submitted', NULL, '', '2026-09-18 10:05:26', '2026-09-18 14:53:15');

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
(1, 2, 'deposit', 4000.00, NULL, 'Loan Disbursement', 'completed', 'OR-20260918132139-836208', 1789730499, 1789730499),
(2, 2, 'deposit', 50000.00, NULL, 'E-wallet deposit approved', 'completed', 'DEP-20260918141010-957560', 1789733410, 1789733410);

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
(3, 'francis_balsy', 'renzpizza172@gmail.com', '$2y$10$uAwDFEnM951LhEl0LaUNGeNO52OqP0szxbk63Ffod.N2DhRN4PTz.', 'member', '2026-09-18 15:10:40', 'active', NULL, NULL, 0);

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
(1, 'NEg0LraKqcx+3v2WOMXXNONVYHNodlId15kX1a3x+ryPlE3StAvdsSBryV1AjDO4yG9P/EQA0g5zlnc4', 59658136);

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
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `account_requests`
--
ALTER TABLE `account_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `deposit_requests`
--
ALTER TABLE `deposit_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `membership_applications`
--
ALTER TABLE `membership_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
