# E-Learning Platform 🎓

A comprehensive, robust, and feature-rich E-Learning Web Application built with PHP. This platform provides a complete ecosystem for online education, featuring distinct roles for Administrators, Teachers, and Students (Users). 

## 🌟 Features

### 🧑‍🎓 Student (User) Features
* **Interactive Dashboard:** Track personal progress and active courses.
* **Course Enrollment:** Browse available courses and enroll in modules.
* **Quizzes & Assessments:** Take quizzes to test your knowledge.
* **Leaderboards:** View rankings and compete with peers.
* **Progress Tracking:** Monitor completion rates and download certificates/scores (PDF).
* **Account Management:** Profile updates, password resets, and score history.

### 👨‍🏫 Teacher Features
* **Course & Module Management:** Create, edit, and organize course materials.
* **Advanced Quiz Creation:** Build quizzes using a dynamic Question Bank.
* **Question Bank Management:** Import/Export questions, view usage stats, and download templates.
* **Student Tracking:** View student progress and quiz results.
* **Analytics:** Export teacher statistics to track course performance.

### 🛡️ Admin Features
* **Complete System Control:** Manage all users, teachers, and courses.
* **Bulk Actions:** Perform bulk operations on users and courses.
* **Activity Logs:** Monitor system events and user actions.
* **Data Export:** Export activity logs, user data, and courses in CSV format.

## 🛠️ Tech Stack & Dependencies

* **Backend:** PHP, MySQL
* **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5
* **Package Management:** Composer
* **Key Libraries:**
  * vlucas/phpdotenv - Secure environment variable management.
  * phpmailer/phpmailer - Sending system emails.
  * tecnickcom/tcpdf - Generating downloadable PDF reports.
  * phpoffice/phpspreadsheet - Importing/exporting CSV and Excel files.

## 🚀 Installation & Setup

### Prerequisites
* PHP 8.0+
* MySQL / MariaDB
* Composer installed globally
* Apache or Nginx server

### Steps

1. Clone the repository:
   git clone https://github.com/makis666/e-learning-platform.git
   cd e-learning-platform

2. Install PHP Dependencies:
   composer install

3. Environment Configuration:
   * Create a .env file in the root directory.
   * Update the database and mailer configurations:
     DB_HOST=localhost
     DB_NAME=your_database_name
     DB_USER=your_database_user
     DB_PASS=your_database_password
     
     SMTP_HOST=smtp.example.com
     SMTP_USER=your_email@example.com
     SMTP_PASS=your_email_password
     SMTP_PORT=587

4. Database Setup:
   * Create a new database in your MySQL server.
   * Import your SQL schema to set up the necessary tables.

5. Run the Application:
   * Host the folder on your local server (e.g., XAMPP htdocs) or run:
     php -S localhost:8000
   * Open your browser at http://localhost:8000.

## 📂 Project Structure

* /admin - Administrator dashboard and management scripts.
* /teacher - Teacher interface, course creation, and question bank.
* /users - Student interface, quiz taking, and progress viewing.
* /includes - Core PHP scripts (DB connection, functions, mailer, sessions).
* /assets & /bootstrap - Frontend styles and the Bootstrap framework.
* /templates - Reusable UI components (header, footer, sidebar).

## 🛡️ Security
This project uses phpdotenv to keep sensitive credentials secure. Ensure your .env file is included in .gitignore and never pushed to a public repository.

