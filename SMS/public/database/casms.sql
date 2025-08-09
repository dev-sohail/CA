-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 17, 2025 at 05:10 PM
-- Server version: 8.0.42-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `casms`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `username` varchar(100) NOT NULL,
  `role` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` varchar(400) NOT NULL,
  `deadline` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`username`, `role`, `title`, `description`, `deadline`) VALUES
('', 'student', 'Any', 'Other', 0),
('', 'student', 'Any', 'Other', 0);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `year` int NOT NULL,
  `month` int DEFAULT NULL,
  `present_days` int DEFAULT '0',
  `absent_days` int DEFAULT '0',
  `late_days` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `username`, `year`, `month`, `present_days`, `absent_days`, `late_days`) VALUES
(1, 'emma_williams', 2025, 5, 1, 2, 1),
(7, 'michael_johenson', 2025, 5, 1, 2, 0),
(8, 'olivia.jones', 2025, 5, 3, 1, 2),
(9, 'alice.smith', 2025, 5, 0, 1, 0),
(10, 'john.doe', 2025, 5, 1, 0, 0),
(15, 'olivia.jones', 2025, 6, 1, 0, 0),
(16, 'michael_johenson', 2025, 6, 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `daily_attendance`
--

CREATE TABLE `daily_attendance` (
  `id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `attendance_status` enum('present','absent','late') NOT NULL,
  `methods` enum('rfid','fingerprint','otp','geolocation','manual') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `daily_attendance`
--

INSERT INTO `daily_attendance` (`id`, `username`, `date`, `attendance_status`, `methods`) VALUES
(33, 'john.doe', '2025-05-27', 'present', NULL),
(34, 'alice.smith', '2025-05-01', 'absent', NULL),
(35, 'olivia.jones', '2025-06-02', 'present', NULL),
(36, 'michael_johenson', '2025-06-02', 'present', NULL);

--
-- Triggers `daily_attendance`
--
-- DELIMITER $$
-- CREATE TRIGGER `update_my_attendance` AFTER INSERT ON `daily_attendance` FOR EACH ROW BEGIN
--     DECLARE present_count INT$$
-- DELIMITER ;

DELIMITER $$

CREATE TRIGGER `update_my_attendance` 
AFTER INSERT ON `daily_attendance`
FOR EACH ROW
BEGIN
    DECLARE present_count INT;

    -- Example logic: Count how many 'present' days this user has in the month and year of the new entry
    SELECT COUNT(*) INTO present_count
    FROM daily_attendance
    WHERE username = NEW.username
      AND YEAR(`date`) = YEAR(NEW.date)
      AND MONTH(`date`) = MONTH(NEW.date)
      AND attendance_status = 'present';

    -- Insert or update the attendance summary
    INSERT INTO attendance (username, year, month, present_days)
    VALUES (NEW.username, YEAR(NEW.date), MONTH(NEW.date), present_count)
    ON DUPLICATE KEY UPDATE present_days = present_count;

END $$

DELIMITER ;


-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int NOT NULL,
  `role` enum('teacher','student','parent','staff','all') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `title` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `role`, `title`, `start_date`, `end_date`, `description`) VALUES
(1, 'teacher', 'Teacher Training Workshop', '2025-01-20', NULL, 'Workshop for teacher skill development.'),
(2, 'student', 'Annual Sports Day', '2025-02-15', '2025-02-15', 'Sports activities for students.'),
(3, 'parent', 'Parent-Teacher Meeting', '2025-03-05', NULL, 'Discussion of student performance.'),
(4, 'staff', 'Staff Meeting', '2025-03-10', NULL, 'Monthly meeting for school staff.');

-- --------------------------------------------------------

--
-- Table structure for table `features_sms`
--

CREATE TABLE `features_sms` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `details` text,
  `icon_class` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `features_sms`
--

INSERT INTO `features_sms` (`id`, `title`, `details`, `icon_class`) VALUES
(1, 'Student Management', 'Efficiently manage student records and performance.', 'fas fa-user-graduate text-light'),
(2, 'Staff Management', 'All staff of the school, excluding teachers and including admin, will be managed.', 'fas fa-solid fa-users text-light'),
(3, 'Library Site Management', 'Students will read and download books.', 'fas fa-solid fa-book text-light'),
(4, 'Learning Site Management', 'Students will watch and read different courses, tutorials, and articles.', 'fas fa-solid fa-graduation-cap text-light'),
(5, 'Teacher Management', 'Handle teacher profiles and assignments.', 'fas fa-chalkboard-teacher text-light');

-- --------------------------------------------------------

--
-- Table structure for table `notice_board`
--

CREATE TABLE `notice_board` (
  `id` int NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `role` enum('teacher','student','parent','staff','all') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notice_board`
--

INSERT INTO `notice_board` (`id`, `username`, `role`, `title`, `content`, `created_at`) VALUES
(1, NULL, 'parent', 'Upcoming Parent-Teacher Meeting', 'There will be a parent-teacher meeting on January 15, 2025. Please be prepared.', '2025-01-08 12:24:30'),
(2, NULL, 'parent', 'New Assignment Submission', 'The deadline for the new assignment is January 20, 2025. Please submit before the due date.', '2025-01-08 12:24:30');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `day` varchar(20) NOT NULL,
  `period_1` varchar(255) DEFAULT NULL,
  `period_1_time` time DEFAULT NULL,
  `period_2` varchar(255) DEFAULT NULL,
  `period_2_time` time DEFAULT NULL,
  `period_3` varchar(255) DEFAULT NULL,
  `period_3_time` time DEFAULT NULL,
  `break_time` time DEFAULT NULL,
  `period_4` varchar(255) DEFAULT NULL,
  `period_4_time` time DEFAULT NULL,
  `period_5` varchar(255) DEFAULT NULL,
  `period_5_time` time DEFAULT NULL,
  `period_6` varchar(255) DEFAULT NULL,
  `period_6_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `username`, `day`, `period_1`, `period_1_time`, `period_2`, `period_2_time`, `period_3`, `period_3_time`, `break_time`, `period_4`, `period_4_time`, `period_5`, `period_5_time`, `period_6`, `period_6_time`) VALUES
(4, 'michael_johenson', 'Monday', '10th', '08:00:00', '9th', '09:00:00', '11th', '10:00:00', '11:00:00', '12th', '11:30:00', '8th', '12:30:00', '7th', '13:30:00'),
(5, 'michael_johenson', 'Tuesday', '10th', '08:00:00', '9th', '09:00:00', '11th', '10:00:00', '11:00:00', '12th', '11:30:00', '8th', '12:30:00', '7th', '13:30:00'),
(6, 'michael_johenson', 'Wednesday', '9th', '08:00:00', '10th', '09:00:00', '11th', '10:00:00', '11:00:00', '12th', '11:30:00', '8th', '12:30:00', '7th', '13:30:00'),
(7, 'emma_williams', 'Monday', '9th', '08:00:00', '10th', '09:00:00', '12th', '10:00:00', '11:00:00', '11th', '11:30:00', '7th', '12:30:00', '8th', '13:30:00'),
(8, 'emma_williams', 'Tuesday', '9th', '08:00:00', '10th', '09:00:00', '12th', '10:00:00', '11:00:00', '11th', '11:30:00', '7th', '12:30:00', '8th', '13:30:00'),
(9, 'emma_williams', 'Wednesday', '11th', '08:00:00', '9th', '09:00:00', '12th', '10:00:00', '11:00:00', '11th', '11:30:00', '7th', '12:30:00', '8th', '13:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `user_info`
--

CREATE TABLE `user_info` (
  `user_id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(40) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `role` enum('student','teacher','parent','staff') NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `grade` varchar(20) DEFAULT NULL,
  `grade_incharge` varchar(10) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `subjects_taught` varchar(255) DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `relationship_to_student` varchar(50) DEFAULT NULL,
  `children` json DEFAULT NULL,
  `child_user` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `full_name` varchar(255) GENERATED ALWAYS AS (concat(`first_name`,_utf8mb4' ',`last_name`)) STORED,
  `parent_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_info`
--

INSERT INTO `user_info` (`user_id`, `first_name`, `last_name`, `username`, `email`, `phone_number`, `role`, `password`, `date_of_birth`, `gender`, `address`, `created_at`, `updated_at`, `status`, `profile_picture`, `student_id`, `grade`, `grade_incharge`, `section`, `guardian_name`, `guardian_contact`, `employee_id`, `department`, `subjects_taught`, `join_date`, `qualification`, `relationship_to_student`, `children`, `child_user`, `designation`, `parent_id`) VALUES
(1, 'John', 'Doe', 'john.doe', 'john.doe@student.com', '9876543210', 'student', 'admin', '2007-04-15', 'male', '123 Main St, Springfield', '2024-11-27 18:57:12', '2025-06-17 14:02:17', 'active', 'https://picsum.photos/200?random=1', 'S1001', '10th', NULL, 'A', 'Jane Doe', '9876543211', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Alice', 'Smith', 'alice.smith', 'alice.smith@student.com', '9123456789', 'student', 'admin', '2008-07-22', 'female', '456 Oak St, Springfield', '2024-11-27 18:57:12', '2025-06-17 14:02:21', 'active', 'https://picsum.photos/200?random=2', 'S1002', '9th', NULL, 'B', 'Bob Smith', '9123456790', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Michael', 'Johnson', 'michael_johenson', 'michael.johnson@school.com', '9988776655', 'teacher', 'admin', '1980-11-12', 'male', '789 Pine St, Springfield', '2024-11-27 18:57:12', '2025-06-17 14:02:23', 'active', 'https://picsum.photos/200?random=3', NULL, NULL, '10th', NULL, NULL, NULL, 'T2001', 'Mathematics', 'Math', '2010-08-15', 'M.Sc. Mathematics', NULL, NULL, NULL, NULL, NULL),
(4, 'Emma', 'Williams', 'emma_williams', 'emma.williams@school.com', '9988776656', 'teacher', 'admin', '1985-05-30', 'female', '101 Maple St, Springfield', '2024-11-27 18:57:12', '2025-06-17 14:02:26', 'active', 'https://via.placeholder.com/80', NULL, NULL, '9th', NULL, NULL, NULL, 'T2002', 'Physics', 'Physics', '2012-02-20', 'M.Sc. Physics', NULL, NULL, NULL, NULL, NULL),
(5, 'David', 'Doe', 'david.doe', 'david.doe@parent.com', '9856432101', 'parent', 'admin', '1980-03-14', 'male', '123 Main St, Springfield', '2024-11-27 18:57:12', '2025-06-17 14:04:05', 'active', 'https://picsum.photos/200?random=5', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Father', '[\"S1001\"]', 'john.doe', NULL, 'P2001'),
(6, 'Sophia', 'Smith', 'sophia.smith', 'sophia.smith@parent.com', '9801234567', 'parent', 'admin', '1982-02-22', 'female', '456 Oak St, Springfield', '2024-11-27 18:57:12', '2025-06-17 14:03:59', 'active', 'https://picsum.photos/200?random=6', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mother', '[\"S1002\"]', NULL, NULL, 'P2002'),
(7, 'James', 'Brown', 'james.brown', 'james.brown@school.com', '9776554433', 'staff', 'admin', '1990-06-17', 'male', '123 Birch St, Springfield', '2024-11-27 18:57:12', '2025-06-17 14:02:33', 'active', 'https://picsum.photos/200?random=7', NULL, NULL, NULL, NULL, NULL, NULL, 'ST3001', 'Administration', NULL, '2015-03-01', NULL, NULL, NULL, NULL, 'Administrator', NULL),
(8, 'Olivia', 'Jones', 'olivia.jones', 'olivia.jones@school.com', '9222334455', 'staff', 'admin', '1992-09-05', 'female', '789 Birch St, Springfield', '2024-11-27 18:57:12', '2025-06-17 14:02:38', 'active', 'https://picsum.photos/200?random=8', NULL, NULL, NULL, NULL, NULL, NULL, 'ST3002', 'Student Services', NULL, '2017-05-10', NULL, NULL, NULL, NULL, 'Counselor', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_my_month` (`username`,`year`,`month`);

--
-- Indexes for table `daily_attendance`
--
ALTER TABLE `daily_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`username`,`date`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `features_sms`
--
ALTER TABLE `features_sms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notice_board`
--
ALTER TABLE `notice_board`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`,`day`);

--
-- Indexes for table `user_info`
--
ALTER TABLE `user_info`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `daily_attendance`
--
ALTER TABLE `daily_attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `features_sms`
--
ALTER TABLE `features_sms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notice_board`
--
ALTER TABLE `notice_board`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_info`
--
ALTER TABLE `user_info`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`username`) REFERENCES `user_info` (`username`) ON DELETE CASCADE;

--
-- Constraints for table `daily_attendance`
--
ALTER TABLE `daily_attendance`
  ADD CONSTRAINT `daily_attendance_ibfk_1` FOREIGN KEY (`username`) REFERENCES `user_info` (`username`) ON DELETE CASCADE;

--
-- Constraints for table `notice_board`
--
ALTER TABLE `notice_board`
  ADD CONSTRAINT `notice_board_ibfk_1` FOREIGN KEY (`username`) REFERENCES `user_info` (`username`) ON DELETE CASCADE;

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`username`) REFERENCES `user_info` (`username`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
