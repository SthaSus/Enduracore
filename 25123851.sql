-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 28, 2026 at 11:04 AM
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
-- Database: `25123851`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `account_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('ADMIN','TRAINER','MEMBER') NOT NULL,
  `created_on` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`account_id`, `username`, `password`, `role`, `created_on`, `last_login`) VALUES
(1, 'admin', '$2y$10$PHhSJj8F4Hzja4tuaeHbv./0dOlHsSc7v7qRW/JBjKFi3YuuXsChO', 'ADMIN', '2026-01-12 18:48:37', '2026-01-28 14:36:38'),
(2, 'trainer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'TRAINER', '2026-01-12 18:48:37', '2026-01-28 15:35:58'),
(3, 'trainer2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'TRAINER', '2026-01-12 18:48:37', '2026-01-23 23:13:28'),
(4, 'member1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MEMBER', '2026-01-12 18:48:37', '2026-01-28 14:23:57'),
(5, 'member2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MEMBER', '2026-01-12 18:48:37', '2026-01-23 22:38:23'),
(6, 'member3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MEMBER', '2026-01-12 18:48:37', '2026-01-22 22:20:16'),
(7, 'member4', '$2y$10$dTCB97V1PYsvAUldNY.Sf.0aoepiJDgzTAnT8SEhYtzlE9dpzCorG', 'MEMBER', '2026-01-22 22:33:25', '2026-01-23 22:39:28'),
(8, 'member5', '$2y$10$Y66xUDEb/dAd83qq64cVT.UtaJT5RpvoLk7PjCCDBxUKhuHCPQIui', 'MEMBER', '2026-01-24 15:34:32', '2026-01-24 15:34:42'),
(9, 'member6', '$2y$10$AZ2PFw4l2mhgiqxXjaPQ8u5NAUc2P7VWEFyBwpMEEwuXo2g2U8KZO', 'MEMBER', '2026-01-28 14:18:47', '2026-01-28 14:18:58'),
(10, 'Trainer3', '$2y$10$4oV82z.3YJsZ1IGjqo0qH.Df0czKY5qCDOyb2HSL/gYE1sG0B.5uu', 'TRAINER', '2026-01-28 14:38:08', '2026-01-28 15:08:17'),
(11, 'test1', '$2y$10$WWmbL05Rsgt5djDedgMaYelqbo6zlFNbJNTumQBinitL7TWqZsxmC', 'TRAINER', '2026-01-28 15:46:23', '2026-01-28 15:46:50');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `member_id`, `attendance_date`, `check_in`, `check_out`) VALUES
(1, 1, '2024-03-01', '06:30:00', '08:00:00'),
(2, 1, '2024-03-02', '06:00:00', '07:30:00'),
(3, 1, '2024-03-03', '06:30:00', '08:00:00'),
(4, 1, '2024-03-05', '06:30:00', '08:15:00'),
(5, 1, '2024-03-06', '06:45:00', '08:00:00'),
(6, 1, '2024-03-08', '06:30:00', '07:45:00'),
(7, 1, '2024-03-10', '06:30:00', '08:00:00'),
(8, 2, '2024-03-01', '07:00:00', '08:30:00'),
(9, 2, '2024-03-03', '17:00:00', '18:30:00'),
(10, 2, '2024-03-04', '07:00:00', '08:15:00'),
(11, 2, '2024-03-06', '07:30:00', '09:00:00'),
(12, 2, '2024-03-08', '17:00:00', '18:45:00'),
(13, 3, '2024-03-02', '18:00:00', '19:30:00'),
(14, 3, '2024-03-04', '06:00:00', '07:15:00'),
(15, 3, '2024-03-06', '18:00:00', '19:45:00'),
(16, 3, '2024-03-09', '18:00:00', '19:30:00'),
(17, 3, '2024-03-11', '18:15:00', '19:45:00'),
(18, 3, '2026-01-21', '13:40:00', '15:50:00'),
(19, 3, '2026-01-23', '17:52:00', '19:30:00'),
(20, 4, '2026-01-23', '19:28:00', '21:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL,
  `equipment_name` varchar(100) NOT NULL,
  `equipment_type` enum('Machine','Free Weight','Accessory') DEFAULT NULL,
  `equipment_condition` enum('New','Good','Needs Repair') DEFAULT NULL,
  `last_serviced` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `equipment_name`, `equipment_type`, `equipment_condition`, `last_serviced`) VALUES
(1, 'Treadmill 1', 'Machine', 'Good', '2024-01-15'),
(2, 'Treadmill 2', 'Machine', 'Needs Repair', '2026-01-18'),
(3, 'Bench Press', 'Machine', 'Good', '2024-02-01'),
(4, 'Dumbbells Set 10kg', 'Free Weight', 'New', '2024-03-01'),
(5, 'Dumbbells Set 20kg', 'Free Weight', 'Good', '2024-02-15'),
(6, 'Rowing Machine', 'Machine', 'Good', '2026-01-18'),
(7, 'Yoga Mats', 'Accessory', 'New', '2026-01-21'),
(8, 'Leg Press Machine', 'Machine', 'Good', '2024-01-10'),
(9, 'Barbells', 'Free Weight', 'New', '2026-01-24'),
(10, 'Exercise Bike', 'Machine', 'Good', '2026-01-21'),
(11, 'Stability Ball', 'Accessory', 'New', '2026-01-24');

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE `member` (
  `member_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `member_name` varchar(100) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `join_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`member_id`, `account_id`, `member_name`, `age`, `gender`, `phone`, `email`, `join_date`) VALUES
(1, 4, 'Mike Wilson', 28, 'Male', '5551234567', 'mike@email.com', '2024-01-15'),
(2, 5, 'Emily Davis', 32, 'Female', '5559876543', 'emily@email.com', '2024-02-20'),
(3, 6, 'David Brown', 25, 'Male', '9898989899', 'david@email.com', '2024-03-10'),
(4, 7, 'Utsav Rai', 20, 'Male', '9876789876', 'uturai@email.com', '2026-01-22'),
(5, 8, 'Bibek Karki', 22, 'Male', '1234565434', 'karkibibek@gmail.com', '2026-01-24'),
(6, 9, 'Ayush Shrestha', 32, 'Male', '1234565432', 'member@email.com', '2026-01-28');

-- --------------------------------------------------------

--
-- Table structure for table `membership`
--

CREATE TABLE `membership` (
  `membership_id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `membership_type` enum('Monthly','Quarterly','Half-Yearly','Yearly') DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT NULL,
  `status` enum('Active','Pending','Expired','Cancelled') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership`
--

INSERT INTO `membership` (`membership_id`, `member_id`, `membership_type`, `start_date`, `end_date`, `fee`, `status`) VALUES
(1, 1, 'Half-Yearly', '2024-01-15', '2024-07-15', 239.00, 'Expired'),
(2, 2, 'Quarterly', '2024-02-20', '2024-05-20', 129.00, 'Expired'),
(3, 3, 'Monthly', '2024-03-10', '2024-04-10', 49.00, 'Expired'),
(4, 1, 'Yearly', '2025-01-18', '2026-01-18', 399.00, 'Expired'),
(5, 1, 'Yearly', '2026-01-18', '2027-01-18', 399.00, 'Active'),
(10, 2, 'Monthly', '2026-01-21', '2026-02-21', 49.00, 'Active'),
(11, 2, 'Quarterly', '2026-02-22', '2026-05-22', 129.00, 'Pending'),
(12, 3, 'Quarterly', '2026-01-21', '2026-04-21', 129.00, 'Active'),
(19, 4, 'Monthly', '2026-01-23', '2026-02-23', 49.00, 'Active'),
(20, 4, 'Quarterly', '2026-02-24', '2026-05-24', 129.00, 'Pending'),
(21, 5, 'Monthly', '2026-01-24', '2026-02-24', 49.00, 'Active'),
(22, 5, 'Monthly', '2026-02-25', '2026-03-25', 49.00, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `member_workout_plan`
--

CREATE TABLE `member_workout_plan` (
  `member_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_workout_plan`
--

INSERT INTO `member_workout_plan` (`member_id`, `plan_id`) VALUES
(1, 1),
(1, 4),
(2, 2),
(3, 3),
(4, 4);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `membership_id` int(11) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('Cash','Card','eSewa','Khalti','Online Banking') NOT NULL,
  `payment_status` enum('Paid','Pending','Failed') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `membership_id`, `payment_date`, `amount`, `payment_method`, `payment_status`) VALUES
(1, 1, '2024-01-15', 239.00, 'Card', 'Paid'),
(2, 2, '2024-02-20', 129.00, 'Online Banking', 'Paid'),
(3, 3, '2024-03-10', 49.00, 'Cash', 'Paid'),
(4, 4, '2025-01-18', 399.00, 'Card', 'Paid'),
(5, 5, '2026-01-18', 399.00, 'Card', 'Paid'),
(10, 10, '2026-01-21', 49.00, 'eSewa', 'Paid'),
(11, 11, '2026-01-21', 129.00, 'Khalti', 'Paid'),
(12, 12, '2026-01-21', 129.00, 'Card', 'Paid'),
(19, 19, '2026-01-23', 49.00, 'Khalti', 'Paid'),
(20, 20, '2026-01-23', 129.00, 'eSewa', 'Paid'),
(21, 21, '2026-01-24', 49.00, 'eSewa', 'Paid'),
(22, 22, '2026-01-24', 49.00, 'Khalti', 'Paid');

-- --------------------------------------------------------

--
-- Table structure for table `trainer`
--

CREATE TABLE `trainer` (
  `trainer_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `trainer_name` varchar(100) NOT NULL,
  `specialization` enum('Strength','Cardio','Yoga','CrossFit','General') DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainer`
--

INSERT INTO `trainer` (`trainer_id`, `account_id`, `trainer_name`, `specialization`, `experience_years`, `phone`) VALUES
(1, 2, 'John Cena', 'Strength', 5, '1234567890'),
(2, 3, 'Jacky Chan', 'Yoga', 3, '9876543210'),
(6, 11, 'Bruce Lee', 'Strength', 2, '9878986523');

-- --------------------------------------------------------

--
-- Table structure for table `workout_plan`
--

CREATE TABLE `workout_plan` (
  `plan_id` int(11) NOT NULL,
  `trainer_id` int(11) DEFAULT NULL,
  `plan_name` varchar(100) NOT NULL,
  `goal` enum('Weight Loss','Muscle Gain','Endurance','Fitness') DEFAULT NULL,
  `duration` enum('1 Month','3 Months','6 Months') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workout_plan`
--

INSERT INTO `workout_plan` (`plan_id`, `trainer_id`, `plan_name`, `goal`, `duration`) VALUES
(1, 1, 'Strength Training Program', 'Muscle Gain', '3 Months'),
(2, 2, 'Weight Loss Journey', 'Weight Loss', '6 Months'),
(3, 1, 'Cardio Blast', 'Endurance', '3 Months'),
(4, 2, 'Beginner Fitness', 'Fitness', '1 Month'),
(5, 1, 'stamina gain', 'Endurance', '3 Months');

-- --------------------------------------------------------

--
-- Table structure for table `workout_plan_equipment`
--

CREATE TABLE `workout_plan_equipment` (
  `plan_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workout_plan_equipment`
--

INSERT INTO `workout_plan_equipment` (`plan_id`, `equipment_id`) VALUES
(1, 3),
(1, 4),
(1, 5),
(1, 8),
(1, 9),
(2, 1),
(2, 2),
(2, 7),
(2, 10),
(3, 1),
(3, 6),
(3, 10),
(4, 4),
(4, 7),
(5, 10);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`account_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equipment_id`);

--
-- Indexes for table `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `account_id` (`account_id`);

--
-- Indexes for table `membership`
--
ALTER TABLE `membership`
  ADD PRIMARY KEY (`membership_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `member_workout_plan`
--
ALTER TABLE `member_workout_plan`
  ADD PRIMARY KEY (`member_id`,`plan_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `membership_id` (`membership_id`);

--
-- Indexes for table `trainer`
--
ALTER TABLE `trainer`
  ADD PRIMARY KEY (`trainer_id`),
  ADD UNIQUE KEY `account_id` (`account_id`);

--
-- Indexes for table `workout_plan`
--
ALTER TABLE `workout_plan`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `workout_plan_equipment`
--
ALTER TABLE `workout_plan_equipment`
  ADD PRIMARY KEY (`plan_id`,`equipment_id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `membership`
--
ALTER TABLE `membership`
  MODIFY `membership_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `trainer`
--
ALTER TABLE `trainer`
  MODIFY `trainer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `workout_plan`
--
ALTER TABLE `workout_plan`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `member`
--
ALTER TABLE `member`
  ADD CONSTRAINT `member_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE CASCADE;

--
-- Constraints for table `membership`
--
ALTER TABLE `membership`
  ADD CONSTRAINT `membership_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `member_workout_plan`
--
ALTER TABLE `member_workout_plan`
  ADD CONSTRAINT `member_workout_plan_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `member_workout_plan_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `workout_plan` (`plan_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`membership_id`) REFERENCES `membership` (`membership_id`) ON DELETE CASCADE;

--
-- Constraints for table `trainer`
--
ALTER TABLE `trainer`
  ADD CONSTRAINT `trainer_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_plan`
--
ALTER TABLE `workout_plan`
  ADD CONSTRAINT `workout_plan_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `trainer` (`trainer_id`) ON DELETE SET NULL;

--
-- Constraints for table `workout_plan_equipment`
--
ALTER TABLE `workout_plan_equipment`
  ADD CONSTRAINT `workout_plan_equipment_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `workout_plan` (`plan_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workout_plan_equipment_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
