-- ============================================================
-- E-Learning Platform — Database Schema
-- Reconstructed from the PHP source code (the repository did
-- not include a schema file). Target: MySQL 8 / MariaDB 10.5+
--
-- Import with:
--   mysql -u root elearning < schema.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS quiz_answers;
DROP TABLE IF EXISTS quiz_attempts;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS questions_bank;
DROP TABLE IF EXISTS quizzes;
DROP TABLE IF EXISTS module_progress;
DROP TABLE IF EXISTS module_materials;
DROP TABLE IF EXISTS modules;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS user_activity_log;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS auth_tokens;
DROP TABLE IF EXISTS users;

-- ------------------------------------------------------------
-- users
-- ------------------------------------------------------------
CREATE TABLE users (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(255) NOT NULL,
  email       VARCHAR(255) NOT NULL,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  profile_pic VARCHAR(255) DEFAULT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- courses
-- `name` is a virtual alias of `title`: leaderboard.php selects
-- c.name while every other page selects c.title.
-- ------------------------------------------------------------
CREATE TABLE courses (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title       VARCHAR(255) NOT NULL,
  name        VARCHAR(255) GENERATED ALWAYS AS (title) VIRTUAL,
  description TEXT DEFAULT NULL,
  teacher_id  INT UNSIGNED DEFAULT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_courses_teacher (teacher_id),
  CONSTRAINT fk_courses_teacher FOREIGN KEY (teacher_id)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- enrollments
-- INSERTs use student_id; the admin pages read e.user_id, so
-- user_id is a virtual alias of student_id.
-- ------------------------------------------------------------
CREATE TABLE enrollments (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED GENERATED ALWAYS AS (student_id) VIRTUAL,
  course_id  INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_enrollments (student_id, course_id),
  KEY idx_enrollments_course (course_id),
  CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id)
    REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_enrollments_course FOREIGN KEY (course_id)
    REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- modules
-- ------------------------------------------------------------
CREATE TABLE modules (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id  INT UNSIGNED NOT NULL,
  title      VARCHAR(255) NOT NULL,
  content    TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_modules_course (course_id),
  CONSTRAINT fk_modules_course FOREIGN KEY (course_id)
    REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- module_materials
-- `filename` is a virtual alias of `file_name` (read as a
-- fallback in course_modules.php).
-- ------------------------------------------------------------
CREATE TABLE module_materials (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  module_id       INT UNSIGNED NOT NULL,
  file_name       VARCHAR(255) NOT NULL,
  filename        VARCHAR(255) GENERATED ALWAYS AS (file_name) VIRTUAL,
  original_name   VARCHAR(255) DEFAULT NULL,
  file_type       VARCHAR(10) DEFAULT NULL,
  size            INT UNSIGNED DEFAULT NULL,
  download_count  INT UNSIGNED NOT NULL DEFAULT 0,
  is_teacher_only TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_materials_module (module_id),
  CONSTRAINT fk_materials_module FOREIGN KEY (module_id)
    REFERENCES modules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- module_progress
-- INSERT IGNORE + delete-by-pair => UNIQUE(student_id, module_id);
-- teacher/view_progress.php also does COUNT(mp.id), so a real id
-- column is required.
-- ------------------------------------------------------------
CREATE TABLE module_progress (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id   INT UNSIGNED NOT NULL,
  module_id    INT UNSIGNED NOT NULL,
  completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_module_progress (student_id, module_id),
  KEY idx_progress_module (module_id),
  CONSTRAINT fk_progress_student FOREIGN KEY (student_id)
    REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_progress_module FOREIGN KEY (module_id)
    REFERENCES modules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- quizzes
-- teacher_id exists only because the question-bank pages query
-- quizzes WHERE teacher_id = ?; the insert in create_quiz.php
-- does not provide it, so a trigger copies it from the course.
-- ------------------------------------------------------------
CREATE TABLE quizzes (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id        INT UNSIGNED NOT NULL,
  module_id        INT UNSIGNED DEFAULT NULL,
  teacher_id       INT UNSIGNED DEFAULT NULL,
  title            VARCHAR(255) NOT NULL,
  duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
  week             INT UNSIGNED NOT NULL DEFAULT 1,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_quizzes_course (course_id),
  KEY idx_quizzes_module (module_id),
  KEY idx_quizzes_teacher (teacher_id),
  CONSTRAINT fk_quizzes_course FOREIGN KEY (course_id)
    REFERENCES courses (id) ON DELETE CASCADE,
  CONSTRAINT fk_quizzes_module FOREIGN KEY (module_id)
    REFERENCES modules (id) ON DELETE SET NULL,
  CONSTRAINT fk_quizzes_teacher FOREIGN KEY (teacher_id)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TRIGGER trg_quizzes_set_teacher
BEFORE INSERT ON quizzes FOR EACH ROW
SET NEW.teacher_id = COALESCE(
  NEW.teacher_id,
  (SELECT teacher_id FROM courses WHERE id = NEW.course_id)
);

-- ------------------------------------------------------------
-- questions_bank (teacher's reusable question bank)
-- type: mcq | true_false | matching | fill_blank_dropdown
-- ------------------------------------------------------------
CREATE TABLE questions_bank (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  teacher_id     INT UNSIGNED NOT NULL,
  course_id      INT UNSIGNED DEFAULT NULL,
  quiz_id        INT UNSIGNED DEFAULT NULL,
  question_text  TEXT NOT NULL,
  type           VARCHAR(30) NOT NULL DEFAULT 'mcq',
  option_a       TEXT DEFAULT NULL,
  option_b       TEXT DEFAULT NULL,
  option_c       TEXT DEFAULT NULL,
  option_d       TEXT DEFAULT NULL,
  correct_answer TEXT DEFAULT NULL,
  hint           TEXT DEFAULT NULL,
  explanation    TEXT DEFAULT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_qbank_teacher (teacher_id),
  KEY idx_qbank_course (course_id),
  KEY idx_qbank_quiz (quiz_id),
  CONSTRAINT fk_qbank_teacher FOREIGN KEY (teacher_id)
    REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_qbank_course FOREIGN KEY (course_id)
    REFERENCES courses (id) ON DELETE SET NULL,
  CONSTRAINT fk_qbank_quiz FOREIGN KEY (quiz_id)
    REFERENCES quizzes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- questions (questions attached to a quiz)
-- correct_answer holds a plain string for mcq/true_false and a
-- JSON payload for matching / fill_blank_dropdown.
-- ------------------------------------------------------------
CREATE TABLE questions (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  quiz_id        INT UNSIGNED NOT NULL,
  qb_id          INT UNSIGNED DEFAULT NULL,
  question_text  TEXT NOT NULL,
  type           VARCHAR(30) NOT NULL DEFAULT 'mcq',
  option_a       TEXT DEFAULT NULL,
  option_b       TEXT DEFAULT NULL,
  option_c       TEXT DEFAULT NULL,
  option_d       TEXT DEFAULT NULL,
  correct_answer TEXT DEFAULT NULL,
  hint           TEXT DEFAULT NULL,
  explanation    TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_questions_quiz (quiz_id),
  KEY idx_questions_qb (qb_id),
  CONSTRAINT fk_questions_quiz FOREIGN KEY (quiz_id)
    REFERENCES quizzes (id) ON DELETE CASCADE,
  CONSTRAINT fk_questions_qb FOREIGN KEY (qb_id)
    REFERENCES questions_bank (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- quiz_attempts
-- attempted_at / time_taken_seconds are never written by the
-- code, so they need defaults.
-- ------------------------------------------------------------
CREATE TABLE quiz_attempts (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id            INT UNSIGNED NOT NULL,
  quiz_id            INT UNSIGNED NOT NULL,
  score              DECIMAL(5,2) NOT NULL DEFAULT 0,
  total_questions    INT UNSIGNED NOT NULL DEFAULT 0,
  attempted_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  time_taken_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_attempts_user (user_id),
  KEY idx_attempts_quiz (quiz_id),
  CONSTRAINT fk_attempts_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_attempts_quiz FOREIGN KEY (quiz_id)
    REFERENCES quizzes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- quiz_answers
-- `selected_answer` is a virtual alias of `student_answer`
-- (quiz_results.php reads the former, everything else the latter).
-- ------------------------------------------------------------
CREATE TABLE quiz_answers (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  attempt_id      INT UNSIGNED NOT NULL,
  user_id         INT UNSIGNED NOT NULL,
  quiz_id         INT UNSIGNED NOT NULL,
  question_id     INT UNSIGNED NOT NULL,
  student_answer  TEXT DEFAULT NULL,
  selected_answer TEXT GENERATED ALWAYS AS (student_answer) VIRTUAL,
  is_correct      TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_answers_attempt (attempt_id),
  KEY idx_answers_user_quiz (user_id, quiz_id),
  KEY idx_answers_question (question_id),
  CONSTRAINT fk_answers_attempt FOREIGN KEY (attempt_id)
    REFERENCES quiz_attempts (id) ON DELETE CASCADE,
  CONSTRAINT fk_answers_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_answers_quiz FOREIGN KEY (quiz_id)
    REFERENCES quizzes (id) ON DELETE CASCADE,
  CONSTRAINT fk_answers_question FOREIGN KEY (question_id)
    REFERENCES questions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- user_activity_log
-- ------------------------------------------------------------
CREATE TABLE user_activity_log (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  activity   VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_activity_user (user_id),
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- password_resets / auth_tokens
-- expires_at is VARCHAR on purpose: the app writes PHP
-- date('d-m-Y H:i:s') strings, which a strict DATETIME column
-- would reject; the app reads them back with strtotime().
-- ------------------------------------------------------------
CREATE TABLE password_resets (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email      VARCHAR(255) NOT NULL,
  token      VARCHAR(64) NOT NULL,
  expires_at VARCHAR(32) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_resets_email (email),
  KEY idx_resets_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_tokens (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  token      VARCHAR(64) NOT NULL,
  expires_at VARCHAR(32) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_tokens_token (token),
  CONSTRAINT fk_tokens_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Seed accounts (local development)
--   admin@elearning.local   / admin123
--   teacher@elearning.local / teacher123
--   student@elearning.local / student123
-- ============================================================
INSERT INTO users (name, email, password, role) VALUES
('Admin',        'admin@elearning.local',   '$2y$10$hpu9tSJ3tb8FazVlHKSXmusciv/BVR7kYLUUpetatowkc1KGzik/q', 'admin'),
('Demo Teacher', 'teacher@elearning.local', '$2y$10$Us4szG.gyc0q5Wxq5ghqEOfkEkWrqhxfP3.3XWhRuX.TRUVNGO6Ve', 'teacher'),
('Demo Student', 'student@elearning.local', '$2y$10$.cmZXyRb.dQHKRZMSqLEPOVTd.DMYoocn.nqU3TvC1MZ7dfvujiRa', 'student');
