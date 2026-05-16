# Grade Management System - Setup Guide

## Prerequisites
- XAMPP installed at `C:\xampp`
- MySQL database imported

## Quick Start

### 1. Start XAMPP Services
1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**
4. Wait for both to turn green

### 2. Import Database (if not done)
1. Open browser: `http://localhost/phpmyadmin`
2. Click **New** in the left sidebar
3. Database name: `minds_that_matter_db`
4. Click **Create**
5. Select the database from left sidebar
6. Click **Import** tab
7. Choose your `.sql` file from Downloads
8. Click **Go**

### 3. Add Test Data (Optional)
Run these URLs to add test data:
- `http://localhost/GradeManagementSystem/backend/add_test_data.php`
- `http://localhost/GradeManagementSystem/backend/add_more_test_data.php`

### 4. Access the Application
Open in browser:
```
http://localhost/Grade-Management-System/Frontend/pages/auth/login.html
```

### 5. Test Login

**Student Login:**
- Role: Student
- Username: `1`
- Password: (any)

**Teacher Login:**
- Role: Teacher
- Username: `1`
- Password: (any)

## Working Features

### Student Features
- Dashboard with real grades
- View detailed grades per subject
- Quarterly performance overview

### Teacher Features
- Dashboard with class overview
- Grade Encoding - input/edit student grades
- View classes assigned
- View students list

### Admin Features
- Manage Students (Add/Edit/Delete)
- Manage Teachers (Add/Edit/Delete)

## Troubleshooting

**"Unknown database" error:**
- Make sure you imported the database as `minds_that_matter_db`

**"Unauthorized" errors:**
- Make sure you logged in first
- Try clearing browser cache or use Incognito mode

**Blank pages:**
- Open browser console (F12) to check for errors
- Make sure Apache and MySQL are running in XAMPP

## Database Connection
The database config is in: `backend/db_connect.php`
- Host: `localhost`
- Username: `root`
- Password: (empty)
- Database: `minds_that_matter_db`
