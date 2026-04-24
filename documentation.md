# Student Record Management System - Documentation

## Project Overview
The Student Record Management System is a robust web application built with native PHP and MySQL. It is designed to manage student information, academic departments, and user roles efficiently. The system provides an interactive dashboard, role-based access control, and comprehensive activity logging.

## Technology Stack
- **Backend:** Native PHP (with PDO for database interactions)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, Bootstrap 5
- **Icons:** Bootstrap Icons
- **Data Visualization:** Chart.js

## Key Features

### 1. Role-Based Authentication
The system supports three distinct user roles:
- **Admin:** Full access to all features, including user management, department management, system logs, and exporting data.
- **Teacher:** Access to manage student records (add, edit) and view the dashboard.
- **Student:** Restricted access. Can only view their own student record and profile.

### 2. Interactive Dashboard
- **Statistics:** Quick overview of total students, departments, users, and recent enrollments.
- **Visualizations:** Bar chart showing the distribution of students across departments (using Chart.js).
- **Recent Activity:** Admins can view a live feed of recent user actions (logins, creations, updates, deletions).
- **Student View:** Students see a read-only view of their personal academic details.

### 3. Student Management (`index.php`, `create.php`, `edit.php`, `delete.php`)
- View a paginated list of all students.
- Search students by Name, Student ID, Email, or Department.
- Filter students by Department.
- Add, Edit, and Delete student records (Delete restricted to Admin).
- Export student records to CSV (Admin only).

### 4. Department Management (`departments.php`)
- Manage academic departments (Admin only).
- Inline editing for department names.
- Validates deletion (prevents deleting a department if it has enrolled students).

### 5. User Management (`users.php`)
- Admins can create, edit, and delete user accounts.
- Assign roles (Admin, Teacher, Student) to users.
- Link a user account to a specific student profile.

### 6. Profile Management (`profile.php`)
- Users can update their personal information (Name, Email).
- Secure password change functionality.

### 7. Audit Logging
- Tracks critical user actions across the system.
- Records action type, target table, target ID, user ID, and IP address.

## Database Schema

The system uses a relational database named `student_crud` with the following key tables:
- `users`: System users (Admins, Teachers, Students) with role definitions.
- `students`: Core student profiles.
- `departments`: Academic departments.
- `subjects`: Courses offered.
- `attendance`: Daily student attendance records.
- `results`: Student grades and marks.
- `documents`: Uploaded student files (certificates, IDs).
- `materials`: Study materials uploaded by teachers.
- `notices`: System announcements.
- `activity_log`: Audit trail of user actions.

> [!NOTE]
> The database and tables are automatically created and seeded on the first run via `db.php`.

## File Structure
- `db.php`: Database connection and automatic schema migration script.
- `auth.php`: Session management, role guards, and logging helper.
- `login.php` / `logout.php`: Authentication entry and exit points.
- `index.php`: Main student directory and management view.
- `dashboard.php`: Analytics and summary dashboard.
- `_nav.php`: Shared navigation bar component.
- `style.css`: Custom styling complementing Bootstrap.
- `create.php`, `edit.php`, `delete.php`: Student CRUD operations.
- `departments.php`, `dept_delete.php`: Department CRUD operations.
- `users.php`, `user_create.php`, `user_edit.php`, `user_delete.php`: User CRUD operations.
- `profile.php`: User profile and password management.
- `export.php`: CSV export utility.

## Security Practices
- **SQL Injection Prevention:** Uses PDO prepared statements for all database queries.
- **Password Security:** Passwords are hashed using PHP's native `password_hash()` and verified with `password_verify()`.
- **Access Control:** `require_role()` function in `auth.php` strictly enforces page-level access permissions.
- **XSS Protection:** Outputs are sanitized using `htmlspecialchars()`.

## Setup Instructions
1. Clone or place the project files in your web server directory (e.g., `htdocs` for XAMPP, `www` for WAMP).
2. Ensure the MySQL server is running.
3. Access the project via your local server (e.g., `http://localhost/Ariful/login.php`).
4. The system will automatically create the `student_crud` database and seed initial data.
5. Log in using the default admin credentials:
   - **Email:** admin@admin.com
   - **Password:** admin123
