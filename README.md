# E-Courses Management System
An online platform for managing courses, assignments, quizzes, attendance, and certificates for teachers and students. Built with PHP, MySQL, HTML/CSS, and some JavaScript for user experience.

Features
For Students
Register and login securely

Enroll in courses

View enrolled courses

Download assignments/quizzes

Upload assignment solutions

View attendance records

View/download earned certificates

For Teachers
Register as a teacher (with qualification/experience)

Select up to 2 courses to teach

Upload assignments/quizzes to assigned courses

Mark attendance for enrolled students

Issue certificates to students

Technology Stack
PHP 7+

MySQL 5.7+

HTML5, CSS3, JavaScript (basic)

Bootstrap (optional for UI enhancements)

Apache/Nginx

Getting Started
1. Clone the Repository
git clone https://github.com/yourusername/ecourses-management-system.git
cd ecourses-management-system
2. Database Setup
Import the provided SQL files (or use the schema below) into your MySQL server.

Make sure you have created the following tables: users, students, teachers, courses, teacher_courses, enrollments, assignments, assignment_submissions, attendance, certificates.

3. Configuration
Edit db.php and set your database credentials.

Set file upload permissions for the uploads/ folder.

4. Configure PHP For File Uploads
Edit php.ini to set reasonable limits for upload_max_filesize and post_max_size (e.g., 10M).

5. Run the Application
Access the application in your browser:
http://localhost/ecourses-management-system/

6. Database Schema Overview
Table ||	Description
users ||	Student/Teacher credentials
students ||	Student profiles
teachers ||	Teacher profiles
courses ||	Course information
teacher_courses ||	What a teacher teaches
enrollments ||	What a student is enrolled in
assignments ||	Assignment to a course
assignment_submissions ||	Student submissions
attendance ||	Daily attendance records
certificates ||	Issued certificates

Security Features
Passwords should be securely hashed (update code for production usage)

Input validation/sanitization

Prepared statements for DB queries (prevents SQL injection)

User session and role checking

Contributing
Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change or add.
