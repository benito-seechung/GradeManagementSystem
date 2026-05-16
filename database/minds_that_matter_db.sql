-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 12, 2026 at 01:58 PM
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
-- Database: `minds_that_matter_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `advisory`
--

CREATE TABLE `advisory` (
  `Advisory_ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `School_Year` varchar(9) NOT NULL,
  `Teacher_ID` int(11) NOT NULL,
  `Section_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_history`
--

CREATE TABLE `enrollment_history` (
  `Enrollment_ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `School_Year` varchar(9) NOT NULL,
  `Date_Enrolled` date NOT NULL,
  `Enrollment_Status` varchar(20) DEFAULT 'Enrolled',
  `Student_ID` int(11) NOT NULL,
  `Section_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `grade`
--

CREATE TABLE `grade` (
  `Grade_ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Q1` decimal(5,2) DEFAULT 0.00,
  `Q2` decimal(5,2) DEFAULT 0.00,
  `Q3` decimal(5,2) DEFAULT 0.00,
  `Q4` decimal(5,2) DEFAULT 0.00,
  `Grade_Avg` decimal(5,2) GENERATED ALWAYS AS ((`Q1` + `Q2` + `Q3` + `Q4`) / 4) VIRTUAL,
  `Remarks` varchar(255) DEFAULT NULL,
  `Student_ID` int(11) NOT NULL,
  `Assignment_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `grade_submission`
--

CREATE TABLE `grade_submission` (
  `Submission_ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Quarter` int(1) NOT NULL,
  `Status` varchar(20) DEFAULT 'Draft',
  `Date_Submitted` datetime DEFAULT NULL,
  `Assignment_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `guardian`
--

CREATE TABLE `guardian` (
  `Guardian_ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Firstname` varchar(50) NOT NULL,
  `Middlename` varchar(50) DEFAULT NULL,
  `Lastname` varchar(50) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Address` varchar(255) NOT NULL,
  `Contact_No` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `level`
--

CREATE TABLE `level` (
  `Level_ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Level_Name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `Section_ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Section_Name` varchar(50) NOT NULL,
  `Level_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `Student_ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Firstname` varchar(50) NOT NULL,
  `Middlename` varchar(50) NOT NULL,
  `Lastname` varchar(50) NOT NULL,
  `Address` varchar(250) NOT NULL,
  `Birthdate` date NOT NULL,
  `Guardian_Relationship` varchar(30) NOT NULL,
  `Guardian_ID` int(11) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Age` int(11) GENERATED ALWAYS AS (year(curdate()) - year(`Birthdate`)) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `Subject_ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Subject_Name` varchar(100) NOT NULL,
  `Level_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `subject_assignment`
--

CREATE TABLE `subject_assignment` (
  `Assignment_ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `School_Year` varchar(9) NOT NULL,
  `Day` varchar(10) NOT NULL,
  `TimeStart` time NOT NULL,
  `TimeEnd` time NOT NULL,
  `Teacher_ID` int(11) NOT NULL,
  `Section_ID` int(11) NOT NULL,
  `Subject_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

CREATE TABLE `teacher` (
  `Teacher_ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Firstname` varchar(50) NOT NULL,
  `Middlename` varchar(50) DEFAULT NULL,
  `Lastname` varchar(50) NOT NULL,
  `Address` varchar(255) NOT NULL,
  `Birthdate` date NOT NULL,
  `Subject_Teacher` varchar(3) DEFAULT 'No',
  `Adviser` varchar(3) DEFAULT 'No',
  `Email` varchar(100) NOT NULL,
  `Contact_No` varchar(15) NOT NULL,
  `Password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advisory`
--
ALTER TABLE `advisory`
  ADD KEY `Teacher_ID` (`Teacher_ID`),
  ADD KEY `Section_ID` (`Section_ID`);

--
-- Indexes for table `enrollment_history`
--
ALTER TABLE `enrollment_history`
  ADD KEY `Student_ID` (`Student_ID`),
  ADD KEY `Section_ID` (`Section_ID`);

--
-- Indexes for table `grade`
--
ALTER TABLE `grade`
  ADD KEY `Student_ID` (`Student_ID`),
  ADD KEY `Assignment_ID` (`Assignment_ID`);

--
-- Indexes for table `grade_submission`
--
ALTER TABLE `grade_submission`
  ADD KEY `Assignment_ID` (`Assignment_ID`);

--
-- Indexes for table `level`
--
ALTER TABLE `level`
  ADD UNIQUE KEY `Level_Name` (`Level_Name`);

--
-- Indexes for table `section`
--
ALTER TABLE `section`
  ADD KEY `Level_ID` (`Level_ID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD KEY `fk_student_guardian` (`Guardian_ID`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD KEY `Level_ID` (`Level_ID`);

--
-- Indexes for table `subject_assignment`
--
ALTER TABLE `subject_assignment`
  ADD KEY `Teacher_ID` (`Teacher_ID`),
  ADD KEY `Section_ID` (`Section_ID`),
  ADD KEY `Subject_ID` (`Subject_ID`);

--
-- Indexes for table `teacher`
--
ALTER TABLE `teacher`
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `advisory`
--
ALTER TABLE `advisory`
  ADD CONSTRAINT `advisory_ibfk_1` FOREIGN KEY (`Teacher_ID`) REFERENCES `teacher` (`Teacher_ID`),
  ADD CONSTRAINT `advisory_ibfk_2` FOREIGN KEY (`Section_ID`) REFERENCES `section` (`Section_ID`);

--
-- Constraints for table `enrollment_history`
--
ALTER TABLE `enrollment_history`
  ADD CONSTRAINT `enrollment_history_ibfk_1` FOREIGN KEY (`Student_ID`) REFERENCES `student` (`Student_ID`),
  ADD CONSTRAINT `enrollment_history_ibfk_2` FOREIGN KEY (`Section_ID`) REFERENCES `section` (`Section_ID`);

--
-- Constraints for table `grade`
--
ALTER TABLE `grade`
  ADD CONSTRAINT `grade_ibfk_1` FOREIGN KEY (`Student_ID`) REFERENCES `student` (`Student_ID`),
  ADD CONSTRAINT `grade_ibfk_2` FOREIGN KEY (`Assignment_ID`) REFERENCES `subject_assignment` (`Assignment_ID`);

--
-- Constraints for table `grade_submission`
--
ALTER TABLE `grade_submission`
  ADD CONSTRAINT `grade_submission_ibfk_1` FOREIGN KEY (`Assignment_ID`) REFERENCES `subject_assignment` (`Assignment_ID`);

--
-- Constraints for table `section`
--
ALTER TABLE `section`
  ADD CONSTRAINT `section_ibfk_1` FOREIGN KEY (`Level_ID`) REFERENCES `level` (`Level_ID`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `fk_student_guardian` FOREIGN KEY (`Guardian_ID`) REFERENCES `guardian` (`Guardian_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `subject`
--
ALTER TABLE `subject`
  ADD CONSTRAINT `subject_ibfk_1` FOREIGN KEY (`Level_ID`) REFERENCES `level` (`Level_ID`);

--
-- Constraints for table `subject_assignment`
--
ALTER TABLE `subject_assignment`
  ADD CONSTRAINT `subject_assignment_ibfk_1` FOREIGN KEY (`Teacher_ID`) REFERENCES `teacher` (`Teacher_ID`),
  ADD CONSTRAINT `subject_assignment_ibfk_2` FOREIGN KEY (`Section_ID`) REFERENCES `section` (`Section_ID`),
  ADD CONSTRAINT `subject_assignment_ibfk_3` FOREIGN KEY (`Subject_ID`) REFERENCES `subject` (`Subject_ID`);

-- Reset auto-increment counters
ALTER TABLE `student` AUTO_INCREMENT=1;
ALTER TABLE `teacher` AUTO_INCREMENT=1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
