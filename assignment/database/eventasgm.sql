-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2026 at 06:44 PM
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
-- Database: `eventasgm`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievement`
--

CREATE TABLE `achievement` (
  `achievement_id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('Certificate','Award') NOT NULL DEFAULT 'Certificate',
  `event_type` varchar(30) DEFAULT NULL,
  `award_level` varchar(20) DEFAULT NULL,
  `award_status` enum('None','Pending','Approved','Rejected') DEFAULT 'None',
  `notes` varchar(500) DEFAULT NULL,
  `issued_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `achievement`
--

INSERT INTO `achievement` (`achievement_id`, `userid`, `event_id`, `title`, `type`, `event_type`, `award_level`, `award_status`, `notes`, `issued_date`, `created_at`, `file_path`) VALUES
(1, 8, 12, 'Certificate of Participation – cry', 'Certificate', 'Competition', '1st Place', 'Approved', 'im the winner', '2026-03-21', '2026-03-21 19:24:23', NULL),
(2, 8, 13, 'Certificate of Participation – shout', 'Certificate', 'Competition', '2nd Place', 'Approved', NULL, '2026-03-21', '2026-03-21 19:24:23', NULL),
(3, 8, 11, 'Certificate of Participation – laugh', 'Certificate', 'Sport', NULL, 'None', '', '2026-03-26', '2026-04-14 00:14:58', NULL),
(4, 241, 14, 'Certificate of Participation – eat', 'Certificate', 'Sport', NULL, 'None', '', '2026-04-16', '2026-04-16 21:55:57', NULL),
(5, 8, 14, 'Certificate of Participation – eat', 'Certificate', 'Sport', NULL, 'None', '', '2026-04-16', '2026-04-17 14:30:13', NULL),
(6, 241, NULL, 'sleep', 'Award', 'Competition', '1st', 'Approved', '', '2026-04-14', '2026-04-17 15:41:27', 'ext_1776411687_241.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `club`
--

CREATE TABLE `club` (
  `club_id` int(10) NOT NULL,
  `club_name` varchar(50) NOT NULL,
  `club_CreateDate` date NOT NULL,
  `club_description` varchar(500) NOT NULL,
  `club_status` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `club`
--

INSERT INTO `club` (`club_id`, `club_name`, `club_CreateDate`, `club_description`, `club_status`) VALUES
(1, 'sleep club', '2026-03-09', 'a club that can let u sleep anytime at school', 'Available'),
(4, 'Aikido Club', '2026-04-17', 'Lets learn aikido together!!!!!', 'Available'),
(5, 'Skateboard club', '2026-04-17', 'Lets have fun together', 'Reject'),
(10, 'English club', '2026-04-17', 'lets learn pro english', 'Close'),
(11, 'Math lover club', '2026-04-17', 'lets learn math together', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `club_membership`
--

CREATE TABLE `club_membership` (
  `membership_Id` int(255) NOT NULL,
  `userId` int(255) NOT NULL,
  `clubId` int(255) NOT NULL,
  `club_role` enum('Member','Chairperson','Vice Chairperson','Secretary','Assistant Secretary','Treasurer','Assistant Treasurer','Auditor','Committee') DEFAULT 'Member',
  `club_joinDate` datetime NOT NULL,
  `register_status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `club_membership`
--

INSERT INTO `club_membership` (`membership_Id`, `userId`, `clubId`, `club_role`, `club_joinDate`, `register_status`) VALUES
(1, 7, 1, 'Committee', '2026-03-09 09:27:43', 'Approved'),
(3, 8, 1, 'Secretary', '2026-03-20 12:21:58', 'Approved'),
(4, 241, 1, 'Chairperson', '2026-04-13 07:03:17', 'Approved'),
(8, 340, 4, 'Chairperson', '2026-04-17 15:42:41', 'Approved'),
(9, 240, 5, 'Chairperson', '2026-04-17 16:06:43', 'Approved'),
(10, 8, 4, 'Member', '2026-04-17 16:28:17', 'Approved'),
(11, 240, 4, 'Member', '2026-04-17 16:37:00', 'Rejected'),
(12, 341, 4, 'Member', '2026-04-17 16:59:42', 'Approved'),
(13, 341, 1, 'Member', '2026-04-17 16:59:43', 'Pending'),
(18, 342, 10, 'Chairperson', '2026-04-17 17:35:49', 'Approved'),
(19, 342, 4, 'Member', '2026-04-17 17:37:08', 'Approved'),
(20, 342, 1, 'Member', '2026-04-17 17:37:09', 'Pending'),
(21, 343, 11, 'Chairperson', '2026-04-17 17:44:03', 'Approved'),
(22, 343, 10, 'Member', '2026-04-17 17:45:06', 'Approved'),
(23, 343, 4, 'Member', '2026-04-17 17:45:07', 'Pending'),
(25, 344, 10, 'Member', '2026-04-17 17:52:07', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `EventId` int(10) NOT NULL,
  `UserId` int(10) NOT NULL,
  `EventName` varchar(50) NOT NULL,
  `EventType` varchar(30) NOT NULL,
  `EventInfo` varchar(500) NOT NULL,
  `EventDate` date NOT NULL,
  `EventStartTime` time NOT NULL,
  `EventEndTime` time NOT NULL,
  `EventBlock` varchar(10) NOT NULL,
  `EventHall` varchar(50) NOT NULL,
  `EventImage` varchar(255) NOT NULL,
  `EventStatus` varchar(10) NOT NULL,
  `Register_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`EventId`, `UserId`, `EventName`, `EventType`, `EventInfo`, `EventDate`, `EventStartTime`, `EventEndTime`, `EventBlock`, `EventHall`, `EventImage`, `EventStatus`, `Register_date`) VALUES
(9, 7, 'badminton Tournament', 'Competition', 'a competitive that show how to play badminton and get the champion ', '2026-03-19', '21:00:00', '22:59:00', 'M', 'Grand Hall', 'image/badminton.jpg', 'Ended', '2026-03-08 14:00:05'),
(10, 8, 'hello world', 'Workshop', 'a world that say hello', '2026-03-18', '01:18:00', '00:17:00', 'L', 'Small Hall 1', 'image/sleep.jpg', 'Ended', '2026-03-08 15:16:30'),
(11, 7, 'laugh', 'Sport', 'who laugh longer, then win ', '2026-03-26', '02:00:00', '04:00:00', 'A', 'Main Hall', '0', 'Ended', '2026-03-20 19:44:10'),
(12, 7, 'cry', 'Competition', 'who can cry loudly', '2026-03-21', '16:30:00', '16:31:00', 'I', 'Small Hall 1', '0', 'Ended', '2026-03-21 09:27:44'),
(13, 7, 'shout', 'Competition', 'who shout loudly', '2026-03-21', '19:21:00', '19:22:00', 'L', 'Small Hall 1', '0', 'Ended', '2026-03-21 12:19:39'),
(14, 8, 'eat', 'Sport', 'who can eat more ', '2026-04-16', '21:50:00', '21:55:00', 'A', 'Main Hall', '0', 'Ended', '2026-04-16 15:45:47'),
(15, 345, 'abc', 'Event', 'abc', '2026-04-18', '00:27:00', '00:28:00', 'A', 'General Hall', '', 'Approved', '2026-04-17 18:27:42'),
(16, 8, 'aaaaa', 'Competition', 'aaaaaa', '2026-04-18', '00:30:00', '00:35:00', 'A', 'General Hall', '', 'Approved', '2026-04-17 18:30:23');

-- --------------------------------------------------------

--
-- Table structure for table `event_participant`
--

CREATE TABLE `event_participant` (
  `id` int(11) NOT NULL,
  `userid` int(11) DEFAULT NULL,
  `eventid` int(11) DEFAULT NULL,
  `join_date` datetime DEFAULT NULL,
  `merit_created` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_participant`
--

INSERT INTO `event_participant` (`id`, `userid`, `eventid`, `join_date`, `merit_created`) VALUES
(1, 8, 11, '2026-03-21 12:09:51', 1),
(2, 8, 12, '2026-03-21 16:28:07', 1),
(3, 8, 13, '2026-03-21 19:19:58', 1),
(7, 8, 14, '2026-04-16 21:46:59', 1),
(8, 241, 14, '2026-04-16 21:49:08', 1),
(9, 8, 16, '2026-04-18 00:30:46', 0);

-- --------------------------------------------------------

--
-- Table structure for table `merit`
--

CREATE TABLE `merit` (
  `merit_id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `activity_name` varchar(255) NOT NULL,
  `hours` decimal(5,2) NOT NULL,
  `merit_description` text DEFAULT NULL,
  `merit_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `graded_by` int(11) DEFAULT NULL COMMENT 'userid of who gave the grade',
  `graded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `merit`
--

INSERT INTO `merit` (`merit_id`, `userid`, `event_id`, `activity_name`, `hours`, `merit_description`, `merit_date`, `status`, `graded_by`, `graded_at`) VALUES
(1, 8, 12, 'cry', 0.02, NULL, '2026-03-21 00:00:00', 'approved', NULL, NULL),
(2, 8, 13, 'shout', 0.02, NULL, '2026-03-21 00:00:00', 'approved', NULL, NULL),
(3, 8, 11, 'laugh', 2.00, NULL, '2026-03-26 00:00:00', 'approved', NULL, NULL),
(4, 241, 14, 'eat', 0.10, 'thanks for coming', '2026-04-16 00:00:00', 'approved', 8, '2026-04-17 14:29:13'),
(5, 8, 14, 'eat', 0.10, 'ssleep', '2026-04-16 00:00:00', 'approved', 8, '2026-04-17 14:29:13');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(50) NOT NULL,
  `register_date` datetime NOT NULL,
  `role` varchar(10) NOT NULL DEFAULT 'student',
  `last_online` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `email`, `register_date`, `role`, `last_online`) VALUES
(7, 'Alex', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'alex@gmail.com', '2026-03-08 13:25:51', 'admin', NULL),
(8, 'jiawei', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'jiawei@gmail.com', '2026-03-08 13:26:17', 'admin', NULL),
(9, 'jianxiong', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'jian@gmail.com', '2026-03-08 13:27:49', 'admin', NULL),
(10, 'yongsheng', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'yong@gmail.com', '2026-03-08 13:28:20', 'admin', NULL),
(240, 'Rahul Razak', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'rahul.razak1@example.com', '0000-00-00 00:00:00', 'student', NULL),
(241, 'David Tan', '$2y$10$5p6pl4tSEhTaqmYJ1z28TOE/pkItXp.Lw6q37DmY95tnxvnaNsYfC', 'david.tan2@example.com', '0000-00-00 00:00:00', 'student', NULL),
(242, 'Suraya Walker', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'suraya.walker3@example.com', '0000-00-00 00:00:00', 'student', NULL),
(243, 'Tao Dahlan', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'tao.dahlan4@example.com', '0000-00-00 00:00:00', 'student', NULL),
(244, 'Ibrahim Nair', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'ibrahim.nair5@example.com', '0000-00-00 00:00:00', 'student', NULL),
(245, 'Harish Osman', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'harish.osman6@example.com', '0000-00-00 00:00:00', 'student', NULL),
(246, 'Chen Low', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'chen.low7@example.com', '0000-00-00 00:00:00', 'student', NULL),
(247, 'Idris Ooi', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'idris.ooi8@example.com', '0000-00-00 00:00:00', 'student', NULL),
(248, 'Suraya See', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'suraya.see9@example.com', '0000-00-00 00:00:00', 'student', NULL),
(249, 'Xiang Young', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'xiang.young10@example.com', '0000-00-00 00:00:00', 'student', NULL),
(250, 'Adam Mani', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'adam.mani11@example.com', '0000-00-00 00:00:00', 'student', NULL),
(251, 'Eric Pua', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'eric.pua12@example.com', '0000-00-00 00:00:00', 'student', NULL),
(252, 'Ashley See', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'ashley.see13@example.com', '0000-00-00 00:00:00', 'student', NULL),
(253, 'Sanjay Walker', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'sanjay.walker14@example.com', '0000-00-00 00:00:00', 'student', NULL),
(254, 'Shuo Das', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'shuo.das15@example.com', '0000-00-00 00:00:00', 'student', NULL),
(255, 'Amit Eng', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'amit.eng16@example.com', '0000-00-00 00:00:00', 'student', NULL),
(256, 'Syafiq Loo', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'syafiq.loo17@example.com', '0000-00-00 00:00:00', 'student', NULL),
(257, 'Jannah Brown', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'jannah.brown18@example.com', '0000-00-00 00:00:00', 'student', NULL),
(258, 'Lokesh Ishaq', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'lokesh.ishaq19@example.com', '0000-00-00 00:00:00', 'student', NULL),
(259, 'Suresh Singh', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'suresh.singh20@example.com', '0000-00-00 00:00:00', 'student', NULL),
(260, 'Eric Idris', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'eric.idris21@example.com', '0000-00-00 00:00:00', 'student', NULL),
(261, 'Divya Smith', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'divya.smith22@example.com', '0000-00-00 00:00:00', 'student', NULL),
(262, 'Michael Brown', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'michael.brown23@example.com', '0000-00-00 00:00:00', 'student', NULL),
(263, 'Emily Choo', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'emily.choo24@example.com', '0000-00-00 00:00:00', 'student', NULL),
(264, 'Arvind Iyer', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'arvind.iyer25@example.com', '0000-00-00 00:00:00', 'student', NULL),
(265, 'An Loo', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'an.loo26@example.com', '0000-00-00 00:00:00', 'student', NULL),
(266, 'Mia Low', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'mia.low27@example.com', '0000-00-00 00:00:00', 'student', NULL),
(267, 'Ashley Ramasamy', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'ashley.ramasamy28@example.com', '0000-00-00 00:00:00', 'student', NULL),
(268, 'Karthik Lim', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'karthik.lim29@example.com', '0000-00-00 00:00:00', 'student', NULL),
(269, 'Jun Martin', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'jun.martin30@example.com', '0000-00-00 00:00:00', 'student', NULL),
(270, 'Hakim Ariffin', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'hakim.ariffin31@example.com', '0000-00-00 00:00:00', 'student', NULL),
(271, 'Hui Lee', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'hui.lee32@example.com', '0000-00-00 00:00:00', 'student', NULL),
(272, 'Abhishek Tay', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'abhishek.tay33@example.com', '0000-00-00 00:00:00', 'student', NULL),
(273, 'Prakash Taylor', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'prakash.taylor34@example.com', '0000-00-00 00:00:00', 'student', NULL),
(274, 'Aisyah Sim', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'aisyah.sim35@example.com', '0000-00-00 00:00:00', 'student', NULL),
(275, 'Max Ghani', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'max.ghani36@example.com', '0000-00-00 00:00:00', 'student', NULL),
(276, 'Hafiz Ooi', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'hafiz.ooi37@example.com', '0000-00-00 00:00:00', 'student', NULL),
(277, 'Ishaan Walker', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'ishaan.walker38@example.com', '0000-00-00 00:00:00', 'student', NULL),
(278, 'Varun Choo', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'varun.choo39@example.com', '0000-00-00 00:00:00', 'student', NULL),
(279, 'Noah Ali', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'noah.ali40@example.com', '0000-00-00 00:00:00', 'student', NULL),
(280, 'Vikram Ng', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'vikram.ng41@example.com', '0000-00-00 00:00:00', 'student', NULL),
(281, 'Grace Joshi', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'grace.joshi42@example.com', '0000-00-00 00:00:00', 'student', NULL),
(282, 'Aiden Khan', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'aiden.khan43@example.com', '0000-00-00 00:00:00', 'student', NULL),
(283, 'Danial Ho', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'danial.ho44@example.com', '0000-00-00 00:00:00', 'student', NULL),
(284, 'Mia Lee', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'mia.lee45@example.com', '0000-00-00 00:00:00', 'student', NULL),
(285, 'Anjali Brown', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'anjali.brown46@example.com', '0000-00-00 00:00:00', 'student', NULL),
(286, 'Lucas Khan', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'lucas.khan47@example.com', '0000-00-00 00:00:00', 'student', NULL),
(287, 'Irfan Wong', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'irfan.wong48@example.com', '0000-00-00 00:00:00', 'student', NULL),
(288, 'Xing Tan', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'xing.tan49@example.com', '0000-00-00 00:00:00', 'student', NULL),
(289, 'Aisyah Omar', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'aisyah.omar50@example.com', '0000-00-00 00:00:00', 'student', NULL),
(290, 'Ryan Pang', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'ryan.pang51@example.com', '0000-00-00 00:00:00', 'student', NULL),
(291, 'Ling Patel', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'ling.patel52@example.com', '0000-00-00 00:00:00', 'student', NULL),
(292, 'Batrisya Anderson', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'batrisya.anderson53@example.com', '0000-00-00 00:00:00', 'student', NULL),
(293, 'Ryan Morris', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'ryan.morris54@example.com', '0000-00-00 00:00:00', 'student', NULL),
(294, 'Varun Martin', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'varun.martin55@example.com', '0000-00-00 00:00:00', 'student', NULL),
(295, 'Chloe Morris', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'chloe.morris56@example.com', '0000-00-00 00:00:00', 'student', NULL),
(296, 'John Das', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'john.das57@example.com', '0000-00-00 00:00:00', 'student', NULL),
(297, 'Jason Hashim', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'jason.hashim58@example.com', '0000-00-00 00:00:00', 'student', NULL),
(298, 'Vijay Elias', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'vijay.elias59@example.com', '0000-00-00 00:00:00', 'student', NULL),
(299, 'Hafiz Low', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'hafiz.low60@example.com', '0000-00-00 00:00:00', 'student', NULL),
(300, 'Nur Chong', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'nur.chong61@example.com', '0000-00-00 00:00:00', 'student', NULL),
(301, 'Zhi Allen', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'zhi.allen62@example.com', '0000-00-00 00:00:00', 'student', NULL),
(302, 'Jessica Ghani', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'jessica.ghani63@example.com', '0000-00-00 00:00:00', 'student', NULL),
(303, 'Megan Anderson', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'megan.anderson64@example.com', '0000-00-00 00:00:00', 'student', NULL),
(304, 'Rohan Dahlan', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'rohan.dahlan65@example.com', '0000-00-00 00:00:00', 'student', NULL),
(305, 'Rizwan Ishaq', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'rizwan.ishaq66@example.com', '0000-00-00 00:00:00', 'student', NULL),
(306, 'Luna Nair', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'luna.nair67@example.com', '0000-00-00 00:00:00', 'student', NULL),
(307, 'Qiang Stewart', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'qiang.stewart68@example.com', '0000-00-00 00:00:00', 'student', NULL),
(308, 'Suresh Teo', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'suresh.teo69@example.com', '0000-00-00 00:00:00', 'student', NULL),
(309, 'Sai Idris', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'sai.idris70@example.com', '0000-00-00 00:00:00', 'student', NULL),
(310, 'Leo Evans', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'leo.evans71@example.com', '0000-00-00 00:00:00', 'student', NULL),
(311, 'James Khoo', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'james.khoo72@example.com', '0000-00-00 00:00:00', 'student', NULL),
(312, 'Ping Thomas', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'ping.thomas73@example.com', '0000-00-00 00:00:00', 'student', NULL),
(313, 'Tao Stewart', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'tao.stewart74@example.com', '0000-00-00 00:00:00', 'student', NULL),
(314, 'Anuar Hashim', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'anuar.hashim75@example.com', '0000-00-00 00:00:00', 'student', NULL),
(315, 'Anjali Mahmud', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'anjali.mahmud76@example.com', '0000-00-00 00:00:00', 'student', NULL),
(316, 'Ibrahim Pillay', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'ibrahim.pillay77@example.com', '0000-00-00 00:00:00', 'student', NULL),
(317, 'Jason Mani', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'jason.mani78@example.com', '0000-00-00 00:00:00', 'student', NULL),
(318, 'Krish Cook', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'krish.cook79@example.com', '0000-00-00 00:00:00', 'student', NULL),
(319, 'Jason Ramasamy', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'jason.ramasamy80@example.com', '0000-00-00 00:00:00', 'student', NULL),
(320, 'Shuo Evans', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'shuo.evans81@example.com', '0000-00-00 00:00:00', 'student', NULL),
(321, 'Xiao Kulkarni', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'xiao.kulkarni82@example.com', '0000-00-00 00:00:00', 'student', NULL),
(322, 'Jessica Mehta', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'jessica.mehta83@example.com', '0000-00-00 00:00:00', 'student', NULL),
(323, 'Thivya Tiong', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'thivya.tiong84@example.com', '0000-00-00 00:00:00', 'student', NULL),
(324, 'Izzat Low', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'izzat.low85@example.com', '0000-00-00 00:00:00', 'student', NULL),
(325, 'Siti Thomas', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'siti.thomas86@example.com', '0000-00-00 00:00:00', 'student', NULL),
(326, 'Bo Pandey', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'bo.pandey87@example.com', '0000-00-00 00:00:00', 'student', NULL),
(327, 'Sarah Choo', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'sarah.choo88@example.com', '0000-00-00 00:00:00', 'student', NULL),
(328, 'Jun Varma', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'jun.varma89@example.com', '0000-00-00 00:00:00', 'student', NULL),
(329, 'Krish Babu', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'krish.babu90@example.com', '0000-00-00 00:00:00', 'student', NULL),
(330, 'Vijay Cook', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'vijay.cook91@example.com', '0000-00-00 00:00:00', 'student', NULL),
(331, 'Jyothi Fadzil', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'jyothi.fadzil92@example.com', '0000-00-00 00:00:00', 'student', NULL),
(332, 'Alex Ang', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'alex.ang93@example.com', '0000-00-00 00:00:00', 'student', NULL),
(333, 'Krish Liew', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'krish.liew94@example.com', '0000-00-00 00:00:00', 'student', NULL),
(334, 'Arjun Pillay', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'arjun.pillay95@example.com', '0000-00-00 00:00:00', 'student', NULL),
(335, 'Zahra Siew', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'zahra.siew96@example.com', '0000-00-00 00:00:00', 'student', NULL),
(336, 'Siti Subramaniam', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'siti.subramaniam97@example.com', '0000-00-00 00:00:00', 'student', NULL),
(337, 'Ganesh Devi', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'ganesh.devi98@example.com', '0000-00-00 00:00:00', 'student', NULL),
(338, 'Yong Bakri', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'yong.bakri99@example.com', '0000-00-00 00:00:00', 'student', NULL),
(339, 'Jyothi Baker', '$2y$10$4dWLoMA/rY3dSkHTzlOdKuHLgoQ7xgGk1Jye90Ikp797qAALiAs6S', 'jyothi.baker100@example.com', '0000-00-00 00:00:00', 'student', NULL),
(340, 'abc666', '$2y$10$UF0VEfyB5cAm3agqHTKp/.skULRyDhLvW.gLz3/p9Rn0U8ytjNWf2', 'abc@gmail.com', '2026-04-17 15:41:15', 'student', NULL),
(341, 'ali', '$2y$10$8PoHjA8unbKvq1GxgUpBk.gleyeOaSPKKtm./PpU.dsL/OXOmE05.', 'ali@gmail.com', '2026-04-17 16:46:19', 'student', NULL),
(342, 'abu', '$2y$10$iZTJJK5LgCKKBta2fye3Xeiz1xHx8IyrCk659ag3x.fH2w8fmGyAy', 'abu@gmail.com', '2026-04-17 17:07:37', 'student', NULL),
(343, 'chin', '$2y$10$JmT2E25Go6aOybTymoztLuBsJvozRwlWTRUKAUQYxQVxt2yABXl0S', 'chin@gmail.com', '2026-04-17 17:41:52', 'student', NULL),
(344, '123', '$2y$10$KdEXuAmXmbbIOajAE7A.POm2Eb.S2RhifL48NN/0DC7Sh6nljMhIm', '123@gmail.com', '2026-04-17 17:49:32', 'student', NULL),
(345, 'jiawei123', '$2y$10$DSEN0criiKvfaf6rGuMiDO/m0RytBVQX2j9q/OfJZklbfF0h3mlcy', '123@gmail.com', '2026-04-17 18:15:50', 'student', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievement`
--
ALTER TABLE `achievement`
  ADD PRIMARY KEY (`achievement_id`),
  ADD KEY `userid` (`userid`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `club`
--
ALTER TABLE `club`
  ADD PRIMARY KEY (`club_id`);

--
-- Indexes for table `club_membership`
--
ALTER TABLE `club_membership`
  ADD PRIMARY KEY (`membership_Id`),
  ADD KEY `userId` (`userId`),
  ADD KEY `clubId` (`clubId`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`EventId`),
  ADD KEY `event_ibfk_1` (`UserId`);

--
-- Indexes for table `event_participant`
--
ALTER TABLE `event_participant`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `userid` (`userid`,`eventid`),
  ADD KEY `eventid` (`eventid`);

--
-- Indexes for table `merit`
--
ALTER TABLE `merit`
  ADD PRIMARY KEY (`merit_id`),
  ADD KEY `userid` (`userid`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievement`
--
ALTER TABLE `achievement`
  MODIFY `achievement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `club`
--
ALTER TABLE `club`
  MODIFY `club_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `club_membership`
--
ALTER TABLE `club_membership`
  MODIFY `membership_Id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
  MODIFY `EventId` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `event_participant`
--
ALTER TABLE `event_participant`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `merit`
--
ALTER TABLE `merit`
  MODIFY `merit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=346;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `achievement`
--
ALTER TABLE `achievement`
  ADD CONSTRAINT `achievement_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `achievement_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `event` (`EventId`) ON DELETE SET NULL;

--
-- Constraints for table `club_membership`
--
ALTER TABLE `club_membership`
  ADD CONSTRAINT `club_membership_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `club_membership_ibfk_2` FOREIGN KEY (`clubId`) REFERENCES `club` (`club_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event_participant`
--
ALTER TABLE `event_participant`
  ADD CONSTRAINT `event_participant_ibfk_1` FOREIGN KEY (`eventid`) REFERENCES `event` (`EventId`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `event_participant_ibfk_2` FOREIGN KEY (`userid`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `merit`
--
ALTER TABLE `merit`
  ADD CONSTRAINT `merit_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `merit_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `event` (`EventId`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
