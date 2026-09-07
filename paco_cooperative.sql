-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 07, 2026 at 05:43 PM
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
(1, 1, 'CREATE_ACCOUNT', 'Created savings account for member MEM-2026-0001', '127.0.0.1', 'success', '2026-09-07 13:41:00');

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `loan_number` varchar(30) NOT NULL,
  `loan_amount` decimal(15,2) NOT NULL,
  `remaining_balance` decimal(15,2) NOT NULL,
  `status` enum('pending','approved','rejected','paid') NOT NULL,
  `application_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `member_id`, `loan_number`, `loan_amount`, `remaining_balance`, `status`, `application_date`, `created_at`) VALUES
(1, 1, 'LN-2026-0001', 15000.00, 5000.00, 'approved', '2026-09-07', '2026-09-07 14:07:49'),
(2, 1, 'LN-2026-1518', 10100.00, 10100.00, 'approved', '2026-09-07', '2026-09-07 14:01:23'),
(3, 1, 'LN-2026-4297', 15000.00, 15000.00, 'approved', '2026-09-07', '2026-09-07 14:02:37');

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
  `password` varchar(255) NOT NULL,
  `role` enum('member','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `status`) VALUES
(1, 'admin_juan', '$2y$10$jDFEleZdO2CQTnWIl/5treHBiq.InlLAcH9OCyz/5m.P7Ao8AS2I.', 'admin', '2026-09-07 13:52:00', 'active'),
(2, 'maria_santos', '$2y$10$jDFEleZdO2CQTnWIl/5treHBiq.InlLAcH9OCyz/5m.P7Ao8AS2I.', 'member', '2026-09-07 13:52:00', 'active'),
(3, 'juan_cruz', '$2y$10$4.aC6TqS4hP4xVn9E/HZe.WJ3s.W5g/fB4R5L2vN7T0ZzS6B8N/5m', 'member', '2026-09-07 14:05:27', 'active');

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
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
