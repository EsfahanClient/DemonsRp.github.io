-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 28, 2026 at 08:44 PM
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
-- Database: `dsite3`
--

-- --------------------------------------------------------

--
-- Table structure for table `chat_settings`
--

CREATE TABLE `chat_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_settings`
--

INSERT INTO `chat_settings` (`setting_key`, `setting_value`) VALUES
('chat_status', 'open'),
('min_chat_level', '0');

-- --------------------------------------------------------

--
-- Table structure for table `global_chat`
--

CREATE TABLE `global_chat` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('public','announcement') DEFAULT 'public',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(50) NOT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `subject`, `status`, `created_at`) VALUES
(1, 2, 'باگ و مشکلات سرور', 'open', '2026-06-28 17:31:34'),
(2, 3, 'شکایت از پلیر', 'open', '2026-06-28 17:35:21'),
(3, 5, 'شکایت از پلیر', 'closed', '2026-06-28 18:21:12');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_messages`
--

CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_messages`
--

INSERT INTO `ticket_messages` (`id`, `ticket_id`, `sender_id`, `message`, `created_at`) VALUES
(1, 1, 2, 'سلام این تیکت برای تست است', '2026-06-28 17:31:34'),
(2, 1, 2, 'تست', '2026-06-28 17:31:57'),
(3, 2, 3, 'درود', '2026-06-28 17:35:21'),
(4, 3, 5, 'سلام این یک موضوع تست هست', '2026-06-28 18:21:12'),
(5, 3, 5, '.', '2026-06-28 18:21:21'),
(6, 3, 2, '.', '2026-06-28 18:23:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `admin_level` int(11) DEFAULT 0,
  `chat_muted_until` datetime DEFAULT NULL,
  `chat_banned` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `admin_level`, `chat_muted_until`, `chat_banned`) VALUES
(1, 'Director_User', '$2y$10$U6UonmR6HjlybH/8aT/0e.iTqA873rW8YhCbeQ28rV9pD/rGgC7gq', '', 4, NULL, 0),
(2, 'seyed', '$2y$10$pkSP.cH49bSOPp/pOmWSkuntJJlLAE43tpYkJtkQCOBV7IrLSER2C', 'behdadmemartolouie@gmail.com', 4, NULL, 0),
(3, 'seyed2', '$2y$10$hIFBFnqbJD086RDlU0wGVuv/Lk3P3dpk.286V/9LK9xMvFW6HecWC', 'slotangamer9@gmail.com', 0, NULL, 0),
(4, 'lvl3', '$2y$10$vzZKbXBNuUlS3JqHq/Gi/OrTUuXDx/50wQ0R4HRNzmCddPtjMea5G', 'steamaliasgare@gmail.com', 3, NULL, 0),
(5, 'demons player', '$2y$10$8i8Xs2kl9BOg7kcKZuV9TeIMI6z/r87Rm50jIXr/snI0A/pF6IFzu', 'demonsemail@gmail.com', 0, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chat_settings`
--
ALTER TABLE `chat_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `global_chat`
--
ALTER TABLE `global_chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `global_chat`
--
ALTER TABLE `global_chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `global_chat`
--
ALTER TABLE `global_chat`
  ADD CONSTRAINT `global_chat_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `ticket_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
