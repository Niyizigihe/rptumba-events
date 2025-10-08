-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2025 at 06:13 PM
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
-- Database: `rptumba_events`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `duration_unit` enum('minutes','hours','days','weeks','months') DEFAULT NULL,
  `venue` varchar(100) NOT NULL,
  `organizer` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT 'assets/images/event-placeholder.jpg',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `auto_complete_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `event_time`, `duration`, `duration_unit`, `venue`, `organizer`, `category`, `max_participants`, `image_path`, `created_by`, `created_at`, `status`, `completed_at`, `completed_by`, `auto_complete_time`) VALUES
(8, 'Graduation', 'Join us for exciting sports competitions and games. Open to all students and staff. 1. Inventory Analysis: Identify which software\r\nJoin us for exciting sports competitions and games. Open to all students and staff. 1. Inventory Analysis: Identify which softwareJoin us for exciting sports competitions and games. Open to all students and staff. 1. Inventory Analysis: Identify which software\r\n\r\nJoin us for exciting sports competitions and games. Open to all students and staff. 1. Inventory Analysis: Identify which software', '2025-10-17', '10:00:00', 2, 'days', 'UR Huye Campus', 'University of Rwanda', 'academic', NULL, 'assets/uploads/events/68e6808c19525_1759936652.jpg', 3, '2025-10-08 15:17:32', 'upcoming', '2025-10-08 18:10:30', NULL, NULL),
(9, 'Gufata amakarita y\'ishuri', 'Turamenyesha abanyeshuri bose ko guhera kuwa mbere tuzatanga amakarita y\'ishuri. azatangirwa muri IT Lab IV guhera saa sita. Utazaza azayibona mu wundi mwaka.', '2025-10-13', '12:00:00', 2, 'hours', 'IT Lab IV', 'DAS', 'academic', NULL, 'assets/uploads/events/68e6814948bf4_1759936841.jpeg', 3, '2025-10-08 15:20:41', 'upcoming', NULL, NULL, NULL),
(10, 'Annual Sport Day', 'Join us for annual sport day happening this weekend. There will be expert coaches helping us in physical exercises. \r\n\r\nPlease, register now because the seats are limited!', '2025-10-08', '09:00:00', NULL, NULL, 'Campus Gymnastic', 'Guild Counsil', 'sports', 30, 'assets/images/event-placeholder.jpg', 3, '2025-10-08 15:34:30', 'upcoming', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `event_completion_logs`
--

CREATE TABLE `event_completion_logs` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `completed_at` datetime NOT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `completion_type` enum('auto','manual') NOT NULL,
  `total_registrations` int(11) DEFAULT 0,
  `total_interested` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_completion_logs`
--

INSERT INTO `event_completion_logs` (`id`, `event_id`, `completed_at`, `completed_by`, `completion_type`, `total_registrations`, `total_interested`, `notes`) VALUES
(8, 8, '2025-10-08 18:10:30', NULL, 'auto', 1, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('registered','interested') NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`id`, `event_id`, `user_id`, `status`, `registered_at`) VALUES
(24, 8, 5, 'registered', '2025-10-08 15:18:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','staff','admin') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@rptumba.ac.rw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-09-30 18:15:39'),
(2, 'John Student', 'student@rptumba.ac.rw', '$2y$10$tY9u1qf901rZXYa.ticHhupeur1iMh/eDkMqiwm9ViWi5/AREXoPC', 'student', '2025-09-30 18:15:39'),
(3, 'Niyizigihe', 'admin2@rptumba.ac.rw', '$2y$10$tY9u1qf901rZXYa.ticHhupeur1iMh/eDkMqiwm9ViWi5/AREXoPC', 'admin', '2025-09-30 18:59:18'),
(5, 'Aime', 'aime@rptumba.ac.rw', '$2y$10$tY9u1qf901rZXYa.ticHhupeur1iMh/eDkMqiwm9ViWi5/AREXoPC', 'student', '2025-09-30 18:59:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `completed_by` (`completed_by`);

--
-- Indexes for table `event_completion_logs`
--
ALTER TABLE `event_completion_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_completion_logs_ibfk_1` (`event_id`),
  ADD KEY `event_completion_logs_ibfk_2` (`completed_by`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration` (`event_id`,`user_id`),
  ADD KEY `event_registrations_ibfk_2` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `event_completion_logs`
--
ALTER TABLE `event_completion_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `event_completion_logs`
--
ALTER TABLE `event_completion_logs`
  ADD CONSTRAINT `event_completion_logs_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `event_completion_logs_ibfk_2` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
