-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 07, 2026 at 06:20 PM
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
-- Database: `clinic_system_demo_v2`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `role_target` varchar(50) NOT NULL DEFAULT 'All',
  `province` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `facility` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `patient_id` int(11) DEFAULT NULL,
  `source_role` varchar(50) DEFAULT NULL,
  `emergency_type` varchar(100) DEFAULT NULL,
  `alert_status` varchar(30) DEFAULT NULL,
  `alerted_staff_id` int(11) DEFAULT NULL,
  `medi_alert_sent_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `message`, `created_by`, `role_target`, `province`, `city`, `facility`, `created_at`, `priority`, `patient_id`, `source_role`, `emergency_type`, `alert_status`, `alerted_staff_id`, `medi_alert_sent_at`) VALUES
(1, 'check stock', 1, 'Nurse', 'Eastern Cape', 'Qonce', 'Clinic', '2026-06-07 12:23:41', 'normal', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'clinic loses at 15;00 today', 1, 'All', 'Eastern Cape', 'Qonce', 'Clinic', '2026-06-07 13:07:53', 'normal', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'EMERGENCY — Cardiac / Heart attack\nPatient: Luyanda Ndlovu\ntest', 2, 'Reception', 'Eastern Cape', 'Qonce', 'Clinic', '2026-06-07 15:24:43', 'emergency', 7, 'Nurse', 'Cardiac / Heart attack', 'acknowledged', 3, '2026-06-07 15:28:11'),
(4, 'check stock', 11, 'Nurse', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 16:12:51', 'normal', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'clinic closing at 18:00 today', 11, 'All', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 16:13:13', 'normal', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'EMERGENCY — Cardiac / Heart attack\nPatient: Sipho Mthembu\ntest', 12, 'Reception', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 16:15:05', 'emergency', 15, 'Nurse', 'Cardiac / Heart attack', 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('Scheduled','Checked_In','Completed','Cancelled') DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `facility_id`, `appointment_date`, `appointment_time`, `status`, `notes`, `created_at`) VALUES
(1, 1, 1, 1, '2026-06-07', '10:00:00', 'Scheduled', NULL, '2026-06-07 09:18:40'),
(4, 8, 3, 2, '2026-06-07', '15:30:00', 'Scheduled', 'flu test', '2026-06-07 12:20:20'),
(5, 9, 3, 2, '2026-06-07', '16:00:00', 'Scheduled', 'test', '2026-06-07 12:23:13'),
(8, 16, 13, 3, '2026-06-07', '10:00:00', 'Scheduled', 'testing system', '2026-06-07 16:12:26');

-- --------------------------------------------------------

--
-- Table structure for table `consultations`
--

CREATE TABLE `consultations` (
  `consultation_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consultations`
--

INSERT INTO `consultations` (`consultation_id`, `appointment_id`, `doctor_id`, `diagnosis`, `treatment`, `notes`, `created_at`) VALUES
(2, NULL, 3, 'patient test', 'test', 'testing doctor notes', '2026-06-07 14:03:02'),
(3, NULL, 3, 'test', 'test', 'test', '2026-06-07 14:04:31'),
(6, NULL, 3, 'Pending — referral', 'Referral', 'Referral initiated: [Beyond expertise] test', '2026-06-07 15:44:27'),
(7, NULL, 3, 'test', 'test', 'test', '2026-06-07 15:44:53'),
(8, NULL, 3, 'Pending — referral', 'Referral', 'Referral initiated: [Specialist required] test', '2026-06-07 15:46:08'),
(9, NULL, 3, 'Pending — referral', 'Referral', 'Referral initiated: [Beyond expertise] test5', '2026-06-07 15:48:57');

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `facility_id` int(11) NOT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `facility_name` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`facility_id`, `province`, `city`, `facility_name`) VALUES
(1, 'Western Cape', 'Durbanville', 'Durbanville Clinic'),
(2, 'Eastern Cape', 'Qonce', 'Clinic'),
(3, 'Northern Cape', 'Kimberley', 'Clinic');

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `incident_id` int(11) NOT NULL,
  `patient_name` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `channel` enum('SMS','WhatsApp','Email') DEFAULT NULL,
  `status` enum('Open','In Progress','Resolved') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

CREATE TABLE `medications` (
  `medication_id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `prescription_id` int(11) NOT NULL,
  `consultation_id` int(11) DEFAULT NULL,
  `medication_id` int(11) DEFAULT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `referral_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `consultation_id` int(11) NOT NULL,
  `consultation_details` text DEFAULT NULL,
  `referring_facility_id` int(11) NOT NULL,
  `referred_to_facility_id` int(11) NOT NULL,
  `referral_status` enum('Pending','Accepted','Completed','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`referral_id`, `patient_id`, `doctor_id`, `consultation_id`, `consultation_details`, `referring_facility_id`, `referred_to_facility_id`, `referral_status`, `created_at`, `updated_at`) VALUES
(1, 7, 3, 6, '[Beyond expertise] test', 2, 1, 'Pending', '2026-06-07 15:44:27', '2026-06-07 15:44:27'),
(2, 7, 3, 8, '[Specialist required] test', 2, 1, 'Pending', '2026-06-07 15:46:08', '2026-06-07 15:46:08'),
(3, 7, 3, 9, '[Beyond expertise] test5', 2, 1, 'Pending', '2026-06-07 15:48:57', '2026-06-07 15:48:57');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL,
  `incident_id` int(11) DEFAULT NULL,
  `assigned_to` varchar(150) DEFAULT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `status` enum('Open','Working','Resolved') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `account_type` enum('staff','patient') NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `full_name` varchar(200) GENERATED ALWAYS AS (concat(`first_name`,' ',`surname`)) STORED,
  `id_number` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `employee_number` varchar(50) DEFAULT NULL,
  `role` enum('admin','reception','nurse','doctor','pharmacist') DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `facility` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Waiting',
  `department` varchar(50) DEFAULT 'Reception',
  `bp` varchar(20) DEFAULT NULL,
  `temp` varchar(20) DEFAULT NULL,
  `pulse` varchar(20) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `medication` text DEFAULT NULL,
  `facility_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `account_type`, `first_name`, `surname`, `id_number`, `phone`, `email`, `password`, `employee_number`, `role`, `province`, `city`, `facility`, `created_at`, `status`, `department`, `bp`, `temp`, `pulse`, `weight`, `notes`, `diagnosis`, `prescription`, `medication`, `facility_id`) VALUES
(1, 'staff', 'John', 'Reception', '9001010000001', '0710000001', 'reception@clinic.com', '123456', 'EMP001', 'reception', 'Eastern Cape', 'Qonce', 'Clinic', '2026-06-07 09:42:23', 'Waiting', 'Reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2),
(2, 'staff', 'Anna', 'Nurse', '9001010000002', '0710000002', 'nurse@clinic.com', '123456', 'EMP002', 'nurse', 'Eastern Cape', 'Qonce', 'Clinic', '2026-06-07 09:42:23', 'Waiting', 'Reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2),
(3, 'staff', 'David', 'Doctor', '9001010000003', '0710000003', 'doctor@clinic.com', '123456', 'EMP003', 'doctor', 'Eastern Cape', 'Qonce', 'Clinic', '2026-06-07 09:42:23', 'Waiting', 'Reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2),
(4, 'staff', 'Sarah', 'Pharma', '9001010000004', '0710000004', 'pharma@clinic.com', '123456', 'EMP004', 'pharmacist', 'Eastern Cape', 'Qonce', 'Clinic', '2026-06-07 09:42:23', 'Waiting', 'Reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2),
(5, 'patient', 'Sipho', 'Mbeki', '9101015001081', '0721110001', 'sipho.mbeki@test.com', '123456', NULL, NULL, 'Eastern Cape', 'Qonce', 'Clinic', '2026-06-07 11:45:09', 'Waiting', 'Reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2),
(6, 'patient', 'Thandi', 'Dlamini', '9202026002082', '0721110002', 'thandi.dlamini@test.com', '123456', NULL, NULL, 'Eastern Cape', 'Qonce', 'Clinic', '2026-06-07 11:45:09', 'Waiting', 'Reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2),
(7, 'patient', 'Luyanda', 'Ndlovu', '9303037003083', '0721110003', 'luyanda.ndlovu@test.com', '123456', NULL, NULL, 'Eastern Cape', 'Qonce', 'Clinic', '2026-06-07 11:45:09', 'Referred', 'Referral', NULL, NULL, NULL, NULL, 'Referred: [Beyond expertise] test5', 'test', 'test', NULL, 2),
(8, 'patient', 'Ayanda', 'Zulu', '9404048004084', '0721110004', 'ayanda.zulu@test.com', '123456', NULL, NULL, 'Eastern Cape', 'Qonce', 'Clinic', '2026-06-07 11:45:09', 'Completed', 'Pharmacy', '125/80', '38', '115pm', '70kg', 'Pharmacy: Medication dispensed.', 'patient test', 'test', 'test', 2),
(9, 'patient', 'Bongani', 'Khumalo', '9505059005085', '0721110005', 'bongani.khumalo@test.com', '123456', NULL, NULL, 'Eastern Cape', 'Qonce', 'Clinic', '2026-06-07 11:45:09', 'Completed', 'Pharmacy', '125/80', '37', '120pm', '70kg', 'Pharmacy: Medication dispensed.', 'test', 'test', 'test', 2),
(10, 'patient', 'Elandre', 'Booth', '9829276563098', '0712484099', '', '', NULL, NULL, 'Eastern Cape', 'Qonce', 'Clinic', '2026-06-07 13:49:42', 'With Nurse', 'Reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2),
(11, 'staff', 'Thabo', 'Mokoena', '8001015009087', '0712341001', 'reception@northerncapeclinic.com', '123456', 'EMP101', 'reception', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 15:59:58', 'Active', 'Reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'staff', 'Lerato', 'Nkosi', '8202026009088', '0712341002', 'nurse@northerncapeclinic.com', '123456', 'EMP102', 'nurse', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 15:59:58', 'Active', 'Nursing', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'staff', 'Dr John', 'Mabaso', '7903037009089', '0712341003', 'doctor@northerncapeclinic.com', '123456', 'EMP103', 'doctor', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 15:59:58', 'Active', 'Medical', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'staff', 'Nandi', 'Dlamini', '8504048009090', '0712341004', 'pharma@northerncapeclinic.com', '123456', 'EMP104', 'pharmacist', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 15:59:58', 'Active', 'Pharmacy', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'patient', 'Sipho', 'Mthembu', '9005059009091', '0712342001', 'sipho@test.com', '123456', NULL, '', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 15:59:58', 'With Nurse', 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'patient', 'Ayanda', 'Zulu', '9106069009092', '0712342002', 'ayanda@test.com', '123456', NULL, '', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 15:59:58', 'With Nurse', 'Nurse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 'patient', 'Luyanda', 'Ndlovu', '9207079009093', '0712342003', 'luyanda@test.com', '123456', NULL, '', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 15:59:58', 'With Nurse', 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 'patient', 'Thandi', 'Khumalo', '9308089009094', '0712342004', 'thandi@test.com', '123456', NULL, '', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 15:59:58', 'Waiting', 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'patient', 'Bongani', 'Maseko', '9409099009095', '0712342005', 'bongani@test.com', '123456', NULL, '', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 15:59:58', 'Waiting', 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(29, 'staff', 'Thabo', 'Mokoena', '8001015009087', '0712343001', 'reception2@clinic.com', '123456', 'EMP201', 'reception', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 16:01:44', 'Active', 'Reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(30, 'staff', 'Lerato', 'Nkosi', '8202026009088', '0712343002', 'nurse2@clinic.com', '123456', 'EMP202', 'nurse', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 16:01:44', 'Active', 'Nursing', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(31, 'staff', 'Dr John', 'Mabaso', '7903037009089', '0712343003', 'doctor2@clinic.com', '123456', 'EMP203', 'doctor', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 16:01:44', 'Active', 'Medical', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(32, 'staff', 'Nandi', 'Dlamini', '8504048009090', '0712343004', 'pharma2@clinic.com', '123456', 'EMP204', 'pharmacist', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 16:01:44', 'Active', 'Pharmacy', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(33, 'patient', 'Sipho', 'Mthembu', '9005059009091', '0712344001', 'sipho2@test.com', '123456', NULL, '', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 16:01:44', 'Waiting', 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(34, 'patient', 'Ayanda', 'Zulu', '9106069009092', '0712344002', 'ayanda2@test.com', '123456', NULL, '', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 16:01:44', 'With Nurse', 'Nurse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(35, 'patient', 'Luyanda', 'Ndlovu', '9207079009093', '0712344003', 'luyanda2@test.com', '123456', NULL, '', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 16:01:44', 'Waiting', 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 'patient', 'Thandi', 'Khumalo', '9308089009094', '0712344004', 'thandi2@test.com', '123456', NULL, '', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 16:01:44', 'Waiting', 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(37, 'patient', 'Bongani', 'Maseko', '9409099009095', '0712344005', 'bongani2@test.com', '123456', NULL, '', 'Northern Cape', 'Kimberley', 'Clinic', '2026-06-07 16:01:44', 'Waiting', 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(38, 'staff', 'Michael', 'Smith', '8001015109087', '0723001001', 'reception_wc@clinic.com', '123456', 'EMP301', 'reception', 'Western Cape', 'Cape Town', 'Clinic', '2026-06-07 16:03:52', 'Active', 'Reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(39, 'staff', 'Zanele', 'Adams', '8202026209088', '0723001002', 'nurse_wc@clinic.com', '123456', 'EMP302', 'nurse', 'Western Cape', 'Cape Town', 'Clinic', '2026-06-07 16:03:52', 'Active', 'Nursing', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(40, 'staff', 'Dr Peter', 'Daniels', '7903037309089', '0723001003', 'doctor_wc@clinic.com', '123456', 'EMP303', 'doctor', 'Western Cape', 'Cape Town', 'Clinic', '2026-06-07 16:03:52', 'Active', 'Medical', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(41, 'staff', 'Fatima', 'Peters', '8504048409090', '0723001004', 'pharma_wc@clinic.com', '123456', 'EMP304', 'pharmacist', 'Western Cape', 'Cape Town', 'Clinic', '2026-06-07 16:03:52', 'Active', 'Pharmacy', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(42, 'patient', 'Siyabonga', 'Khumalo', '9005059109091', '0723002001', 'siyabonga_wc@test.com', '123456', NULL, '', 'Western Cape', 'Cape Town', 'Clinic', '2026-06-07 16:03:52', 'Waiting', 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(43, 'patient', 'Nomvula', 'Nkosi', '9106069109092', '0723002002', 'nomvula_wc@test.com', '123456', NULL, '', 'Western Cape', 'Cape Town', 'Clinic', '2026-06-07 16:03:52', 'Waiting', 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(44, 'patient', 'Brandon', 'Jacobs', '9207079109093', '0723002003', 'brandon_wc@test.com', '123456', NULL, '', 'Western Cape', 'Cape Town', 'Clinic', '2026-06-07 16:03:52', 'Waiting', 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, 'patient', 'Ayesha', 'Khan', '9308089109094', '0723002004', 'ayesha_wc@test.com', '123456', NULL, '', 'Western Cape', 'Cape Town', 'Clinic', '2026-06-07 16:03:52', 'Waiting', 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 'patient', 'Ryan', 'Naidoo', '9409099109095', '0723002005', 'ryan_wc@test.com', '123456', NULL, '', 'Western Cape', 'Cape Town', 'Clinic', '2026-06-07 16:03:52', 'Waiting', 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `facility_id` (`facility_id`),
  ADD KEY `fk_appt_patient` (`patient_id`),
  ADD KEY `fk_appt_doctor` (`doctor_id`);

--
-- Indexes for table `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`consultation_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `fk_consult_doctor` (`doctor_id`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`facility_id`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`incident_id`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`medication_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`prescription_id`),
  ADD KEY `consultation_id` (`consultation_id`),
  ADD KEY `medication_id` (`medication_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`referral_id`),
  ADD KEY `fk_ref_patient` (`patient_id`),
  ADD KEY `fk_ref_doctor` (`doctor_id`),
  ADD KEY `fk_ref_consult` (`consultation_id`),
  ADD KEY `fk_ref_from_facility` (`referring_facility_id`),
  ADD KEY `fk_ref_to_facility` (`referred_to_facility_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `incident_id` (`incident_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `consultations`
--
ALTER TABLE `consultations`
  MODIFY `consultation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `facility_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `incident_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `medication_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `prescription_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`facility_id`),
  ADD CONSTRAINT `fk_appt_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_appt_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `consultations`
--
ALTER TABLE `consultations`
  ADD CONSTRAINT `consultations_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`),
  ADD CONSTRAINT `fk_consult_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`consultation_id`),
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`medication_id`) REFERENCES `medications` (`medication_id`);

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `fk_ref_consult` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`consultation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ref_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ref_from_facility` FOREIGN KEY (`referring_facility_id`) REFERENCES `facilities` (`facility_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ref_to_facility` FOREIGN KEY (`referred_to_facility_id`) REFERENCES `facilities` (`facility_id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
