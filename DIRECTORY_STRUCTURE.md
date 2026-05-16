# Grade Management System - Directory Structure

## Single Source of Truth
All files should be in: C:\xampp\htdocs\GradeManagementSystem

## Directory Structure


### Backend (C:\xampp\htdocs\GradeManagementSystem\backend)
- db_connect.php - Database connection
- login.php - Authentication handler
- api_student.php - Student data API
- api_teacher.php - Teacher data API
- submit_grades.php - Grade submission handler
- *_actions.php - CRUD operations for each entity


### Frontend (C:\xampp\htdocs\GradeManagementSystem\Frontend)
- css/styles.css - Global styles
- js/main.js - JavaScript utilities
- pages/
  - auth/login.html - Login page
  - profile.php - User profile (all roles)
  - student/
    - dashboard-student.php - Student dashboard
    - student-grades.php - Student grades view
  - teacher/
    - dashboard-teacher.php - Teacher dashboard
    - teacher-classes.php - Teacher's classes
    - teacher-students.php - Students in class
    - grade-encoding.php - Grade entry form
  - admin/
    - dashboard-admin.php - Admin dashboard
    - manage-students.php - Student management
    - manage-teachers.php - Teacher management
    - manage-classes.php - Class/subject management


### Database (C:\xampp\htdocs\GradeManagementSystem\database)
- grade_db.sql - Database schema and seed data

## URLs to Access the System

- Login: http://localhost/GradeManagementSystem/Frontend/pages/auth/login.html
- Student Dashboard: http://localhost/GradeManagementSystem/Frontend/pages/student/dashboard-student.php
- Teacher Dashboard: http://localhost/GradeManagementSystem/Frontend/pages/teacher/dashboard-teacher.php
- Admin Dashboard: http://localhost/GradeManagementSystem/Frontend/pages/admin/dashboard-admin.php

## Test Credentials

- Student: ID=1, any password, role=student
- Teacher: ID=1, any password, role=teacher
- Admin: ID=1, any password, role=admin
